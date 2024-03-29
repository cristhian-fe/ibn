/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function(tinymce) {
    var each = tinymce.each;

    // Checks if the selection/caret is at the start of the specified block element
    function isAtStart(rng, par) {
        var doc = par.ownerDocument, rng2 = doc.createRange(), elm;

        rng2.setStartBefore(par);
        rng2.setEnd(rng.endContainer, rng.endOffset);

        elm = doc.createElement('body');
        elm.appendChild(rng2.cloneContents());

        // Check for text characters of other elements that should be treated as content
        return elm.innerHTML.replace(/<(br|img|object|embed|input|textarea)[^>]*>/gi, '-').replace(/<[^>]+>/g, '').length == 0;
    }

    function getSpanVal(td, name) {
        return parseInt(td.getAttribute(name) || 1);
    }

    /**
     * Table Grid class.
     */
    function TableGrid(table, dom, selection) {
        var grid, startPos, endPos, selectedCell;

        buildGrid();
        selectedCell = dom.getParent(selection.getStart(), 'th,td');
        if (selectedCell) {
            startPos = getPos(selectedCell);
            endPos = findEndPos();
            selectedCell = getCell(startPos.x, startPos.y);
        }

        function cloneNode(node, children) {
            node = node.cloneNode(children);
            node.removeAttribute('id');

            return node;
        }

        function buildGrid() {
            var startY = 0;

            grid = [];

            each(['thead', 'tbody', 'tfoot'], function(part) {
                var rows = dom.select('> ' + part + ' tr', table);

                each(rows, function(tr, y) {
                    y += startY;

                    each(dom.select('> td, > th', tr), function(td, x) {
                        var x2, y2, rowspan, colspan;

                        // Skip over existing cells produced by rowspan
                        if (grid[y]) {
                            while (grid[y][x])
                                x++;
                        }

                        // Get col/rowspan from cell
                        rowspan = getSpanVal(td, 'rowspan');
                        colspan = getSpanVal(td, 'colspan');

                        // Fill out rowspan/colspan right and down
                        for (y2 = y; y2 < y + rowspan; y2++) {
                            if (!grid[y2])
                                grid[y2] = [];

                            for (x2 = x; x2 < x + colspan; x2++) {
                                grid[y2][x2] = {
                                    part: part,
                                    real: y2 == y && x2 == x,
                                    elm: td,
                                    rowspan: rowspan,
                                    colspan: colspan
                                };
                            }
                        }
                    });
                });

                startY += rows.length;
            });
        }

        function getCell(x, y) {
            var row;

            row = grid[y];
            if (row)
                return row[x];
        }

        function setSpanVal(td, name, val) {
            if (td) {
                val = parseInt(val);

                if (val === 1)
                    td.removeAttribute(name, 1);
                else
                    td.setAttribute(name, val, 1);
            }
        }

        function isCellSelected(cell) {
            return cell && (dom.hasClass(cell.elm, 'mceSelected') || cell == selectedCell);
        }

        function getSelectedRows() {
            var rows = [];

            each(table.rows, function(row) {
                each(row.cells, function(cell) {
                    if (dom.hasClass(cell, 'mceSelected') || cell == selectedCell.elm) {
                        rows.push(row);
                        return false;
                    }
                });
            });

            return rows;
        }

        function deleteTable() {
            var rng = dom.createRng();

            rng.setStartAfter(table);
            rng.setEndAfter(table);

            selection.setRng(rng);

            dom.remove(table);
        }

        function cloneCell(cell) {
            var formatNode;

            // Clone formats
            tinymce.walk(cell, function(node) {
                var curNode;

                if (node.nodeType == 3) {
                    each(dom.getParents(node.parentNode, null, cell).reverse(), function(node) {
                        node = cloneNode(node, false);

                        if (!formatNode)
                            formatNode = curNode = node;
                        else if (curNode)
                            curNode.appendChild(node);

                        curNode = node;
                    });

                    // Add something to the inner node
                    if (curNode)
                        curNode.innerHTML = tinymce.isIE ? '&nbsp;' : '<br data-mce-bogus="1" />';

                    return false;
                }
            }, 'childNodes');

            cell = cloneNode(cell, false);
            setSpanVal(cell, 'rowSpan', 1);
            setSpanVal(cell, 'colSpan', 1);

            if (formatNode) {
                cell.appendChild(formatNode);
            } else {
                if (!tinymce.isIE)
                    cell.innerHTML = '<br data-mce-bogus="1" />';
            }

            return cell;
        }

        function cleanup() {
            var rng = dom.createRng();

            // Empty rows
            each(dom.select('tr', table), function(tr) {
                if (tr.cells.length == 0)
                    dom.remove(tr);
            });

            // Empty table
            if (dom.select('tr', table).length == 0) {
                rng.setStartAfter(table);
                rng.setEndAfter(table);
                selection.setRng(rng);
                dom.remove(table);
                return;
            }

            // Empty header/body/footer
            each(dom.select('thead,tbody,tfoot', table), function(part) {
                if (part.rows.length == 0)
                    dom.remove(part);
            });

            // Restore selection to start position if it still exists
            buildGrid();

            // Restore the selection to the closest table position
            row = grid[Math.min(grid.length - 1, startPos.y)];
            if (row) {
                selection.select(row[Math.min(row.length - 1, startPos.x)].elm, true);
                selection.collapse(true);
            }
        }

        function fillLeftDown(x, y, rows, cols) {
            var tr, x2, r, c, cell;

            tr = grid[y][x].elm.parentNode;
            for (r = 1; r <= rows; r++) {
                tr = dom.getNext(tr, 'tr');

                if (tr) {
                    // Loop left to find real cell
                    for (x2 = x; x2 >= 0; x2--) {
                        cell = grid[y + r][x2].elm;

                        if (cell.parentNode == tr) {
                            // Append clones after
                            for (c = 1; c <= cols; c++)
                                dom.insertAfter(cloneCell(cell), cell);

                            break;
                        }
                    }

                    if (x2 == -1) {
                        // Insert nodes before first cell
                        for (c = 1; c <= cols; c++)
                            tr.insertBefore(cloneCell(tr.cells[0]), tr.cells[0]);
                    }
                }
            }
        }

        function split() {
            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    var colSpan, rowSpan, newCell, i;

                    if (isCellSelected(cell)) {
                        cell = cell.elm;
                        colSpan = getSpanVal(cell, 'colspan');
                        rowSpan = getSpanVal(cell, 'rowspan');

                        if (colSpan > 1 || rowSpan > 1) {
                            setSpanVal(cell, 'rowSpan', 1);
                            setSpanVal(cell, 'colSpan', 1);

                            // Insert cells right
                            for (i = 0; i < colSpan - 1; i++)
                                dom.insertAfter(cloneCell(cell), cell);

                            fillLeftDown(x, y, rowSpan - 1, colSpan);
                        }
                    }
                });
            });
        }

        function merge(cell, cols, rows) {
            var startX, startY, endX, endY, x, y, startCell, endCell, cell, children, count;

            // Use specified cell and cols/rows
            if (cell) {
                pos = getPos(cell);
                startX = pos.x;
                startY = pos.y;
                endX = startX + (cols - 1);
                endY = startY + (rows - 1);
            } else {
                startPos = endPos = null;

                // Calculate start/end pos by checking for selected cells in grid works better with context menu
                each(grid, function(row, y) {
                    each(row, function(cell, x) {
                        if (isCellSelected(cell)) {
                            if (!startPos) {
                                startPos = {x: x, y: y};
                            }

                            endPos = {x: x, y: y};
                        }
                    });
                });

                // Use selection
                startX = startPos.x;
                startY = startPos.y;
                endX = endPos.x;
                endY = endPos.y;
            }

            // Find start/end cells
            startCell = getCell(startX, startY);
            endCell = getCell(endX, endY);

            // Check if the cells exists and if they are of the same part for example tbody = tbody
            if (startCell && endCell && startCell.part == endCell.part) {
                // Split and rebuild grid
                split();
                buildGrid();

                // Set row/col span to start cell
                startCell = getCell(startX, startY).elm;
                setSpanVal(startCell, 'colSpan', (endX - startX) + 1);
                setSpanVal(startCell, 'rowSpan', (endY - startY) + 1);

                // Remove other cells and add it's contents to the start cell
                for (y = startY; y <= endY; y++) {
                    for (x = startX; x <= endX; x++) {
                        if (!grid[y] || !grid[y][x])
                            continue;

                        cell = grid[y][x].elm;

                        if (cell != startCell) {
                            // Move children to startCell
                            children = tinymce.grep(cell.childNodes);
                            each(children, function(node) {
                                startCell.appendChild(node);
                            });

                            // Remove bogus nodes if there is children in the target cell
                            if (children.length) {
                                children = tinymce.grep(startCell.childNodes);
                                count = 0;
                                each(children, function(node) {
                                    if (node.nodeName == 'BR' && dom.getAttrib(node, 'data-mce-bogus') && count++ < children.length - 1)
                                        startCell.removeChild(node);
                                });
                            }

                            // Remove cell
                            dom.remove(cell);
                        }
                    }
                }

                // Remove empty rows etc and restore caret location
                cleanup();
            }
        }

        function insertRow(before) {
            var posY, cell, lastCell, x, rowElm, newRow, newCell, otherCell, rowSpan;

            // Find first/last row
            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    if (isCellSelected(cell)) {
                        cell = cell.elm;
                        rowElm = cell.parentNode;
                        newRow = cloneNode(rowElm, false);
                        posY = y;

                        if (before)
                            return false;
                    }
                });

                if (before)
                    return !posY;
            });

            for (x = 0; x < grid[0].length; x++) {
                // Cell not found could be because of an invalid table structure
                if (!grid[posY][x])
                    continue;

                cell = grid[posY][x].elm;

                if (cell != lastCell) {
                    if (!before) {
                        rowSpan = getSpanVal(cell, 'rowspan');
                        if (rowSpan > 1) {
                            setSpanVal(cell, 'rowSpan', rowSpan + 1);
                            continue;
                        }
                    } else {
                        // Check if cell above can be expanded
                        if (posY > 0 && grid[posY - 1][x]) {
                            otherCell = grid[posY - 1][x].elm;
                            rowSpan = getSpanVal(otherCell, 'rowSpan');
                            if (rowSpan > 1) {
                                setSpanVal(otherCell, 'rowSpan', rowSpan + 1);
                                continue;
                            }
                        }
                    }

                    // Insert new cell into new row
                    newCell = cloneCell(cell);
                    setSpanVal(newCell, 'colSpan', cell.colSpan);

                    newRow.appendChild(newCell);

                    lastCell = cell;
                }
            }

            if (newRow.hasChildNodes()) {
                if (!before)
                    dom.insertAfter(newRow, rowElm);
                else
                    rowElm.parentNode.insertBefore(newRow, rowElm);
            }
        }

        function insertCol(before) {
            var posX, lastCell;

            // Find first/last column
            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    if (isCellSelected(cell)) {
                        posX = x;

                        if (before)
                            return false;
                    }
                });

                if (before)
                    return !posX;
            });

            each(grid, function(row, y) {
                var cell, rowSpan, colSpan;

                if (!row[posX])
                    return;

                cell = row[posX].elm;
                if (cell != lastCell) {
                    colSpan = getSpanVal(cell, 'colspan');
                    rowSpan = getSpanVal(cell, 'rowspan');

                    if (colSpan == 1) {
                        if (!before) {
                            dom.insertAfter(cloneCell(cell), cell);
                            fillLeftDown(posX, y, rowSpan - 1, colSpan);
                        } else {
                            cell.parentNode.insertBefore(cloneCell(cell), cell);
                            fillLeftDown(posX, y, rowSpan - 1, colSpan);
                        }
                    } else
                        setSpanVal(cell, 'colSpan', cell.colSpan + 1);

                    lastCell = cell;
                }
            });
        }

        function deleteCols() {
            var cols = [];

            // Get selected column indexes
            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    if (isCellSelected(cell) && tinymce.inArray(cols, x) === -1) {
                        each(grid, function(row) {
                            var cell = row[x].elm, colSpan;

                            colSpan = getSpanVal(cell, 'colSpan');

                            if (colSpan > 1)
                                setSpanVal(cell, 'colSpan', colSpan - 1);
                            else
                                dom.remove(cell);
                        });

                        cols.push(x);
                    }
                });
            });

            cleanup();
        }

        function deleteRows() {
            var rows;

            function deleteRow(tr) {
                var nextTr, pos, lastCell;

                nextTr = dom.getNext(tr, 'tr');

                // Move down row spanned cells
                each(tr.cells, function(cell) {
                    var rowSpan = getSpanVal(cell, 'rowSpan');

                    if (rowSpan > 1) {
                        setSpanVal(cell, 'rowSpan', rowSpan - 1);
                        pos = getPos(cell);
                        fillLeftDown(pos.x, pos.y, 1, 1);
                    }
                });

                // Delete cells
                pos = getPos(tr.cells[0]);
                each(grid[pos.y], function(cell) {
                    var rowSpan;

                    cell = cell.elm;

                    if (cell != lastCell) {
                        rowSpan = getSpanVal(cell, 'rowSpan');

                        if (rowSpan <= 1)
                            dom.remove(cell);
                        else
                            setSpanVal(cell, 'rowSpan', rowSpan - 1);

                        lastCell = cell;
                    }
                });
            }

            // Get selected rows and move selection out of scope
            rows = getSelectedRows();

            // Delete all selected rows
            each(rows.reverse(), function(tr) {
                deleteRow(tr);
            });

            cleanup();
        }

        function cutRows() {
            var rows = getSelectedRows();

            dom.remove(rows);
            cleanup();

            return rows;
        }

        function copyRows() {
            var rows = getSelectedRows();

            each(rows, function(row, i) {
                rows[i] = cloneNode(row, true);
            });

            return rows;
        }
        
        function pasteRows(rows, before) {
            // If we don't have any rows in the clipboard, return immediately
            if (!rows)
                return;

            var selectedRows = getSelectedRows(),
                    targetRow = selectedRows[before ? 0 : selectedRows.length - 1],
                    targetCellCount = targetRow.cells.length;

            // Calc target cell count
            each(grid, function(row) {
                var match;

                targetCellCount = 0;
                each(row, function(cell, x) {
                    if (cell.real)
                        targetCellCount += cell.colspan;

                    if (cell.elm.parentNode == targetRow)
                        match = 1;
                });

                if (match)
                    return false;
            });

            if (!before)
                rows.reverse();

            each(rows, function(row) {
                var cellCount = row.cells.length, cell;

                // Remove col/rowspans
                for (i = 0; i < cellCount; i++) {
                    cell = row.cells[i];
                    setSpanVal(cell, 'colSpan', 1);
                    setSpanVal(cell, 'rowSpan', 1);
                }

                // Needs more cells
                for (i = cellCount; i < targetCellCount; i++)
                    row.appendChild(cloneCell(row.cells[cellCount - 1]));

                // Needs less cells
                for (i = targetCellCount; i < cellCount; i++)
                    dom.remove(row.cells[i]);

                // Add before/after
                if (before)
                    targetRow.parentNode.insertBefore(row, targetRow);
                else
                    dom.insertAfter(row, targetRow);
            });

            // Remove current selection
            dom.removeClass(dom.select('td.mceSelected,th.mceSelected'), 'mceSelected');
        }

        function getPos(target) {
            var pos;

            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    if (cell.elm == target) {
                        pos = {x: x, y: y};
                        return false;
                    }
                });

                return !pos;
            });

            return pos;
        }

        function setStartCell(cell) {
            startPos = getPos(cell);
        }

        function findEndPos() {
            var pos, maxX, maxY;

            maxX = maxY = 0;

            each(grid, function(row, y) {
                each(row, function(cell, x) {
                    var colSpan, rowSpan;

                    if (isCellSelected(cell)) {
                        cell = grid[y][x];

                        if (x > maxX)
                            maxX = x;

                        if (y > maxY)
                            maxY = y;

                        if (cell.real) {
                            colSpan = cell.colspan - 1;
                            rowSpan = cell.rowspan - 1;

                            if (colSpan) {
                                if (x + colSpan > maxX)
                                    maxX = x + colSpan;
                            }

                            if (rowSpan) {
                                if (y + rowSpan > maxY)
                                    maxY = y + rowSpan;
                            }
                        }
                    }
                });
            });

            return {x: maxX, y: maxY};
        }

        function setEndCell(cell) {
            var startX, startY, endX, endY, maxX, maxY, colSpan, rowSpan;

            endPos = getPos(cell);

            if (startPos && endPos) {
                // Get start/end positions
                startX = Math.min(startPos.x, endPos.x);
                startY = Math.min(startPos.y, endPos.y);
                endX = Math.max(startPos.x, endPos.x);
                endY = Math.max(startPos.y, endPos.y);

                // Expand end positon to include spans
                maxX = endX;
                maxY = endY;

                // Expand startX
                for (y = startY; y <= maxY; y++) {
                    cell = grid[y][startX];

                    if (!cell.real) {
                        if (startX - (cell.colspan - 1) < startX)
                            startX -= cell.colspan - 1;
                    }
                }

                // Expand startY
                for (x = startX; x <= maxX; x++) {
                    cell = grid[startY][x];

                    if (!cell.real) {
                        if (startY - (cell.rowspan - 1) < startY)
                            startY -= cell.rowspan - 1;
                    }
                }

                // Find max X, Y
                for (y = startY; y <= endY; y++) {
                    for (x = startX; x <= endX; x++) {
                        cell = grid[y][x];

                        if (cell.real) {
                            colSpan = cell.colspan - 1;
                            rowSpan = cell.rowspan - 1;

                            if (colSpan) {
                                if (x + colSpan > maxX)
                                    maxX = x + colSpan;
                            }

                            if (rowSpan) {
                                if (y + rowSpan > maxY)
                                    maxY = y + rowSpan;
                            }
                        }
                    }
                }

                // Remove current selection
                dom.removeClass(dom.select('td.mceSelected,th.mceSelected'), 'mceSelected');

                // Add new selection
                for (y = startY; y <= maxY; y++) {
                    for (x = startX; x <= maxX; x++) {
                        if (grid[y][x])
                            dom.addClass(grid[y][x].elm, 'mceSelected');
                    }
                }
            }
        }

        // Expose to public
        tinymce.extend(this, {
            deleteTable: deleteTable,
            split: split,
            merge: merge,
            insertRow: insertRow,
            insertCol: insertCol,
            deleteCols: deleteCols,
            deleteRows: deleteRows,
            cutRows: cutRows,
            copyRows: copyRows,
            pasteRows: pasteRows,
            getPos: getPos,
            setStartCell: setStartCell,
            setEndCell: setEndCell
        });
    }

    tinymce.create('tinymce.plugins.TablePlugin', {
        init: function(ed, url) {
            var winMan, clipboardRows, hasCellSelection = true; // Might be selected cells on reload

            // store editor
            this.editor = ed;

            function createTableGrid(node) {
                var selection = ed.selection, tblElm = ed.dom.getParent(node || selection.getNode(), 'table');

                if (tblElm)
                    return new TableGrid(tblElm, ed.dom, selection);
            }

            function cleanup() {
                // Restore selection possibilities
                ed.getBody().style.webkitUserSelect = '';

                if (hasCellSelection) {
                    ed.dom.removeClass(ed.dom.select('td.mceSelected,th.mceSelected'), 'mceSelected');
                    hasCellSelection = false;
                }
            }

            // Register buttons
            each([
                ['table', 'table.desc', 'mceInsertTable', true],
                ['delete_table', 'table.del', 'mceTableDelete'],
                ['delete_col', 'table.delete_col_desc', 'mceTableDeleteCol'],
                ['delete_row', 'table.delete_row_desc', 'mceTableDeleteRow'],
                ['col_after', 'table.col_after_desc', 'mceTableInsertColAfter'],
                ['col_before', 'table.col_before_desc', 'mceTableInsertColBefore'],
                ['row_after', 'table.row_after_desc', 'mceTableInsertRowAfter'],
                ['row_before', 'table.row_before_desc', 'mceTableInsertRowBefore'],
                ['row_props', 'table.row_desc', 'mceTableRowProps', true],
                ['cell_props', 'table.cell_desc', 'mceTableCellProps', true],
                ['split_cells', 'table.split_cells_desc', 'mceTableSplitCells', true],
                ['merge_cells', 'table.merge_cells_desc', 'mceTableMergeCells', true]
            ], function(c) {
                ed.addButton(c[0], {title: c[1], cmd: c[2], ui: c[3]});
            });

            // Select whole table is a table border is clicked
            if (!tinymce.isIE) {
                ed.onClick.add(function(ed, e) {
                    e = e.target;

                    if (e.nodeName === 'TABLE') {
                        ed.selection.select(e);
                        ed.nodeChanged();
                    }
                });
            }

            ed.onPreProcess.add(function(ed, args) {
                var nodes, i, node, dom = ed.dom, value;

                nodes = dom.select('table', args.node);
                i = nodes.length;
                while (i--) {
                    node = nodes[i];
                    dom.setAttrib(node, 'data-mce-style', '');

                    if ((value = dom.getAttrib(node, 'width'))) {
                        dom.setStyle(node, 'width', value);
                        dom.setAttrib(node, 'width', '');
                    }

                    if ((value = dom.getAttrib(node, 'height'))) {
                        dom.setStyle(node, 'height', value);
                        dom.setAttrib(node, 'height', '');
                    }
                }
            });

            // Handle node change updates
            ed.onNodeChange.add(function(ed, cm, n) {
                var p;

                n = ed.selection.getStart();
                p = ed.dom.getParent(n, 'td,th,caption');
                cm.setActive('table', n.nodeName === 'TABLE' || !!p);

                // Disable table tools if we are in caption
                if (p && p.nodeName === 'CAPTION')
                    p = 0;

                cm.setDisabled('delete_table', !p);
                cm.setDisabled('delete_col', !p);
                cm.setDisabled('delete_table', !p);
                cm.setDisabled('delete_row', !p);
                cm.setDisabled('col_after', !p);
                cm.setDisabled('col_before', !p);
                cm.setDisabled('row_after', !p);
                cm.setDisabled('row_before', !p);
                cm.setDisabled('row_props', !p);
                cm.setDisabled('cell_props', !p);
                cm.setDisabled('split_cells', !p);
                cm.setDisabled('merge_cells', !p);
            });

            ed.onInit.add(function(ed) {
                var startTable, startCell, dom = ed.dom, tableGrid;

                winMan = ed.windowManager;

                // Add cell selection logic
                ed.onMouseDown.add(function(ed, e) {
                    if (e.button != 2) {
                        cleanup();

                        startCell = dom.getParent(e.target, 'td,th');
                        startTable = dom.getParent(startCell, 'table');
                    }
                });

                dom.bind(ed.getDoc(), 'mouseover', function(e) {
                    var sel, table, target = e.target;

                    if (startCell && (tableGrid || target != startCell) && (target.nodeName == 'TD' || target.nodeName == 'TH')) {
                        table = dom.getParent(target, 'table');
                        if (table == startTable) {
                            if (!tableGrid) {
                                tableGrid = createTableGrid(table);
                                tableGrid.setStartCell(startCell);

                                ed.getBody().style.webkitUserSelect = 'none';
                            }

                            tableGrid.setEndCell(target);
                            hasCellSelection = true;
                        }

                        // Remove current selection
                        sel = ed.selection.getSel();

                        try {
                            if (sel.removeAllRanges)
                                sel.removeAllRanges();
                            else
                                sel.empty();
                        } catch (ex) {
                            // IE9 might throw errors here
                        }

                        e.preventDefault();
                    }
                });

                ed.onMouseUp.add(function(ed, e) {
                    var rng, sel = ed.selection, selectedCells, nativeSel = sel.getSel(), walker, node, lastNode, endNode;

                    // Move selection to startCell
                    if (startCell) {
                        if (tableGrid)
                            ed.getBody().style.webkitUserSelect = '';

                        function setPoint(node, start) {
                            var walker = new tinymce.dom.TreeWalker(node, node);

                            do {
                                // Text node
                                if (node.nodeType == 3 && tinymce.trim(node.nodeValue).length != 0) {
                                    if (start)
                                        rng.setStart(node, 0);
                                    else
                                        rng.setEnd(node, node.nodeValue.length);

                                    return;
                                }

                                // BR element
                                if (node.nodeName == 'BR') {
                                    if (start)
                                        rng.setStartBefore(node);
                                    else
                                        rng.setEndBefore(node);

                                    return;
                                }
                            } while (node = (start ? walker.next() : walker.prev()));
                        }

                        // Try to expand text selection as much as we can only Gecko supports cell selection
                        selectedCells = dom.select('td.mceSelected,th.mceSelected');
                        if (selectedCells.length > 0) {
                            rng = dom.createRng();
                            node = selectedCells[0];
                            endNode = selectedCells[selectedCells.length - 1];
                            rng.setStartBefore(node);
                            rng.setEndAfter(node);

                            setPoint(node, 1);
                            walker = new tinymce.dom.TreeWalker(node, dom.getParent(selectedCells[0], 'table'));

                            do {
                                if (node.nodeName == 'TD' || node.nodeName == 'TH') {
                                    if (!dom.hasClass(node, 'mceSelected'))
                                        break;

                                    lastNode = node;
                                }
                            } while (node = walker.next());

                            setPoint(lastNode);

                            sel.setRng(rng);
                        }

                        ed.nodeChanged();
                        startCell = tableGrid = startTable = null;
                    }
                });

                ed.onKeyUp.add(function(ed, e) {
                    cleanup();
                });

                ed.onKeyDown.add(function(ed, e) {
                    fixTableCellSelection(ed);
                });

                ed.onMouseDown.add(function(ed, e) {
                    if (e.button != 2) {
                        fixTableCellSelection(ed);
                    }
                });
                function tableCellSelected(ed, rng, n, currentCell) {
                    // The decision of when a table cell is selected is somewhat involved.  The fact that this code is
                    // required is actually a pointer to the root cause of this bug. A cell is selected when the start 
                    // and end offsets are 0, the start container is a text, and the selection node is either a TR (most cases)
                    // or the parent of the table (in the case of the selection containing the last cell of a table).
                    var TEXT_NODE = 3, table = ed.dom.getParent(rng.startContainer, 'TABLE'),
                            tableParent, allOfCellSelected, tableCellSelection;
                    if (table)
                        tableParent = table.parentNode;
                    allOfCellSelected = rng.startContainer.nodeType == TEXT_NODE &&
                            rng.startOffset == 0 &&
                            rng.endOffset == 0 &&
                            currentCell &&
                            (n.nodeName == "TR" || n == tableParent);
                    tableCellSelection = (n.nodeName == "TD" || n.nodeName == "TH") && !currentCell;
                    return  allOfCellSelected || tableCellSelection;
                    // return false;
                }

                // this nasty hack is here to work around some WebKit selection bugs.
                function fixTableCellSelection(ed) {
                    if (!tinymce.isWebKit)
                        return;

                    var rng = ed.selection.getRng();
                    var n = ed.selection.getNode();
                    var currentCell = ed.dom.getParent(rng.startContainer, 'TD,TH');

                    if (!tableCellSelected(ed, rng, n, currentCell))
                        return;
                    if (!currentCell) {
                        currentCell = n;
                    }

                    // Get the very last node inside the table cell
                    var end = currentCell.lastChild;
                    while (end.lastChild)
                        end = end.lastChild;

                    // Select the entire table cell. Nothing outside of the table cell should be selected.
                    rng.setEnd(end, end.nodeValue.length);
                    ed.selection.setRng(rng);
                }
                ed.plugins.table.fixTableCellSelection = fixTableCellSelection;

                // Add context menu
                if (ed && ed.plugins.contextmenu) {
                    ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                        var sm, se = ed.selection, el = se.getNode() || ed.getBody();

                        if (ed.dom.getParent(e, 'td') || ed.dom.getParent(e, 'th') || ed.dom.select('td.mceSelected,th.mceSelected').length) {
                            m.removeAll();

                            /*if (el.nodeName == 'A' && !ed.dom.getAttrib(el, 'name')) {
                                m.add({title: 'advanced.link_desc', icon: 'link', cmd: ed.plugins.advlink ? 'mceAdvLink' : 'mceLink', ui: true});
                                m.add({title: 'advanced.unlink_desc', icon: 'unlink', cmd: 'UnLink'});
                                m.addSeparator();
                            }

                            if (el.nodeName == 'IMG' && el.className.indexOf('mceItem') == -1) {
                                m.add({title: 'advanced.image_desc', icon: 'image', cmd: ed.plugins.advimage ? 'mceAdvImage' : 'mceImage', ui: true});
                                m.addSeparator();
                            }*/

                            m.add({title: 'table.desc', icon: 'table', cmd: 'mceInsertTable', value: {action: 'insert'}});
                            m.add({title: 'table.props_desc', icon: 'table_props', cmd: 'mceInsertTable'});
                            m.add({title: 'table.del', icon: 'delete_table', cmd: 'mceTableDelete'});
                            m.addSeparator();

                            // Cell menu
                            sm = m.addMenu({title: 'table.cell'});
                            sm.add({title: 'table.cell_desc', icon: 'cell_props', cmd: 'mceTableCellProps'});
                            sm.add({title: 'table.split_cells_desc', icon: 'split_cells', cmd: 'mceTableSplitCells'});
                            sm.add({title: 'table.merge_cells_desc', icon: 'merge_cells', cmd: 'mceTableMergeCells'});

                            // Row menu
                            sm = m.addMenu({title: 'table.row'});
                            sm.add({title: 'table.row_desc', icon: 'row_props', cmd: 'mceTableRowProps'});
                            sm.add({title: 'table.row_before_desc', icon: 'row_before', cmd: 'mceTableInsertRowBefore'});
                            sm.add({title: 'table.row_after_desc', icon: 'row_after', cmd: 'mceTableInsertRowAfter'});
                            sm.add({title: 'table.delete_row_desc', icon: 'delete_row', cmd: 'mceTableDeleteRow'});
                            sm.addSeparator();
                            sm.add({title: 'table.cut_row_desc', icon: 'cut', cmd: 'mceTableCutRow'});
                            sm.add({title: 'table.copy_row_desc', icon: 'copy', cmd: 'mceTableCopyRow'});
                            sm.add({title: 'table.paste_row_before_desc', icon: 'paste', cmd: 'mceTablePasteRowBefore'}).setDisabled(!clipboardRows);
                            sm.add({title: 'table.paste_row_after_desc', icon: 'paste', cmd: 'mceTablePasteRowAfter'}).setDisabled(!clipboardRows);

                            // Column menu
                            sm = m.addMenu({title: 'table.col'});
                            sm.add({title: 'table.col_before_desc', icon: 'col_before', cmd: 'mceTableInsertColBefore'});
                            sm.add({title: 'table.col_after_desc', icon: 'col_after', cmd: 'mceTableInsertColAfter'});
                            sm.add({title: 'table.delete_col_desc', icon: 'delete_col', cmd: 'mceTableDeleteCol'});
                        } else
                            m.add({title: 'table.desc', icon: 'table', cmd: 'mceInsertTable'});
                    });
                }

                // Fix to allow navigating up and down in a table in WebKit browsers.
                if (tinymce.isWebKit) {
                    function moveSelection(ed, e) {
                        var VK = tinymce.VK;
                        var key = e.keyCode;

                        function handle(upBool, sourceNode, event) {
                            var siblingDirection = upBool ? 'previousSibling' : 'nextSibling';
                            var currentRow = ed.dom.getParent(sourceNode, 'tr');
                            var siblingRow = currentRow[siblingDirection];

                            if (siblingRow) {
                                moveCursorToRow(ed, sourceNode, siblingRow, upBool);
                                tinymce.dom.Event.cancel(event);
                                return true;
                            } else {
                                var tableNode = ed.dom.getParent(currentRow, 'table');
                                var middleNode = currentRow.parentNode;
                                var parentNodeName = middleNode.nodeName.toLowerCase();
                                if (parentNodeName === 'tbody' || parentNodeName === (upBool ? 'tfoot' : 'thead')) {
                                    var targetParent = getTargetParent(upBool, tableNode, middleNode, 'tbody');
                                    if (targetParent !== null) {
                                        return moveToRowInTarget(upBool, targetParent, sourceNode, event);
                                    }
                                }
                                return escapeTable(upBool, currentRow, siblingDirection, tableNode, event);
                            }
                        }

                        function getTargetParent(upBool, topNode, secondNode, nodeName) {
                            var tbodies = ed.dom.select('>' + nodeName, topNode);
                            var position = tbodies.indexOf(secondNode);
                            if (upBool && position === 0 || !upBool && position === tbodies.length - 1) {
                                return getFirstHeadOrFoot(upBool, topNode);
                            } else if (position === -1) {
                                var topOrBottom = secondNode.tagName.toLowerCase() === 'thead' ? 0 : tbodies.length - 1;
                                return tbodies[topOrBottom];
                            } else {
                                return tbodies[position + (upBool ? -1 : 1)];
                            }
                        }

                        function getFirstHeadOrFoot(upBool, parent) {
                            var tagName = upBool ? 'thead' : 'tfoot';
                            var headOrFoot = ed.dom.select('>' + tagName, parent);
                            return headOrFoot.length !== 0 ? headOrFoot[0] : null;
                        }

                        function moveToRowInTarget(upBool, targetParent, sourceNode, event) {
                            var targetRow = getChildForDirection(targetParent, upBool);
                            targetRow && moveCursorToRow(ed, sourceNode, targetRow, upBool);
                            tinymce.dom.Event.cancel(event);
                            return true;
                        }

                        function escapeTable(upBool, currentRow, siblingDirection, table, event) {
                            var tableSibling = table[siblingDirection];
                            if (tableSibling) {
                                moveCursorToStartOfElement(tableSibling);
                                return true;
                            } else {
                                var parentCell = ed.dom.getParent(table, 'td,th');
                                if (parentCell) {
                                    return handle(upBool, parentCell, event);
                                } else {
                                    var backUpSibling = getChildForDirection(currentRow, !upBool);
                                    moveCursorToStartOfElement(backUpSibling);
                                    return tinymce.dom.Event.cancel(event);
                                }
                            }
                        }

                        function getChildForDirection(parent, up) {
                            var child = parent && parent[up ? 'lastChild' : 'firstChild'];
                            // BR is not a valid table child to return in this case we return the table cell
                            return child && child.nodeName === 'BR' ? ed.dom.getParent(child, 'td,th') : child;
                        }

                        function moveCursorToStartOfElement(n) {
                            ed.selection.setCursorLocation(n, 0);
                        }

                        function isVerticalMovement() {
                            return key == VK.UP || key == VK.DOWN;
                        }

                        function isInTable(ed) {
                            var node = ed.selection.getNode();
                            var currentRow = ed.dom.getParent(node, 'tr');
                            return currentRow !== null;
                        }

                        function columnIndex(column) {
                            var colIndex = 0;
                            var c = column;
                            while (c.previousSibling) {
                                c = c.previousSibling;
                                colIndex = colIndex + getSpanVal(c, "colspan");
                            }
                            return colIndex;
                        }

                        function findColumn(rowElement, columnIndex) {
                            var c = 0;
                            var r = 0;
                            each(rowElement.children, function(cell, i) {
                                c = c + getSpanVal(cell, "colspan");
                                r = i;
                                if (c > columnIndex)
                                    return false;
                            });
                            return r;
                        }

                        function moveCursorToRow(ed, node, row, upBool) {
                            var srcColumnIndex = columnIndex(ed.dom.getParent(node, 'td,th'));
                            var tgtColumnIndex = findColumn(row, srcColumnIndex);
                            var tgtNode = row.childNodes[tgtColumnIndex];
                            var rowCellTarget = getChildForDirection(tgtNode, upBool);
                            moveCursorToStartOfElement(rowCellTarget || tgtNode);
                        }

                        function shouldFixCaret(preBrowserNode) {
                            var newNode = ed.selection.getNode();
                            var newParent = ed.dom.getParent(newNode, 'td,th');
                            var oldParent = ed.dom.getParent(preBrowserNode, 'td,th');
                            return newParent && newParent !== oldParent && checkSameParentTable(newParent, oldParent)
                        }

                        function checkSameParentTable(nodeOne, NodeTwo) {
                            return ed.dom.getParent(nodeOne, 'TABLE') === ed.dom.getParent(NodeTwo, 'TABLE');
                        }

                        if (isVerticalMovement() && isInTable(ed)) {
                            var preBrowserNode = ed.selection.getNode();
                            setTimeout(function() {
                                if (shouldFixCaret(preBrowserNode)) {
                                    handle(!e.shiftKey && key === VK.UP, preBrowserNode, e);
                                }
                            }, 0);
                        }
                    }

                    ed.onKeyDown.add(moveSelection);
                }

                // Fixes an issue on Gecko where it's impossible to place the caret behind a table
                // This fix will force a paragraph element after the table but only when the forced_root_block setting is enabled
                function fixTableCaretPos() {
                    var last;

                    // Skip empty text nodes form the end
                    for (last = ed.getBody().lastChild; last && last.nodeType == 3 && !last.nodeValue.length; last = last.previousSibling)
                        ;

                    if (last && last.nodeName == 'TABLE') {
                        if (ed.settings.forced_root_block)
                            ed.dom.add(ed.getBody(), ed.settings.forced_root_block, null, tinymce.isIE ? '&nbsp;' : '<br data-mce-bogus="1" />');
                        else
                            ed.dom.add(ed.getBody(), 'br', {'data-mce-bogus': '1'});
                    }
                }

                // Fixes an bug where it's impossible to place the caret before a table in Gecko
                // this fix solves it by detecting when the caret is at the beginning of such a table
                // and then manually moves the caret infront of the table
                if (tinymce.isGecko) {
                    ed.onKeyDown.add(function(ed, e) {
                        var rng, table, dom = ed.dom;

                        // On gecko it's not possible to place the caret before a table
                        if (e.keyCode == 37 || e.keyCode == 38) {
                            rng = ed.selection.getRng();
                            table = dom.getParent(rng.startContainer, 'table');

                            if (table && ed.getBody().firstChild == table) {
                                if (isAtStart(rng, table)) {
                                    rng = dom.createRng();

                                    rng.setStartBefore(table);
                                    rng.setEndBefore(table);

                                    ed.selection.setRng(rng);

                                    e.preventDefault();
                                }
                            }
                        }
                    });
                }

                ed.onKeyUp.add(fixTableCaretPos);
                ed.onSetContent.add(fixTableCaretPos);
                ed.onVisualAid.add(fixTableCaretPos);

                ed.onPreProcess.add(function(ed, o) {
                    var last = o.node.lastChild;

                    if (last && (last.nodeName == "BR" || (last.childNodes.length == 1 && (last.firstChild.nodeName == 'BR' || last.firstChild.nodeValue == '\u00a0'))) && last.previousSibling && last.previousSibling.nodeName == "TABLE") {
                        ed.dom.remove(last);
                    }
                });


                /**
                 * Fixes bug in Gecko where shift-enter in table cell does not place caret on new line
                 *
                 * Removed: Since the new enter logic seems to fix this one.
                 */
                /*
                 if (tinymce.isGecko) {
                 ed.onKeyDown.add(function(ed, e) {
                 if (e.keyCode === tinymce.VK.ENTER && e.shiftKey) {
                 var node = ed.selection.getRng().startContainer;
                 var tableCell = dom.getParent(node, 'td,th');
                 if (tableCell) {
                 var zeroSizedNbsp = ed.getDoc().createTextNode("\uFEFF");
                 dom.insertAfter(zeroSizedNbsp, node);
                 }
                 }
                 });
                 }
                 */

                fixTableCaretPos();
                ed.startContent = ed.getContent({format: 'raw'});
            });

            // Register action commands
            each({
                mceTableSplitCells: function(grid) {
                    grid.split();
                },
                mceTableMergeCells: function(grid) {
                    var rowSpan, colSpan, cell;

                    cell = ed.dom.getParent(ed.selection.getNode(), 'th,td');
                    if (cell) {
                        rowSpan = cell.rowSpan;
                        colSpan = cell.colSpan;
                    }

                    if (!ed.dom.select('td.mceSelected,th.mceSelected').length) {
                        winMan.open({
                            url: ed.getParam('site_url') + 'index.php?option=com_jce&view=editor&layout=plugin&plugin=table&context=merge',
                            width: 240 + parseInt(ed.getLang('table.merge_cells_delta_width', 0)),
                            height: 170 + parseInt(ed.getLang('table.merge_cells_delta_height', 0)),
                            inline: 1
                        }, {
                            rows: rowSpan,
                            cols: colSpan,
                            onaction: function(data) {
                                grid.merge(cell, data.cols, data.rows);
                            },
                            plugin_url: url,
                            context : "merge"
                        });
                    } else {
                        grid.merge();
                    }
                },
                mceTableInsertRowBefore: function(grid) {
                    grid.insertRow(true);
                },
                mceTableInsertRowAfter: function(grid) {
                    grid.insertRow();
                },
                mceTableInsertColBefore: function(grid) {
                    grid.insertCol(true);
                },
                mceTableInsertColAfter: function(grid) {
                    grid.insertCol();
                },
                mceTableDeleteCol: function(grid) {
                    grid.deleteCols();
                },
                mceTableDeleteRow: function(grid) {
                    grid.deleteRows();
                },
                mceTableCutRow: function(grid) {
                    clipboardRows = grid.cutRows();
                },
                mceTableCopyRow: function(grid) {
                    clipboardRows = grid.copyRows();
                },
                mceTablePasteRowBefore: function(grid) {
                    grid.pasteRows(clipboardRows, true);
                },
                mceTablePasteRowAfter: function(grid) {
                    grid.pasteRows(clipboardRows);
                },
                mceTableDelete: function(grid) {
                    grid.deleteTable();
                }
            }, function(func, name) {
                ed.addCommand(name, function() {
                    var grid = createTableGrid();

                    if (grid) {
                        func(grid);
                        ed.execCommand('mceRepaint');
                        cleanup();
                    }
                });
            });

            // Register dialog commands
            each({
                mceInsertTable: function(val) {
                    winMan.open({
                        url: ed.getParam('site_url') + 'index.php?option=com_jce&view=editor&layout=plugin&plugin=table',
                        width: 420 + parseInt(ed.getLang('table.table_delta_width', 0)),
                        height: 440 + parseInt(ed.getLang('table.table_delta_height', 0)),
                        inline: 1,
                        popup_css: false
                    }, {
                        plugin_url: url,
                        action: val ? val.action : 0,
                        context : "table"
                    });
                },
                mceTableRowProps: function() {
                    winMan.open({
                        url: ed.getParam('site_url') + 'index.php?option=com_jce&view=editor&layout=plugin&plugin=table&context=row',
                        width: 440 + parseInt(ed.getLang('table.rowprops_delta_width', 0)),
                        height: 440 + parseInt(ed.getLang('table.rowprops_delta_height', 0)),
                        inline: 1,
                        popup_css: false
                    }, {
                        plugin_url: url,
                        context : "row"
                    });
                },
                mceTableCellProps: function() {
                    winMan.open({
                        url: ed.getParam('site_url') + 'index.php?option=com_jce&view=editor&layout=plugin&plugin=table&context=cell',
                        width: 420 + parseInt(ed.getLang('table.cellprops_delta_width', 0)),
                        height: 440 + parseInt(ed.getLang('table.cellprops_delta_height', 0)),
                        inline: 1,
                        popup_css: false
                    }, {
                        plugin_url: url,
                        context : "cell"
                    });
                }

            }, function(func, name) {
                ed.addCommand(name, function(ui, val) {
                    func(val);
                });

            });
        },
        createControl: function(n, cm) {
            var t = this, ed = t.editor;

            switch (n) {
                case 'table_insert':
                    var border = ed.getParam('table_default_border', '');

                    // any value border will always be 1 in html5
                    if (ed.settings.schema == 'html5' && ed.settings.validate) {
                        if (border) {
                            border = 1;
                        }
                    }

                    var c = new tinymce.ui.TableSplitButton(cm.prefix + 'table_insert', {
                        title: ed.getLang('table.desc', 'Inserts a new table'),
                        'class': 'mce_table_insert',
                        'menu_class': ed.getParam('skin') + 'Skin',
                        onclick: function(e) {
                            ed.execCommand('mceInsertTable');
                        },
                        onselect: function(html) {
                            ed.execCommand('mceInsertContent', false, html);
                        },
                        scope: ed,
                        width: ed.getParam('table_default_width'),
                        height: ed.getParam('table_default_height'),
                        border: border,
                        classes: ed.getParam('table_classes', '')
                    }, ed);

                    ed.onMouseDown.add(c.hideMenu, c);

                    // Remove the menu element when the editor is removed
                    ed.onRemove.add(function() {
                        c.destroy();
                    });

                    return cm.add(c);
                    break;
            }

            return null;
        }
    });

    // Register plugin
    tinymce.PluginManager.add('table', tinymce.plugins.TablePlugin);
})(tinymce);

/* Table Grid SplitButton Control */
(function(tinymce) {
    var DOM = tinymce.DOM, Event = tinymce.dom.Event, is = tinymce.is, each = tinymce.each;
    /**
     * This class is used to create UI table split button. A table split button will show a table grid
     * when you press the open menu.
     *
     * @class tinymce.ui.TableSplitButton
     * @extends tinymce.ui.SplitButton
     */
    tinymce.create('tinymce.ui.TableSplitButton:tinymce.ui.SplitButton', {
        /**
         * Constructs a new color split button control instance.
         *
         * @constructor
         * @method ColorSplitButton
         * @param {String} id Control id for the color split button.
         * @param {Object} s Optional name/value settings object.
         * @param {Editor} ed The editor instance this button is for.
         */
        TableSplitButton: function(id, s, ed) {
            var t = this;

            this.editor = ed;

            t.parent(id, s, ed);

            /**
             * Settings object.
             *
             * @property settings
             * @type Object
             */
            t.settings = s = tinymce.extend({
                cols: 4,
                rows: 4,
                width: '',
                height: '',
                border: 0
            }, t.settings);

            /**
             * Fires when the menu is shown.
             *
             * @event onShowMenu
             */
            t.onShowMenu = new tinymce.util.Dispatcher(t);

            /**
             * Fires when the menu is hidden.
             *
             * @event onHideMenu
             */
            t.onHideMenu = new tinymce.util.Dispatcher(t);
        },
        /**
         * Shows the color menu. The color menu is a layer places under the button
         * and displays a table of colors for the user to pick from.
         *
         * @method showMenu
         */
        showMenu: function() {
            var t = this, r, p, e, p2, ed = this.editor;

            if (t.isDisabled())
                return;

            // Store bookmark
            if (tinymce.isIE) {
                ed.focus();
                this.bookmark = ed.selection.getBookmark(1);
            }

            if (!t.isMenuRendered) {
                t.renderMenu();
                t.isMenuRendered = true;
            }

            if (t.isMenuVisible)
                return t.hideMenu();

            e = DOM.get(t.id);
            DOM.show(t.id + '_menu');
            DOM.addClass(e, 'mceSplitButtonSelected');
            p2 = DOM.getPos(e);
            DOM.setStyles(t.id + '_menu', {
                left: p2.x,
                top: p2.y + e.clientHeight,
                zIndex: 200000
            });
            e = 0;

            Event.add(DOM.doc, 'mousedown', t.hideMenu, t);
            t.onShowMenu.dispatch(t);

            if (t._focused) {
                t._keyHandler = Event.add(t.id + '_menu', 'keydown', function(e) {
                    if (e.keyCode == 27)
                        t.hideMenu();
                });
            }

            t.isMenuVisible = 1;

            t.setActive(1);
        },
        /**
         * Hides the color menu. The optional event parameter is used to check where the event occured so it
         * doesn't close them menu if it was a event inside the menu.
         *
         * @method hideMenu
         * @param {Event} e Optional event object.
         */
        hideMenu: function(e) {
            var t = this;

            if (t.isMenuVisible) {
                // Prevent double toogles by canceling the mouse click event to the button
                if (e && e.type == "mousedown" && DOM.getParent(e.target, function(e) {
                    return e.id === t.id + '_open';
                }))
                    return;

                if (!e || !DOM.getParent(e.target, '.mceSplitButtonMenu')) {
                    Event.remove(t.id, 'mouseover');
                    DOM.removeClass(DOM.select('table td.selected', t.id + '_menu'), 'selected');
                    DOM.setHTML(DOM.select('table td.mceTableGridCount', t.id + '_menu'), '&nbsp;');

                    DOM.removeClass(t.id, 'mceSplitButtonSelected');
                    Event.remove(DOM.doc, 'mousedown', t.hideMenu, t);
                    Event.remove(t.id + '_menu', 'keydown', t._keyHandler);
                    DOM.hide(t.id + '_menu');
                }

                t.isMenuVisible = 0;

                t.setActive(0);
            }
        },
        /**
         * Renders the menu to the DOM.
         *
         * @method renderMenu
         */
        renderMenu: function() {
            var t = this, ed = this.editor, m, i = 0, s = t.settings, n, tb, tr, w, context, bm;

            w = DOM.add(s.menu_container, 'div', {
                role: 'listbox',
                id: t.id + '_menu',
                'class': s['menu_class'] + ' ' + s['class'],
                style: 'position:absolute;left:0;top:-1000px;'
            });
            m = DOM.add(w, 'div', {
                'class': s['class'] + ' mceSplitButtonMenu'
            });
            DOM.add(m, 'span', {
                'class': 'mceMenuLine'
            });

            n = DOM.add(m, 'table', {
                role: 'presentation',
                'class': 'mceTableSplitMenu',
                'border': 1
            });
            tb = DOM.add(n, 'tbody');

            for (i = 0; i < s.rows; i++) {
                tr = DOM.add(tb, 'tr');

                for (x = 0; x < s.cols; x++) {
                    td = DOM.create('td', {}, '&nbsp;');
                    DOM.add(tr, td);
                }
            }

            var rows = tb.childNodes;

            Event.add(n, 'mouseover', function(e) {
                var el = e.target;

                if (el.nodeName == 'TD') {
                    var row = el.parentNode, i, z;
                    var x = tinymce.inArray(row.childNodes, el), y = tinymce.inArray(rows, row);

                    if (x < 0 || y < 0) {
                        return;
                    }

                    for (i = 0; i < rows.length; i++) {
                        cells = rows[i].childNodes;

                        for (z = 0; z < cells.length; z++) {
                            if (z > x || i > y) {
                                DOM.removeClass(cells[z], 'selected');
                            } else {
                                DOM.addClass(cells[z], 'selected');
                            }
                        }
                    }

                    DOM.setHTML(DOM.select('td.mceTableGridCount', n), (y + 1) + ' x ' + (x + 1));
                }
            });

            tf = DOM.add(n, 'tfoot');
            DOM.add(DOM.add(tf, 'tr'), 'td', {
                colspan: s.rows,
                'class': 'mceTableGridCount'
            }, '&nbsp;');

            DOM.addClass(m, 'mceTableSplitMenu');

            new tinymce.ui.KeyboardNavigation({
                root: t.id + '_menu',
                items: DOM.select('a', t.id + '_menu'),
                onCancel: function() {
                    t.hideMenu();
                    t.focus();
                }
            });

            // Prevent IE from scrolling and hindering click to occur #4019
            Event.add(t.id + '_menu', 'mousedown', function(e) {
                return Event.cancel(e);
            });

            Event.add(t.id + '_menu', 'click', function(e) {
                var c, el = e.target;

                if (el.nodeName.toLowerCase() == 'td') {
                    var table = DOM.getParent(el, 'table');

                    var styles = [];

                    var width = t.settings.width;

                    if (/^[0-9\.]+$/.test(width)) {
                        width += 'px';
                    }
                    // add width
                    if (width) {
                        styles.push('width:' + width);
                    }

                    var height = t.settings.height;

                    if (/^[0-9\.]+$/.test(height)) {
                        height += 'px';
                    }
                    // add height
                    if (height) {
                        styles.push('height:' + height);
                    }

                    var html = '<table';

                    if (t.settings.border != '') {
                        html += ' border="' + t.settings.border + '"';
                    }

                    if (t.settings.classes) {
                        html += ' class="' + t.settings.classes + '"';
                    }

                    if (styles.length) {
                        html += ' style="' + styles.join(';') + ';"';
                    }
                    html += '>';

                    var rows = tinymce.grep(DOM.select('tr', table), function(row) {
                        return DOM.select('td.selected', row).length;
                    });

                    for (var y = 0; y < rows.length; y++) {
                        html += "<tr>";

                        var cols = DOM.select('td.selected', rows[y]).length;

                        for (var x = 0; x < cols; x++) {
                            if (!tinymce.isIE)
                                html += '<td><br data-mce-bogus="1"/></td>';
                            else
                                html += '<td></td>';
                        }
                        html += "</tr>";
                    }
                    html += "</table>";

                    // restore bookmark
                    if (t.bookmark) {
                        ed.selection.moveToBookmark(t.bookmark);
                        ed.focus();
                        t.bookmark = 0;
                    }

                    t.createGrid(html);
                }

                return Event.cancel(e); // Prevent IE auto save warning
            });

            return w;
        },
        /**
         * Sets the current color for the control and hides the menu if it should be visible.
         *
         * @method setColor
         * @param {String} html Table HTML
         */
        createGrid: function(html) {
            this.hideMenu();
            this.settings.onselect(html);
        },
        /**
         * Post render event. This will be executed after the control has been rendered and can be used to
         * set states, add events to the control etc. It's recommended for subclasses of the control to call this method by using this.parent().
         *
         * @method postRender
         */
        postRender: function() {
            var t = this, id = t.id;

            t.parent();
        },
        /**
         * Destroys the control. This means it will be removed from the DOM and any
         * events tied to it will also be removed.
         *
         * @method destroy
         */
        destroy: function() {
            this.parent();

            Event.clear(this.id + '_menu');
            Event.clear(this.id + '_more');
            DOM.remove(this.id + '_menu');
        }
    });
})(tinymce); 
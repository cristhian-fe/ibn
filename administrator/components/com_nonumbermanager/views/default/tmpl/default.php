<?php
/**
 * @package         NoNumber Extension Manager
 * @version         4.2.2
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

JHtml::_('behavior.modal');
JHtml::_('behavior.tooltip');

$ids = array();
foreach ($this->items as $item) {
	$ids[] = $item->id;
}

$config = JComponentHelper::getParams('com_nonumbermanager');
$check_data = $config->get('check_data', 1);
$hide_notinstalled = $config->get('hide_notinstalled', 0);
$has_not_installed = 0;
foreach ($this->items as $item) {
	if (!$item->installed) {
		$has_not_installed = 1;
		break;
	}
}

if (version_compare(PHP_VERSION, '5.3', 'l')) {
	JFactory::getApplication()->enqueueMessage(JText::sprintf('NNEM_NOT_COMPATIBLE_PHP', PHP_VERSION, '5.3'), 'notice');
}

JHtml::stylesheet('nnframework/style.min.css', false, true);
JHtml::script('nnframework/script.min.js', false, true);

$key = trim($config->get('key'));
if ($key) {
	$key = strtolower(substr($key, 0, 8) . md5(substr($key, 8)));
}
$script = "
	var NNEM_IDS = ['" . implode("', '", $ids) . "'];
	var NNEM_NOUPDATE = '" . addslashes(JText::_('NNEM_ALERT_NO_ITEMS_TO_UPDATE')) . "';
	var NNEM_NONESELECTED = '" . addslashes(JText::_('NNEM_ALERT_NO_ITEMS_SELECTED')) . "';
	var NNEM_FAIL = '" . addslashes(JText::_('NNEM_ALERT_FAIL')) . "';
	var NNEM_CHANGELOG = '" . addslashes(JText::_('NNEM_CHANGELOG')) . "';
	var NNEM_TIMEOUT = " . (int) $config->get('timeout', 5) . ";
	var NNEM_TOKEN = '" . JSession::getFormToken() . "';
	var NNEM_KEY = '" . $key . "';
";
JFactory::getDocument()->addScriptDeclaration($script);

JHtml::stylesheet('nonumbermanager/style.min.css', false, true);
JHtml::script('nonumbermanager/script.min.js', false, true);

$onload = array();
if ($check_data) {
	$onload[] = 'nnManager.refreshData();';
}
if (!empty($onload)) {
	$script = "
		window.addEvent( 'domready', function() {
			" . implode("\n\t\t", $onload) . "
		});
	";
	JFactory::getDocument()->addScriptDeclaration($script);
}

// Version check
require_once JPATH_PLUGINS . '/system/nnframework/helpers/versions.php';
if ($config->get('show_update_notification', 1)) {
	echo NNVersions::getInstance()->getMessage('nonumbermanager', '', '', 'component');
}

?>
	<a id="nnem_modal" href=""></a>
	<div id="nnem">
		<form action="" name="adminForm" id="adminForm">
			<div class="nnem_main_buttons">
				<?php
				echo makeElement('', 'data', 'refresh', JText::_('NNEM_CHECK_DATA'), JText::_('NNEM_REFRESH_DESC'), 0, $check_data);
				echo makeElement('', 'refresh', 'refresh', JText::_('NNEM_REFRESH'), JText::_('NNEM_REFRESH_DESC'), 0, !$check_data);
				if ($has_not_installed) {
					echo makeElement('hide', '', 'toggle', JText::_('NNEM_HIDE_NOTINSTALLED'), 0, 0, $hide_notinstalled);
					echo makeElement('show', '', 'toggle', JText::_('NNEM_SHOW_NOTINSTALLED'), 0, 0, !$hide_notinstalled);
				}
				echo makeElement('', 'all_ghosted', 'installselected', 0, JText::_('NNEM_INSTALL_SELECTED'), 'none');
				echo makeElement('', 'all', 'installselected', JText::_('NNEM_INSTALL_SELECTED'), JText::_('NNEM_INSTALL_SELECTED_DESC'), 0, 1);
				echo makeElement('', 'all_ghosted', 'updateall', 0, JText::_('NNEM_UPDATE_ALL'), 'none');
				echo makeElement('', 'all', 'updateall', JText::_('NNEM_UPDATE_ALL'), JText::_('NNEM_UPDATE_ALL_DESC'), 0, 1);
				?>
			</div>
			<table class="adminlist<?php echo $hide_notinstalled ? ' hide_not_installed' : ''; ?>">
				<thead>
					<tr>
						<th width="1%">
							<input type="checkbox" name="checkall-toggle" value="" onclick="Joomla.checkAll(this)" />
						</th>
						<th width="5%" class="left" nowrap="nowrap">
							<?php echo JText::_('NNEM_EXTENSION'); ?>
						</th>
						<th width="1%">&nbsp;</th>
						<th width="2%" class="left" nowrap="nowrap">
							<?php echo JText::_('NNEM_TYPE'); ?>
						</th>
						<th width="5%" class="left" nowrap="nowrap">
							<?php echo JText::_('NNEM_INSTALLED'); ?>
						</th>
						<th width="5%" class="left" nowrap="nowrap">
							<?php echo JText::_('NNEM_AVAILABLE'); ?>
						</th>
						<th width="1%">&nbsp;</th>
						<th width="5%" class="left buttons" nowrap="nowrap">
							<?php echo JText::_('NNEM_INSTALL_UPDATE'); ?>
						</th>
						<th width="1%">&nbsp;</th>
						<th class="left" nowrap="nowrap">
							<?php echo JText::_('NNEM_COMMENT'); ?>
						</th>
						<th width="20" class="left">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<tr id="row_<?php echo $item->id; ?>" class="<?php
						if ($item->installed) {
							echo 'installed'
								. ($item->old ? ' old' : ($item->pro ? ' pro_installed' : ' free_installed'))
								. (empty($item->missing) ? '' : ' has_missing');
						} else {
							echo 'not_installed';
						}
						?>">
							<td class="center checkbox">
								<div class="checkbox_container">
									<span class="group_data select" style="display:none;">
										<?php echo JHtml::_('grid.id', $i, $item->id); ?>
									</span>
								</div>
							</td>
							<td nowrap="nowrap" class="ext_name">
								<input type="hidden" id="url_<?php echo $item->id; ?>" value="" />
								<span class="hasTip"
									title="<?php echo JText::_('JGLOBAL_DESCRIPTION') . '::' . JText::_($item->name . '_DESC'); ?>">
									<span class="icon-nonumber icon-<?php echo $item->alias; ?>"></span>
									<?php echo JText::_($item->name); ?>
								</span>
							</td>
							<td class="ext_website">
								<a href="http://www.nonumber.nl/<?php echo $item->id; ?>" target="_blank">
									<span class="hasTip"
										title="<?php echo JText::_('NNEM_WEBSITE'); ?>::http://www.nonumber.nl/<?php echo $item->alias; ?>">
										<img src="<?php echo JURI::root(); ?>media/nonumbermanager/images/link.png"
											alt="" width="16" height="16" />
									</span>
								</a>
							</td>
							<td nowrap="nowrap" class="ext_types">
								<span class="not_installed">
									<?php
									foreach ($item->types as $type) {
										$tip = JText::_('NN_' . strtoupper($type->type)) . ':: ';
										$icon = '<img src="' . JURI::root() . 'media/nonumbermanager/images/ext_' . $type->type . '.png" alt="' . $tip . '" style="margin:0 1px;" width="16" height="16" />';
										echo '<label class="hasTip" title="' . $tip . '">' . $icon . '</label>';
									}
									?>
								</span>
								<span class="installed">
									<?php
									foreach ($item->types as $type) {
										$tip = JText::_('NN_' . strtoupper($type->type)) . '::' . JText::_('NNEM_GO_TO_EXTENSION');
										$icon = '<img src="' . JURI::root() . 'media/nonumbermanager/images/ext_' . $type->type . '.png" alt="' . $tip . '" style="margin:0 1px;" width="16" height="16" />';
										$icon = '<a href="index.php?' . $type->link . '" target="_blank">' . $icon . '</a>';
										echo '<label class="hasTip" title="' . $tip . '">' . $icon . '</label>';
									}
									?>
								</span>
							</td>
							<td nowrap="nowrap" class="">
								<?php
								echo makeElement($item->id, 'version_loading data', 'loading', 0, 0, 0, 1);
								?>
								<span class="installed">
									<?php
									echo makeElement($item->id, '', 'current_version', 0, $item->version, 0, 0);
									echo makeElement($item->id, '', 'free_installed', 0, 'FREE', 0, 1);
									echo makeElement($item->id, '', 'pro_installed', 0, 'PRO', 0, 1);
									?>
									<div class="clr"></div>
								</span>
							</td>
							<td nowrap="nowrap" class="">
								<?php
								echo makeElement('', 'data', 'refresh', JText::_('NNEM_CHECK_DATA'), JText::_('NNEM_REFRESH_DESC'), 0, $check_data);
								echo makeElement($item->id, 'data', 'loading', 0, 0, 0, 1);
								?>
								<span class="hide_uptodate">
									<?php
									echo makeElement($item->id, 'data', 'new_version', 0, '&nbsp;', 0, 1);
									echo makeElement($item->id, 'data', 'pro_no_access', 0, 'FREE', 0, 1);
									echo makeElement($item->id, 'data', 'pro_access', 0, 'PRO', 0, 1);
									?>
								</span>
							</td>
							<td class="center ext_changelog">
								<?php
								echo makeElement($item->id, 'data', 'changelog', 0, JText::_('NNEM_CHANGELOG'), 'http://www.nonumber.nl/' . $item->id . '#changelog', 1);
								?>
							</td>
							<td nowrap="nowrap" class="buttons">
								<?php
								echo makeElement($item->id, 'data', 'update', JText::_('NNEM_TITLE_UPDATE'), 0, 0, 1);
								echo makeElement($item->id, 'data', 'install', JText::_('NNEM_TITLE_INSTALL'), 0, 0, 1);
								echo makeElement($item->id, 'data', 'reinstall', JText::_('NNEM_TITLE_REINSTALL'), 0, 0, 1);
								echo makeElement($item->id, 'data', 'downgrade', JText::_('NNEM_TITLE_DOWNGRADE'), 0, 0, 1);
								?>
							</td>
							<td nowrap="nowrap" class="buttons">
								<span class="pro_not_installed"><span class="pro_available"><span class="pro_no_access">
											<?php
											echo makeElement($item->id, '', 'buy', JText::_('NNEM_BUY_PRO_VERSION'), 0, 'http://www.nonumber.nl/subscribe?ext=' . $item->id);
											?>
										</span></span></span>
								<span class="pro_installed"><span class="pro_no_access">
										<?php
										echo makeElement($item->id, '', 'nopro', JText::_('NNEM_RENEW_SUBSCRIPTION'), 0, 'http://www.nonumber.nl/subsciptions?ext=' . $item->id);
										?>
									</span>
							</td>
							<td class="comments">
								<?php
								if ($item->error) {
									echo makeElement($item->id, 'error', 'error', 0, $item->error);
								}
								echo makeElement($item->id, 'comment', 'uptodate', 0, JText::_('NNEM_COMMENT_UPTODATE'), 0, 1);
								echo makeElement($item->id, 'comment', 'update', 0, JText::_('NNEM_COMMENT_UPDATE'), 0, 1);
								echo makeElement($item->id, 'comment', 'downgrade', 0, JText::_('NNEM_COMMENT_DOWNGRADE'), 0, 1);
								echo makeElement($item->id, 'comment', 'pro_no_access', 0, JText::_('NNEM_COMMENT_NO_PRO'), 0, 1);
								echo makeElement($item->id, 'comment', 'old', 0, JText::sprintf('NNEM_COMMENT_OLD', JText::_($item->name)), 0, 1);
								$missing = '';
								if ($item->installed && !empty($item->missing)) {
									$missing = array();
									foreach ($item->missing as $m) {
										$missing[] = JText::_('NN_' . strtoupper($m));
									}
									$missing = JText::sprintf('NNEM_MISSING_EXTENSIONS', implode(',', $missing));
								}
								echo makeElement($item->id, 'comment', 'missing', 0, $missing, 0, (!$missing));
								?>
							</td>
							<td nowrap="nowrap" class="ext_uninstall buttons">
								<?php if ($item->id != 'nonumberextensionmanager') : ?>
									<span class="installed">
										<?php echo makeElement($item->id, 'none', 'uninstall', 'X', JText::_('NNEM_TITLE_UNINSTALL')); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
		</form>
	</div>
	<br />
<?php
// Copyright
echo NNVersions::getInstance()->getCopyright('NONUMBER_EXTENSION_MANAGER', '', 17071, 'nonumbermanager', 'component');

function makeElement($alias, $group, $type, $button = 0, $tip = 0, $link = 0, $hide = 0)
{
	$class = array();

	$id = '';
	if ($alias) {
		$id = $alias . '_' . $type;
	}
	if ($group != 'comment') {
		$class[] = $type;
	}
	if ($group) {
		if ($alias) {
			$id = $alias . '_' . $group . '_' . $type;
		}
		$class[] = 'group_' . $group;
		$class[] = $group . '_' . $type;
	}

	$txt = $tip;
	if ($button) {
		$txt = $button;
	} else {
		$tip = 0;
	}

	if ($tip) {
		$class[] = 'hasTip';
	} else {
		$tip = 0;
	}

	if ($group == 'comment') {
		$split = explode('<br />', $txt, 2);
		$txt = $split['0'];
		if (isset($split['1'])) {
			$txt .= ' <span class="readmore">' . JText::_('NN_MORE_INFO') . '</span><span class="readmore_text"><br />' . $split['1'] . '</span>';
		}
	}

	$img = '';
	if ($type == 'loading') {
		$txt = '<progress class="loading">' . JText::sprintf('NNEM_PROCESS_LOADING', '...') . '</progress>';
		$id = '';
	} else if ($type == 'changelog') {
		$img = 'changelog.png';
	}
	$html = array();
	$html[] = '<span' . ($id ? ' id="' . str_replace(' ', '_', $id) . '"' : '') . ' class="' . implode(' ', $class) . '"' . ($tip ? ' title="' . $tip . ':: "' : '') . ($hide ? ' style="display:none;"' : '') . '>';

	if ($link === 'none') {
		$html[] = '<a class="nolink">';
	} else if ($button && $link) {
		$html[] = '<a href="' . $link . '" target="_blank">';
	} else if ($button) {
		$html[] = '<a href="javascript://" onclick="nnem_function(\'' . $type . '\', \'' . $alias . '\');" class="nnem_link">';
	} else if ($link) {
		$html[] = '<a href="' . $link . '" target="_blank">';
	}

	if ($type == 'loading') {
		$html[] = $txt;
	} else if ($img) {
		$html[] = '<img src="' . JURI::root() . 'media/nonumbermanager/images/' . $img . '" alt="' . JText::_('NNEM_' . strtoupper($type)) . '" width="16" height="16" />';
	} else if ($txt !== 0) {
		$html[] = '<span class="nnem_button">';
		$html[] = $txt;
		$html[] = '</span>';
	}

	if ($link || $button) {
		$html[] = '</a>';
	}

	$html[] = '</span>';

	return implode('', $html);
}

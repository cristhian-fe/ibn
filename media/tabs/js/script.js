/**
 * Main JavaScript file
 *
 * @package         Tabs
 * @version         3.3.5
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

window.addEvent('domready', function()
{
	// Only do stuff if nn_tabs_nav and nn_tabs_content is found
	if (document.getElements('div.nn_tabs_nav').length && document.getElements('div.nn_tabs_content').length) {
		(function() { nnTabs = new nnTabs(); }).delay(100);
	} else {
		// Try again 2 seconds later, because IE sometimes can't see object immediately
		(function()
		{
			if (document.getElements('div.nn_tabs_nav').length && document.getElements('div.nn_tabs_content').length) {
				nnTabs = new nnTabs();
			}
		}).delay(2000);
	}
});

var nnTabs = new Class({
	initialize: function()
	{
		var self = this;
		this.docScroll = new Fx.Scroll(window);
		this.containers = [];

		var nn_tabs_hash = '';
		if (nn_tabs_use_hash && window.location.hash) {
			nn_tabs_hash = window.location.hash.replace('#', '');
		}

		document.getElements('div.nn_tabs_container').each(function($container)
		{
			if (typeof( $container ) != "undefined") {
				$container.removeClass('nn_tabs_noscript');

				var cid = $container.id.replace('nn_tabs_container_', '');
				var active = 0;

				$container.getElements('div.nn_tabs_content').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						var set_id = $el.id.replace('nn_tabs_content_', '');
						if (set_id == cid) {
							$el.fx = new Fx.Tween($el, { property: 'height', 'duration': nn_tabs_speed, onComplete: function() { self.autoHeight($el); } });
						}
					}
				});

				// add events on tabs
				var first = 1;
				var active_hash = 0;
				$container.getElements('li.nn_tabs_tab').each(function($el)
				{
					if (typeof( $el ) != "undefined" && !$el.hasClass('nn_tabs_notab')) {
						var id = $el.id.replace('nn_tabs_tab_', '');
						var set_id = id.slice(0, id.indexOf('-'));
						if (set_id == cid) {
							self.containers[id] = cid;

							// set first tab as active or active tab
							if (( first && !$el.hasClass('inactive') ) || $el.hasClass('active')) {
								active = id;
							}
							if (nn_tabs_use_hash) {
								$el.alias = $el.getFirst().className.slice(String('nn_tabs_alias_').length);
								if (!active_hash && nn_tabs_hash && $el.alias == nn_tabs_hash) {
									active_hash = id;
								}
							}
								mode = 'click';
							$el.addEvent(mode, function() { self.showTab(id, cid); });
							first = 0;
						}
					}
					$el.setStyle('display', '');
				});

				if (nn_tabs_use_hash && active_hash) {
					active = active_hash;
				}


				// add fx
				$container.getElements('div.nn_tabs_item').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						var id = $el.id.replace('nn_tabs_item_', '');
						var set_id = id.slice(0, id.indexOf('-'));
						if (set_id == cid) {
							$el.setStyle('display', 'block');
							$el.fade_in = new Fx.Tween($el, { property: 'opacity', 'duration': nn_tabs_fade_in_speed });
							$el.fx = new Fx.Slide($el, { 'duration': 0, onComplete: function() { self.autoHeight($el.getParent()); } }).hide();
						}
					}
				});

				// hide content titles
				$container.getElements('.nn_tabs_title').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						$el.setStyle('display', 'none');
					}
				});

				// show only active tab
				self.showTab(active, cid, 1, ( active === nn_tabs_urlscroll ));
				if (active == nn_tabs_url || active == active_hash) {
					self.openParents(cid);
				}

				// show tabs list
				$container.getElements('div.nn_tabs_nav').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						$el.setStyle('display', 'block');
					}
				});
			}
		});

		// add onclick events on tab links {tablink=...}
		document.getElements('a.nn_tabs_link').each(function($el)
		{
			if (typeof( $el ) != "undefined" && $el.rel && typeof( self.containers[$el.rel] ) != "undefined") {
				$el.addEvent('click', function()
				{
					self.showTab($el.rel, self.containers[$el.rel], 0, nn_tabs_linkscroll);
				});
				$el.href = 'javascript://';
			}
		});
	},

	showTab: function(id, cid, first, scroll)
	{
		var $container = document.id('nn_tabs_container_' + cid);
		var $item = document.id('nn_tabs_tab_' + id);
		var isactive = ( typeof( $item ) != "undefined" && $item && $item.hasClass('active') );
		var $content = null;

		// remove all active classes
		$container.getElements('li.nn_tabs_tab').each(function($el)
		{
			if (typeof( $el ) != "undefined" && $el && $el.hasClass('active')) {
				var el_id = $el.id.replace('nn_tabs_tab_', '');
				var set_id = el_id.slice(0, el_id.indexOf('-'));
				if (set_id == cid) {
					$el.removeClass('active');
					$content = document.id('nn_tabs_item_' + el_id);
					if ($content) {
						$content.getParent().getParent().setStyle('height', parseInt($content.getStyle('height')));
					}
				}
			}
		});

		if (typeof( $item ) != "undefined" && $item) {
			$item.addClass('active');
		}

		var $el = document.id('nn_tabs_item_' + id);

		// show active content block
		if (typeof( $el ) != "undefined" && $el && typeof( $el.fx ) != "undefined") {
			$el.removeClass('nn_tabs_item_inactive');
			$content = $el.getParent().getParent();
			$content.className = ( 'nn_tabs_content ' + ( $el.className.replace('nn_tabs_item', '') ) ).trim();
			$el.fx.cancel();

			// show active content block
			if (first) {
				$el.fx.show();
				this.autoHeight($el.getParent(), 1);
				this.autoHeight($content, 1);
			} else if (!isactive) {
				$el.fade_in.cancel();
				$el.setStyle('opacity', 0);
				$el.fx.show();
				this.autoHeight($el.getParent());
				$el.fade_in.start(1);
				$content.fx.cancel().start(parseInt($el.getStyle('height')));
			}

			if (!first) {
				$item.getElement('a').focus();
			}
		}

		// hide all content block
		$container.getElements('div.nn_tabs_item').each(function($el)
		{
			if (id && typeof( $el ) != "undefined" && $el && $el.id && $el.id != 'nn_tabs_item_' + id && typeof( $el.fx ) != "undefined") {
				var el_id = $el.id.replace('nn_tabs_item_', '');
				var set_id = el_id.slice(0, el_id.indexOf('-'));
				if (set_id == cid) {
					$el.fx.hide();
				}
			}
		});
	},

	openParents: function($cid)
	{
		// open parent tabs
		var $container = document.id('nn_tabs_container_' + $cid);
		var $parent = $container.getParent();
		while ($parent && $parent != document.body) {
			if ($parent.hasClass('nn_tabs_item')) {
				var pcontainer = $parent.getParent().getParent().getParent();
				if (pcontainer.hasClass('nn_tabs_container')) {
					var pid = $parent.id.replace('nn_tabs_item_', '');
					var pcid = pcontainer.id.replace('nn_tabs_container_', '');
					this.showTab(pid, pcid, 1, 0);
				}
				$parent = pcontainer;
			}
			$parent = $parent.getParent();
		}
	},


	autoHeight: function($el, force)
	{
		if (typeof( $el ) != "undefined" && $el && $el.getStyle('height') && ( force || parseInt($el.getStyle('height')) > 0 )) {
			$el.setStyle('height', 'auto');
		}
	}
});

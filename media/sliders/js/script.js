/**
 * Main JavaScript file
 *
 * @package         Sliders
 * @version         3.3.1
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

window.addEvent('domready', function()
{
	// Only do stuff if nn_sliders_slider and nn_sliders_content is found
	if (document.getElements('div.nn_sliders_slider').length && document.getElements('div.nn_sliders_content').length) {
		(function() { nnSliders = new nnSliders(); }).delay(100);
	} else {
		// Try again 2 seconds later, because IE sometimes can't see object immediately
		(function()
		{
			if (document.getElements('div.nn_sliders_slider').length && document.getElements('div.nn_sliders_content').length) {
				nnSliders = new nnSliders();
			}
		}).delay(2000);
	}
});

var nnSliders = new Class({
	initialize: function()
	{
		var self = this;
		this.containers = [];

		var nn_sliders_hash = '';
		if (nn_sliders_use_hash && window.location.hash) {
			nn_sliders_hash = window.location.hash.replace('#', '');
		}

		document.getElements('div.nn_sliders_container').each(function(container)
		{
			if (typeof( container ) != "undefined") {
				container.removeClass('nn_sliders_noscript');

				var c_id = container.id.replace('nn_sliders_container_', '');
				var active = 0;
				var active_hash = 0;

				// add events on sliders and show them
				container.getElements('div.nn_sliders_slider').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						if (!$el.hasClass('nn_sliders_noslider')) {
							var id = $el.id.replace('nn_sliders_slider_', '');
							var set_id = id.slice(0, id.indexOf('-'));
							if (set_id == c_id) {
								self.containers[id] = c_id;
								// set first slider as active or active slider
								if ($el.hasClass('active')) {
									active = id;
								}
								if (nn_sliders_use_hash) {
									$el.alias = $el.getFirst().className.slice(String('nn_sliders_alias_').length);
									if (!active_hash && nn_sliders_hash && $el.alias == nn_sliders_hash) {
										active_hash = id;
									}
								}
									mode = 'click';
								$el.addEvent(mode, function() { self.showSlide(id, c_id); });
							}
						}
					}
				});

				// add fx
				container.getElements('div.nn_sliders_content_wrapper').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						$el.setStyle('display', 'block');
						var id = $el.id.replace('nn_sliders_content_', '');
						var set_id = id.slice(0, id.indexOf('-'));
						if (set_id == c_id) {
							$el.fx = new Fx.Slide($el, { 'duration': 0, onComplete: function()
							{
								self.autoHeight($el.getParent());
								self.showItem(id);
							} });
							$el.setStyle('visibility', 'hidden');
							$el.fx.hide();
						}
					}
				});

				if (nn_sliders_use_hash && active_hash) {
					active = active_hash;
				}

				// add fx
				container.getElements('div.nn_sliders_item').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						var id = $el.id.replace('nn_sliders_item_', '');
						var set_id = id.slice(0, id.indexOf('-'));
						if (set_id == c_id) {
							$el.setStyle('display', 'block');
							$el.fade_in = new Fx.Tween($el, { property: 'opacity', 'duration': nn_sliders_fade_in_speed });
							$el.fade_out = new Fx.Tween($el, { property: 'opacity', 'duration': nn_sliders_fade_out_speed });
							$el.fx = new Fx.Slide($el, { 'duration': nn_sliders_speed, onComplete: function()
							{
								self.autoHeight($el.getParent());
								self.hideContent(id);
							} }).hide();
						}
					}
				});

				// hide content titles
				container.getElements('.nn_sliders_title').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						$el.setStyle('display', 'none');
					}
				});

				// show only active slider
				self.showSlide(active, c_id, 1, 0, ( active === nn_sliders_urlscroll ));

				container.getElements('div.nn_sliders_slider').each(function($el)
				{
					if (typeof( $el ) != "undefined") {
						$el.setStyle('display', 'block');
					}
				});
			}
		});

		// add onclick events on slider links {sliderlink=...}
		document.getElements('a.nn_sliders_link').each(function($el)
		{
			if (typeof( $el ) != "undefined" && $el.rel && typeof( self.containers[$el.rel] ) != "undefined") {
				$el.addEvent('click', function()
				{
					self.showSlide($el.rel, self.containers[$el.rel], 0, 1, nn_sliders_linkscroll);
				});
				$el.href = 'javascript://';
			}
		});
	},

	showSlide: function(id, c_id, first, open, scroll)
	{
		var self = this;
		var $container = document.id('nn_sliders_container_' + c_id);
		var $item = document.id('nn_sliders_slider_' + id);
		var show_slider = ( first || open || ( $item && !$item.hasClass('active') ) );

		// remove all active classes
		$container.getElements('div.nn_sliders_slider').each(function($el)
		{
			if (typeof( $el ) != "undefined" && $el) {
				var el_id = $el.id.replace('nn_sliders_slider_', '');
				var set_id = el_id.slice(0, el_id.indexOf('-'));
				if (set_id == c_id) {
					$el.removeClass('show');
				}
			}
		});

		if (show_slider && typeof( $item ) != "undefined" && $item) {
			$item.addClass('show');
			if (first) {
				$item.addClass('active');
			}
		}

		var $el = document.id('nn_sliders_content_' + id);

		// show active blocks
		if (typeof( $el ) != "undefined" && $el && typeof( $el.fx ) != "undefined") {
			if (show_slider) {
				$el.setStyle('visibility', 'visible');
				$el.fx.cancel();
				// show active content block
				if (first) {
					$el.fx.show();
					this.autoHeight($el.getParent(), 1);
				} else {
					$el.fx.slideIn();
				}

				if (!first) {
					$item.getElement('a').focus();
				}
			}

		}

		// hide all non-active blocks
		$container.getElements('div.nn_sliders_item').each(function($el)
		{
			if (typeof( $el ) != "undefined" && $el && typeof( $el.fx ) != "undefined") {
				var el_id = $el.id.replace('nn_sliders_item_', '');
				var set_id = el_id.slice(0, el_id.indexOf('-'));
				if (set_id == c_id) {
					$el.fx.cancel();
					if (show_slider && id && $el.id == 'nn_sliders_item_' + id) {
						if (first) {
							$el.fx.show();
							self.autoHeight($el.getParent());
						}
					} else {
						$el.fx.slideOut();
						$el.fade_in.cancel();
						$el.fade_out.cancel().start(0);
					}
				}
			}
		});
	},

	hideContent: function(id)
	{
		var $item = document.id('nn_sliders_slider_' + id);
		if (typeof( $item ) != "undefined" && $item && !$item.hasClass('show')) {
			// hide content block
			var $el = document.id('nn_sliders_content_' + id);
			if (typeof( $el ) != "undefined" && $el) {
				$el.fx.cancel().hide();
				$el.setStyle('visibility', 'hidden');
			}
			$item.removeClass('active');
		}
	},

	showItem: function(id)
	{
		var $item = document.id('nn_sliders_slider_' + id);
		if (typeof( $item ) != "undefined" && $item && $item.hasClass('show')) {
			$item.addClass('active');
			// show item block
			var $el = document.id('nn_sliders_item_' + id);
			if (typeof( $el ) != "undefined" && $el) {
				$el.removeClass('nn_sliders_item_inactive');
				$el.fx.cancel().slideIn();
				$el.fade_out.cancel();
				$el.fade_in.cancel().start(1);
			}
		}
	},


	autoHeight: function($el, force)
	{
		if (typeof( $el ) != "undefined" && $el && $el.getStyle('height') && ( force || parseInt($el.getStyle('height')) > 0 )) {
			$el.setStyle('height', 'auto');
		}
	}
});

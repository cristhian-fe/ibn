<?php
/**
 * Plugin Helper File
 *
 * @package         Sliders
 * @version         3.3.1
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

// Load common functions
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/tags.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';

/**
 * Plugin that replaces stuff
 */
class plgSystemSlidersHelper
{
	function __construct(&$params)
	{
		$this->params = $params;
		$this->hasitems = 0;

		$this->name = 'Sliders';
		$this->alias = 'sliders';
		$this->id = 'nn_sliders';
		$this->item = 'slider';

		$this->params->comment_start = '<!-- START: ' . $this->name . ' -->';
		$this->params->comment_end = '<!-- END: ' . $this->name . ' -->';

		$bts = '((?:<[a-zA-Z][^>]*>\s*){0,3})'; // break tags start
		$bte = '((?:\s*<(?:/[a-zA-Z]|br|BR)[^>]*>){0,3})'; // break tags end

		$this->params->tag_open = preg_replace('#[^a-z0-9-_]#si', '', $this->params->tag_open);
		$this->params->tag_close = preg_replace('#[^a-z0-9-_]#si', '', $this->params->tag_close);
		$this->params->tag_link = preg_replace('#[^a-z0-9-_]#si', '', $this->params->tag_link);
		$this->params->tag_delimiter = ($this->params->tag_delimiter == 'space') ? '(?: |&nbsp;)' : '=';

		$this->params->regex = '#'
			. $bts
			. '\{(' . $this->params->tag_open . 's?'
			. '((?:-[a-zA-Z0-9-_]+)?)'
			. $this->params->tag_delimiter
			. '((?:[^\}]*?\{[^\}]*?\})*[^\}]*?)|/' . $this->params->tag_close
			. '(?:-[a-z0-9-_]*)?)\}'
			. $bte
			. '#s';
		$this->params->regex_end = '#'
			. $bts
			. '\{/' . $this->params->tag_close
			. '(?:-[a-z0-9-_]+)?\}'
			. $bte
			. '#s';
		$this->params->regex_link = '#'
			. '\{' . $this->params->tag_link
			. '(?:-[a-z0-9-_]+)?' . $this->params->tag_delimiter
			. '([^\}]*)\}'
			. '(.*?)'
			. '\{/' . $this->params->tag_link . '\}'
			. '#s';

		$this->allitems = array();
		$this->setcount = 0;

	}

	/* onAfterDispatch */
	function onAfterDispatch()
	{
		// PDF
		if (JFactory::getDocument()->getType() == 'pdf')
		{
			$buffer = JFactory::getDocument()->getBuffer('component');
			if (is_array($buffer))
			{
				if (isset($buffer['component'], $buffer['component']['']))
				{
					if (isset($buffer['component']['']['component'], $buffer['component']['']['component']['']))
					{
						$this->replaceTags($buffer['component']['']['component'][''], 0);
					}
					else
					{
						$this->replaceTags($buffer['component'][''], 0);
					}
				}
				else if (isset($buffer['0'], $buffer['0']['component'], $buffer['0']['component']['']))
				{
					if (isset($buffer['0']['component']['']['component'], $buffer['0']['component']['']['component']['']))
					{
						$this->replaceTags($buffer['component']['']['component'][''], 0);
					}
					else
					{
						$this->replaceTags($buffer['0']['component'][''], 0);
					}
				}
			}
			else
			{
				$this->replaceTags($buffer, 0);
			}
			JFactory::getDocument()->setBuffer($buffer, 'component');
			return;
		}

		// only in html
		if (JFactory::getDocument()->getType() !== 'html' && JFactory::getDocument()->getType() !== 'feed')
		{
			return;
		}

		$buffer = JFactory::getDocument()->getBuffer('component');

		if (empty($buffer) || is_array($buffer))
		{
			return;
		}

		// do not load scripts/styles on print page
		if (JFactory::getDocument()->getType() !== 'feed' && !JFactory::getApplication()->input->getInt('print', 0))
		{
			if ($this->params->load_mootools)
			{
				JHtml::_('behavior.mootools');
			}

			$script = '
				var ' . $this->id . '_speed = 500;
				var ' . $this->id . '_fade_in_speed = 1000;
				var ' . $this->id . '_fade_out_speed = 400;
				var ' . $this->id . '_linkscroll = 0;
				var ' . $this->id . '_url = \'\';
				var ' . $this->id . '_urlscroll = \'\';
				var ' . $this->id . '_use_hash = ' . (int) $this->params->use_hash . ';
			';
			JFactory::getDocument()->addScriptDeclaration('/* START: ' . $this->name . ' scripts */ ' . preg_replace('#\n\s*#s', ' ', trim($script)) . ' /* END: ' . $this->name . ' scripts */');
			JHtml::script($this->alias . '/script.min.js', false, true);

			if ($this->params->load_stylesheet == 2)
			{
				JHtml::stylesheet($this->alias . '/style.min.css', false, true);
			}
			else if ($this->params->load_stylesheet)
			{
				JHtml::stylesheet($this->alias . '/old.min.css', false, true);
				$style = '';
				$css_i = 'div.' . $this->id . '_' . $this->item;
				$this->params->line_color = ($this->params->outline ? '#' . $this->params->line_color : 'transparent');
				if ($this->params->line_color != '#B4B4B4')
				{
					$style .= '
					' . $css_i . ' a,
					' . $css_i . ' a:hover,
					div.' . $this->id . '_content_wrapper div.' . $this->id . '_content {
						border-color: ' . $this->params->line_color . ';
					}
				';
				}
				if ($style)
				{
					JFactory::getDocument()->addStyleDeclaration('/* START: ' . $this->name . ' styles */ ' . preg_replace('#\n\s*#s', ' ', trim($style)) . ' /* END: ' . $this->name . ' styles */');
				}
			}
		}

		if (strpos($buffer, '{' . $this->params->tag_open) === false && strpos($buffer, '{' . $this->params->tag_link) === false)
		{
			return;
		}

		$this->replaceTags($buffer);

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	/* onAfterRender */
	function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html' && JFactory::getDocument()->getType() !== 'feed')
		{
			return;
		}

		$html = JResponse::getBody();
		if ($html == '')
		{
			return;
		}

		if (strpos($html, '{' . $this->params->tag_open) === false)
		{
			if (!$this->hasitems)
			{
				// remove style and script if no items are found
				$html = preg_replace('#\s*<' . 'link [^>]*href="[^"]*/(' . $this->alias . '/css|css/' . $this->alias . ')/[^"]*\.css[^"]*"[^>]* />#s', '', $html);
				$html = preg_replace('#\s*<' . 'script [^>]*src="[^"]*/(' . $this->alias . '/js|js/' . $this->alias . ')/[^"]*\.js[^"]*"[^>]*></script>#s', '', $html);
				$html = preg_replace('#/\* START: ' . $this->name . ' .*?/\* END: ' . $this->name . ' [a-z]* \*/\s*#s', '', $html);
			}
		}
		else
		{
			// only do stuff in body
			list($pre, $body, $post) = nnText::getBody($html);
			$this->replaceTags($body);
			$html = $pre . $body . $post;
		}
		$this->cleanLeftoverJunk($html);

		JResponse::setBody($html, 1, 1);
	}

	function replaceTags(&$str, $checkprint = 1, $last = 0)
	{
		if (!is_string($str) || $str == '')
		{
			return;
		}

		$this->protect($str);

		$print = $checkprint ? (!JFactory::getApplication()->input->getInt('print', 0)) : 0;

		if (!$print || ($last && strpos($str, '{/' . $this->params->tag_close) === false && strpos($str, 'class="' . $this->id . '_container') === false))
		{
			// Replace syntax with general html on print pages
			if (preg_match_all($this->params->regex, $str, $matches, PREG_SET_ORDER) > 0)
			{
				foreach ($matches as $match)
				{
					$title = NNText::cleanTitle($match['4']);
					if (!(strpos($title, '|') === false))
					{
						list($title, $extra) = explode('|', $title, 2);
					}
					$title = trim($title);
					$name = NNText::cleanTitle($title, 1);
					$title = preg_replace('#<\?h[0-9](\s[^>]* )?>#', '', $title);
					$replace = '<a name="' . $name . '"></a><' . $this->params->title_tag . ' class="' . $this->id . '_title">' . $title . '</' . $this->params->title_tag . '>';
					$str = str_replace($match['0'], $replace, $str);
				}
			}
			if (preg_match_all($this->params->regex_end, $str, $matches, PREG_SET_ORDER) > 0)
			{
				foreach ($matches as $match)
				{
					$str = str_replace($match['0'], '', $str);
				}
			}
			if (preg_match_all($this->params->regex_link, $str, $matches, PREG_SET_ORDER) > 0)
			{
				foreach ($matches as $match)
				{
					$href = NNText::getURI($match['1']);
					$link = '<a href="' . $href . '">' . $match['2'] . '</a>';
					$str = str_replace($match['0'], $link, $str);
				}
			}
			$this->unprotect($str);
			return;
		}

		$sets = array();
		$setids = array();

		if (preg_match_all($this->params->regex, $str, $matches, PREG_SET_ORDER) > 0)
		{
			$this->hasitems = 1;
			foreach ($matches as $match)
			{
				if ($match['2']['0'] == '/')
				{
					array_pop($setids);
					continue;
				}
				end($setids);
				$item = new stdClass;
				$item->orig = $match['0'];
				$item->setid = trim(str_replace('-', '_', $match['3']));
				if (empty($setids) || current($setids) != $item->setid)
				{
					$this->setcount++;
					$setids[$this->setcount . '_'] = $item->setid;
				}
				$item->set = str_replace('__', '_', array_search($item->setid, array_reverse($setids)) . $item->setid);
				$item->title = NNText::cleanTitle($match['4']);
				list($item->pre, $item->post) = NNTags::setSurroundingTags($match['1'], $match['5']);
				if (!isset($sets[$item->set]))
				{
					$sets[$item->set] = array();
				}
				$sets[$item->set][] = $item;
			}
		}

		$urlitem = JFactory::getApplication()->input->getString($this->item, '', 'default', 1);
		$urlitem = trim($urlitem);
		if (is_numeric($urlitem))
		{
			$urlitem = '1-' . $urlitem;
		}
		$active_url = '';


		foreach ($sets as $set_id => $items)
		{
			$rand = '___' . rand(100, 999) . '___';
			$active_by_url = '';
			$active_by_cookie = '';
			$active = 0;
			foreach ($items as $i => $item)
			{
				$tag = NNTags::getTagValues($item->title);
				$item->title = $tag->title;
				$item->alias = isset($tag->alias) ? $tag->alias : '';
				$item->active = 0;
				foreach ($tag->params as $j => $val)
				{
					if (in_array($val, array('active', 'opened', 'open')))
					{
						$item->active = 1;
						$active = $i;
						unset($tag->params[$j]);
					}
					else if (in_array($val, array('inactive', 'closed', 'close')))
					{
						$item->active = 0;
						if ($active == $i)
						{
							$active = '';
						}
						unset($tag->params[$j]);
					}
				}
				$item->class = implode(' ', $tag->params);

				$item->set = $set_id . $rand;
				$item->setname = $set_id;
				$item->count = $i + 1;
				$item->id = $item->set . '-' . $item->count;
				$item->haslink = preg_match('#<a [^>]*>.*?</a>#usi', $item->title);


				$item->title_full = $item->title;
				$item->title = NNText::cleanTitle($item->title, 1);
				if ($item->title == '')
				{
					$item->title = NNText::getAttribute('title', $item->title_full);
					if ($item->title == '')
					{
						$item->title = NNText::getAttribute('alt', $item->title_full);
					}
				}
				$item->title = str_replace(array('&nbsp;', '&#160;'), ' ', $item->title);
				$item->title = preg_replace('#\s+#', ' ', $item->title);

				$item->alias = NNText::createAlias($item->alias ? $item->alias : $item->title);

				$item->matches = NNText::createUrlMatches(array($item->alias, $item->title));
				$item->matches[] = ($i + 1) . '';
				$item->matches[] = ((int) $item->set) . '-' . ($i + 1);

				$item->alias = JString::strtolower($item->alias);

				if ($urlitem != '' && (in_array($urlitem, $item->matches, 1) || in_array(strtolower($urlitem), $item->matches, 1)))
				{
					if (!$item->haslink)
					{
						$active_by_url = $i;
					}
				}
				if ($active == $i && $item->haslink)
				{
					$active++;
				}

				$sets[$set_id][$i] = $item;
				$this->allitems[] = $item;
			}

			if ($active_by_url !== '' && isset($sets[$set_id][$active_by_url]))
			{
				$sets[$set_id][$active_by_url]->active = 1;
				$active_url = $sets[$set_id][$active_by_url]->id;
			}
			else if ($active !== '' && isset($sets[$set_id][(int) $active]))
			{
				$sets[$set_id][(int) $active]->active = 1;
			}
		}

		if (preg_match($this->params->regex_end, $str))
		{
			$script_set = 0;
			foreach ($sets as $items)
			{
				$first = key($items);
				end($items);
				$last = key($items);
				foreach ($items as $i => $item)
				{
					$s = '#' . preg_quote($item->orig, '#') . '#';
					if (@preg_match($s . 'u', $str))
					{
						$s .= 'u';
					}
					if (preg_match($s, $str, $match))
					{
						$html = array();
						$html[] = $item->post;
						$html[] = $item->pre;
						if ($item->active)
						{
							// Shows the active slide asap, before the entire script is loaded
							$html[] = '<div id="script_' . $this->id . '" style="display:none;"></div>';
							$html[] = '<script type="text/javascript">document.getElementById(\'script_' . $this->id . '\').innerHTML = '
								. 'String.fromCharCode(60)+\'style type="text/css">'
								. 'div#' . $this->id . '_' . $this->item . '_' . $item->id . ' { display: block !important; }'
								. '\'+String.fromCharCode(60)+\'/style>'
								. '\';</script>';
						}
						if ($i == $first)
						{
							if (!$script_set)
							{
								// Hides the titles and inactive content asap, before the entire script is loaded
								$html[] = '<div id="script_' . $this->id . '" style="display:none;"></div>';
								$html[] = '<script type="text/javascript">document.getElementById(\'script_' . $this->id . '\').innerHTML = '
									. 'String.fromCharCode(60)+\'style type="text/css">'
									. 'div.' . $this->id . '_content_inactive { display: none; }'
									. '.' . $this->id . '_title { display: none !important; }'
									. '\'+String.fromCharCode(60)+\'/style>'
									. '\';</script>';
								$script_set = 1;
							}
							$html[] = '<div class="' . trim($this->id . '_container ' . $this->id . '_container_' . $item->setname . ' ' . $this->id . '_noscript') . '" id="' . $this->id . '_container_' . $item->set . '">';
						}
						else
						{
							$html[] = '<div style="clear:both;"></div>';
							$html[] = '</div></div></div>';
						}

						$item->class = ($item->active == 1 ? ' active' : '') . ' ' . $item->class;
						if (strpos($item->class, 'indent') === false)
						{
							$item->class .= ' ' . $this->params->indent;
						}

						if ($item->haslink && preg_match('#(<a [^>]*>)(.*?)(</a>)#usi', $item->title_full, $match))
						{
							$link = str_replace($match['0'], $match['1'] . '<span>' . $match['2'] . '</span>' . $match['3'], $item->title_full);
							$item->class .= ' ' . $this->id . '_no' . $this->item;
						}
						else
						{
							if ($this->params->use_hash)
							{
								$href = NNText::getURI($item->alias);
							}
							else
							{
								$href = 'javascript: // ' . $item->title;
							}
							$link = '<a href="' . $href . '"><span>' . $item->title_full . '</span></a>';
						}

						$html[] = '<div style="display:none;" class="' . trim($this->id . '_' . $this->item . ' ' . $this->id . '_count_' . $item->count . ' ' . trim($item->class)) . '" id="' . $this->id . '_' . $this->item . '_' . $item->id . '"><span class="' . $this->id . '_alias_' . $item->alias . '">' . $link . '</span></div>';
						$html[] = '<div class="' . trim($this->id . '_content_wrapper ' . $this->id . '_count_' . $item->count . ' ' . trim($item->class)) . ' ' . $this->id . '_content_' . ($item->active ? '' : 'in') . 'active" id="' . $this->id . '_content_' . $item->id . '">';
						$html[] = '<div class="' . trim($this->id . '_content ' . trim($item->class)) . '">';
						$html[] = '<div class="' . $this->id . '_item" id="' . $this->id . '_item_' . $item->id . '">';
						$html[] = '<a name="' . $item->id . '"></a><' . $this->params->title_tag . ' class="' . $this->id . '_title">' . preg_replace('#<\?h[0-9](\s[^>]* )?>#', '', $item->title_full) . '</' . $this->params->title_tag . '>';

						if ($i == $last)
						{
							$html[] = '<script type="text/javascript">'
								. "document.getElementById('" . $this->id . "_container_" . $item->set . "').setAttribute( 'class', document.getElementById('" . $this->id . "_container_" . $item->set . "').className.replace(/\b" . $this->id . "_noscript\b/,'') );"
								. '</script>';
						}

						$html = implode("\n", $html);
						$pos = strpos($str, $match['0']);
						if ($pos !== false)
						{
							$str = substr_replace($str, $html, $pos, strlen($match['0']));
						}
					}
				}
			}
		}

		// closing tag
		if (preg_match_all($this->params->regex_end, $str, $matches, PREG_SET_ORDER) > 0)
		{
			$html = array();
			$html[] = '<div style="clear:both;"></div>';
			$html[] = '</div></div></div>';
			$html[] = '<div style="height:1px;"></div>';
			$html[] = '</div>';
			if ($active_url)
			{
				$html[] = '<script type="text/javascript">';
				$html[] = $this->id . '_url = \'' . $active_url . '\';';
				$html[] = '</script>';
			}
			$html = implode("\n", $html);
			foreach ($matches as $match)
			{
				$m_html = $html;
				list($pre, $post) = NNTags::setSurroundingTags($match['1'], $match['2']);
				$m_html = $pre . $m_html . $post;
				$str = str_replace($match['0'], $m_html, $str);
			}
		}

		// link tag
		if (preg_match_all($this->params->regex_link, $str, $matches, PREG_SET_ORDER) > 0)
		{
			foreach ($matches as $match)
			{
				$linkitem = 0;
				$names = NNText::createUrlMatches(array($match['1']));
				foreach ($names as $name)
				{
					if (is_numeric($name))
					{
						foreach ($this->allitems as $item)
						{
							if (in_array($name, $item->matches, 1) || in_array((int) $name, $item->matches, 1))
							{
								$linkitem = $item;
								break;
							}
						}
					}
					else
					{
						foreach ($this->allitems as $item)
						{
							if (in_array($name, $item->matches, 1) || in_array(strtolower($name), $item->matches, 1))
							{
								$linkitem = $item;
								break;
							}
						}
					}
					if ($linkitem)
					{
						break;
					}
				}
				if ($linkitem)
				{
					$href = NNText::getURI($linkitem->id);
					$link = '<a href="' . $href . '"'
						. ' class="' . $this->id . '_link ' . $this->id . '_link_' . $linkitem->alias . '"'
						. ' rel="' . $linkitem->id . '">' . $match['2'] . '</a>';
				}
				else
				{
					$href = NNText::getURI($name);
					$link = '<a href="' . $href . '">' . $match['2'] . '</a>';
				}
				$str = str_replace($match['0'], $link, $str);
			}
		}

		$this->unprotect($str);
	}

	function protect(&$str)
	{
		NNProtect::protectForm($str, array('{' . $this->params->tag_open, '{/' . $this->params->tag_close, '{' . $this->params->tag_link));
	}

	function unprotect(&$str)
	{
		NNProtect::unprotectForm($str, array('{' . $this->params->tag_open, '{/' . $this->params->tag_close, '{' . $this->params->tag_link));
	}

	/**
	 * Just in case you can't figure the method name out: this cleans the left-over junk
	 */
	function cleanLeftoverJunk(&$str)
	{
		NNProtect::removeInlineComments($str, $this->name);
	}
}

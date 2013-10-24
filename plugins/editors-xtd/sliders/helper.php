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

/**
 ** Plugin that places the button
 */
class plgButtonSlidersHelper
{
	function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * Display the button
	 *
	 * @return array A two element array of ( imageName, textToInsert )
	 */
	function render($name)
	{
		$button = new JObject;

		if (JFactory::getApplication()->isSite() && !$this->params->enable_frontend)
		{
			return $button;
		}

		JHtml::stylesheet('nnframework/style.min.css', false, true);

		$this->params->tag_open = preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_open);
		$this->params->tag_close = preg_replace('#[^a-z0-9-_]#s', '', $this->params->tag_close);
		$this->params->tag_delimiter = ($this->params->tag_delimiter == '=') ? '=' : ' ';

		if ($this->params->button_use_custom_code && $this->params->button_custom_code)
		{
			$text = trim($this->params->button_custom_code);
			$text = str_replace(array("\r", "\n"), array('', '</p>\n<p>'), trim($text)) . '</p>';
			$text = preg_replace('#^(.*?)</p>#', '\1', $text);
			$text = str_replace(array('{slider ', '{/sliders}'), array('{' . $this->params->tag_open . $this->params->tag_delimiter, '{/' . $this->params->tag_close . '}'), trim($text));
		}
		else
		{
			$text = '{' . $this->params->tag_open . $this->params->tag_delimiter . JText::_('SLD_TITLE') . ' 1}\n' .
				'<p>' . JText::_('SLD_TEXT') . '</p>\n' .
				'<p>{' . $this->params->tag_open . $this->params->tag_delimiter . JText::_('SLD_TITLE') . ' 2}</p>\n' .
				'<p>' . JText::_('SLD_TEXT') . '</p>\n' .
				'<p>{/' . $this->params->tag_close . '}</p>';
		}
		$text = str_replace('\\\\n', '\\n', addslashes($text));
		$text = str_replace('{', '{\'+\'', $text);

		$js = "
			function insertSliders(editor) {
				jInsertEditorText('" . $text . "', editor);
			}
		";
		JFactory::getDocument()->addScriptDeclaration($js);

		$class = 'blank';
		if ($this->params->button_icon)
		{
			$class .= ' button-nonumber button-sliders';
		}

		$text_ini = strtoupper(str_replace(' ', '_', $this->params->button_text));
		$text = JText::_($text_ini);
		if ($text == $text_ini)
		{
			$text = JText::_($this->params->button_text);
		}

		$button->modal = false;
		$button->link = '#';
		$button->onclick = 'insertSliders(\'' . $name . '\');return false;';
		$button->text = trim($text);
		$button->name = $class;

		return $button;
	}
}

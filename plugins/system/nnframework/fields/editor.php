<?php
/**
 * Element: Editor
 * Displays an HTML editor text field
 *
 * @package         NoNumber Framework
 * @version         13.10.6
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class JFormFieldNN_Editor extends JFormField
{
	public $type = 'Editor';
	private $params = null;

	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$width = $this->def('width', '100%');
		$height = $this->def('height', 400);

		$this->value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

		// Get an editor object.
		$editor = JFactory::getEditor();
		$html = $editor->display($this->name, $this->value, $width, $height, true, $this->id);

		return $html . '<br clear="all" />';
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}

<?php
/**
 * Element: DateTime
 * Element to display the date and time
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

class JFormFieldNN_DateTime extends JFormField
{
	public $type = 'DateTime';
	private $params = null;

	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$label = $this->def('label');
		$format = $this->def('format');

		$date = JFactory::getDate();

		$tz = new DateTimeZone(JFactory::getApplication()->getCfg('offset'));
		$date->setTimeZone($tz);

		if ($format)
		{
			if (!(strpos($format, '%') === false))
			{
				require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
				$format = NNText::dateToDateFormat($format);
			}
			$html = $date->format($format, 1);
		}
		else
		{
			$html = $date->format('', 1);
		}

		if ($label)
		{
			$html = JText::sprintf($label, $html);
		}

		return '<label class="nn_label">' . $html . '</label>';
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}

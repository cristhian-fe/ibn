<?php
/**
 * NoNumber Extension Manager Update Page
 *
 * @package         NoNumber Extension Manager
 * @version         4.2.2
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2013 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

$xml = array();
$xml[] = '<?xml version="1.0" encoding="utf-8"?>';
$xml[] = '<extensions>';
foreach ($this->items as $item) {
	$xml[] = '	<extension>';
	$xml[] = '		<alias>' . $item->alias . '</alias>';
	$xml[] = '		<version>' . $item->version . '</version>';
	$xml[] = '		<pro>' . $item->pro . '</pro>';
	$xml[] = '		<old>' . $item->old . '</old>';
	$xml[] = '		<missing>' . (!empty($item->missing) ? JText::sprintf('NNEM_MISSING_EXTENSIONS', implode(',', $item->missing)) : '') . '</missing>';
	$xml[] = '	</extension>';
}
$xml[] = '</extensions>';

header("Content-type: text/xml");
echo implode("\n", $xml);
die;

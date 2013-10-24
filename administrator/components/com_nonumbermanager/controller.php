<?php
/**
 * Controller
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

jimport('joomla.application.component.controller');

/**
 * Master Display Controller
 */
class NoNumberManagerController extends JControllerLegacy
{
	/**
	 * @var        string    The default view.
	 */
	protected $default_view = 'default';
}

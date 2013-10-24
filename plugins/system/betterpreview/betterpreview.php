<?php
/**
 * Main Plugin File
 * Does all the magic!
 *
 * @package         Better Preview
 * @version         2.2.3
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2012 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Plugin that makes the preview button as it should be
 */
class plgSystemBetterPreview extends JPlugin
{
	function __construct(&$subject, $config)
	{
		$this->_pass = 0;
		parent::__construct($subject, $config);
	}

	function onAfterRoute()
	{
		$this->_pass = 0;

		// if current page is not an administrator page, return nothing
		if (JFactory::getApplication()->isSite()) {
			return;
		}

		// only in html
		if (JFactory::getDocument()->getType() != 'html') {
			return;
		}

		// load the admin language file
		$lang = JFactory::getLanguage();
		if ($lang->getTag() != 'en-GB') {
			// Loads English language file as fallback (for undefined stuff in other language file)
			$lang->load('plg_' . $this->_type . '_' . $this->_name, JPATH_ADMINISTRATOR, 'en-GB');
		}
		$lang->load('plg_' . $this->_type . '_' . $this->_name, JPATH_ADMINISTRATOR, null, 1);

		jimport('joomla.filesystem.file');
		// return if NoNumber Framework plugin is not installed
		if (!JFile::exists(JPATH_PLUGINS . '/system/nnframework/nnframework.php')) {
			if (JFactory::getApplication()->isAdmin() && JFactory::getApplication()->input->get('option') != 'com_login') {
				$msg = JText::_('BP_NONUMBER_FRAMEWORK_NOT_INSTALLED')
					. ' ' . JText::sprintf('BP_EXTENSION_CAN_NOT_FUNCTION', JText::_('BETTER_PREVIEW'));
				$mq = JFactory::getApplication()->getMessageQueue();
				foreach ($mq as $m) {
					if ($m['message'] == $msg) {
						$msg = '';
						break;
					}
				}
				if ($msg) {
					JFactory::getApplication()->enqueueMessage($msg, 'error');
				}
			}
			return;
		}

		JFactory::getDocument()->addStyleDeclaration('span.viewsite { display:none !important; }');
	}
}

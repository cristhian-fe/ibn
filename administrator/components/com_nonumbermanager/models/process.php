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

jimport('joomla.application.component.modelitem');

/**
 * Process Model
 */
class NoNumberManagerModelProcess extends JModelItem
{
	/**
	 * Get the extensions data
	 */
	public function getItems()
	{
		$ids = JFactory::getApplication()->input->get('ids', array(), 'array');
		$urls = JFactory::getApplication()->input->get('urls', array(), 'array');

		if (empty($ids) || empty($urls)) {
			return array();
		}

		$model = JModel::getInstance('Default', 'NoNumberManagerModel');
		$this->items = $model->getItems($ids);
		foreach ($ids as $i => $id) {
			if (isset($urls[$i])) {
				$this->items[$id]->url = $urls[$i];
			} else {
				unset($this->items[$id]);
			}
		}

		return $this->items;
	}

	/**
	 * Download and install
	 */
	function install($id, $url)
	{
		if (!is_string($url)) {
			return JText::_('NNEM_ERROR_NO_VALID_URL');
		}

		$config = JFactory::getConfig();

		$url = 'http://' . str_replace('http://', '', $url);
		$target = $config->getValue('config.tmp_path') . '/' . uniqid($id) . '.zip';

		jimport('joomla.filesystem.file');
		JFactory::getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);

		if (!(function_exists('curl_init') && function_exists('curl_exec')) && !ini_get('allow_url_fopen')) {
			return JText::_('NNEM_ERROR_CANNOT_DOWNLOAD_FILE');
		} else if (function_exists('curl_init') && function_exists('curl_exec')) {
			/* USE CURL */
			$ch = curl_init();
			$options = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 30
			);
			$params = JComponentHelper::getParams('com_nonumbermanager');
			if ($params->get('use_proxy') && $params->get('proxy_host')) {
				$options[CURLOPT_PROXY] = $params->get('proxy_host') . ($params->get('proxy_port') ? ':' . $params->get('proxy_port') : '');
				$options[CURLOPT_PROXYUSERPWD] = $params->get('proxy_login') . ':' . $params->get('proxy_password');
			}
			curl_setopt_array($ch, $options);
			$content = curl_exec($ch);
			curl_close($ch);
		} else {
			/* USE FOPEN */
			$handle = @fopen($url, 'r');
			if (!$handle) {
				return JText::_('SERVER_CONNECT_FAILED');
			}

			$content = '';
			while (!feof($handle)) {
				$content .= fread($handle, 4096);
				if ($content === false) {
					return JText::_('NNEM_ERROR_FAILED_READING_FILE');
				}
			}
			fclose($handle);
		}

		if (empty($content)) {
			return JText::_('NNEM_ERROR_CANNOT_DOWNLOAD_FILE');
		}

		// Write buffer to file
		JFile::write($target, $content);

		jimport('joomla.installer.installer');
		jimport('joomla.installer.helper');

		// Get an installer instance
		$installer = JInstaller::getInstance();

		// Unpack the package
		$package = JInstallerHelper::unpack($target);

		// Cleanup the install files
		if (!is_file($package['packagefile'])) {
			$config = JFactory::getConfig();
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}
		JInstallerHelper::cleanupInstall($package['packagefile'], $package['packagefile']);

		// Install the package
		if (!$installer->install($package['dir'])) {
			// There was an error installing the package
			return JText::sprintf('COM_INSTALLER_INSTALL_ERROR', JText::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
		}

		return true;
	}

	/**
	 * Download and install
	 */
	function uninstall($id)
	{
		$model = JModel::getInstance('Default', 'NoNumberManagerModel');
		$item = $model->getItems(array($id));
		$item = $item[$id];

		$ids = array();
		foreach ($item->types as $type) {
			if ($type->id) {
				$ids[] = $type->id;
			}
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/manage.php';
		$installer = JModel::getInstance('Manage', 'InstallerModel');
		$result = $installer->remove($ids);
		echo $result;
	}
}

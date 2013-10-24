<?php
/**
 * @version		3.0
 * @package		Joomla
 * @subpackage	Falang
 * @author      Stéphane Bouey
 * @copyright	Copyright (C) 2012 Faboba
 * @license		GNU/GPL, see LICENSE.php
 */


defined( '_JEXEC' ) or die;

require_once JPATH_ROOT.'/administrator/components/com_falang/legacy/controller.php';

class CpanelController extends LegacyController  {
	/**
	 * Joom!Fish Controler for the Control Panel
	 * @param array		configuration
	 * @return joomfishTasker
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'show',  'display' );

		// ensure DB cache table is created and up to date
		JLoader::import( 'helpers.controllerHelper',FALANG_ADMINPATH);
		JLoader::import( 'classes.JCacheStorageJFDB',FALANG_ADMINPATH);
		FalangControllerHelper::_checkDBCacheStructure();
		FalangControllerHelper::_checkDBStructure();

        if( !FalangControllerHelper::_testSystemBotState() ) {;
            echo "<div style='font-size:16px;font-weight:bold;color:red'>".JText::_('COM_FALANG_TEST_SYSTEM_ERROR')."</div>";
        }

	}

	/**
	 * Standard display control structure
	 * 
	 */
	function display()
	{
		$this->view =  $this->getView('cpanel');
		parent::display();
	}
	
	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_falang' );
	}

    function checkUpdates() {

        //get cache timeout from com_installer params
        jimport('joomla.application.component.helper');
        $component = JComponentHelper::getComponent('com_installer');
        $params = $component->params;
        $cache_timeout = $params->get('cachetimeout', 6, 'int');
        $cache_timeout = 3600 * $cache_timeout;


        //find $eid Extension identifier to look for
        $dbo = JFactory::getDBO();
        $query = $dbo->getQuery(true);
        $query->select($dbo->qn('extension_id'))
            ->from($dbo->qn('#__extensions'))
            ->where($dbo->qn('element'). ' = '. $dbo->Quote('pkg_falang'));
        $dbo->setQuery($query);
        $dbo->query();
        $result = $dbo->loadObject();

        $eid =  $result->extension_id;

        //find update for pkg_falang

        $updater = JUpdater::getInstance();
        $update = $updater->findUpdates(array($eid), $cache_timeout);

        //seem $update has problem with cache
        //check manually
        $query = $dbo->getQuery(true);
        $query->select('version')->from('#__updates')->where('element = '.$dbo->Quote('pkg_falang'));
        $dbo->setQuery($query);
        $dbo->query();
        $result = $dbo->loadObject();

        $document =& JFactory::getDocument();
        $document->setMimeEncoding('application/json');

        $version = new FalangVersion();

        if (!$result) {
            echo json_encode(array('update' => "false",'version' => $version->getVersionShort()));
            return true;
        }

        $last_version = $result->version;

        if (version_compare($last_version, $version->getVersionShort(),'>')) {
            echo json_encode(array('update' => "true",'version' =>  $last_version));
        }   else {
            echo json_encode(array('update' => "false",'version' =>$version->getVersionShort() ));
        }

        return true;
    }
}

?>

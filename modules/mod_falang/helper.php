<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_falang
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.language.helper');
jimport('joomla.utilities.utility');
jimport('joomla.html.parameter');

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

abstract class modFaLangHelper
{
	public static function getList(&$params)
	{
		$lang = JFactory::getLanguage();
		$languages	= JLanguageHelper::getLanguages();
		$app		= JFactory::getApplication();

        //use to remove default language code in url
        $lang_codes 	= JLanguageHelper::getLanguages('lang_code');
        $default_lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
        $default_sef 	= $lang_codes[$default_lang]->sef;


        $menu = $app->getMenu();
        $active = $menu->getActive();
        $uri = JURI::getInstance();

        if (FALANG_J30) {
            $assoc = isset($app->item_associations) ? $app->item_associations : 0;
        } else {
            $assoc = $app->get('menu_associations', 0);
        }

		if ($assoc) {
			if ($active) {
				$associations = MenusHelper::getAssociations($active->id);
			}
		}
   		foreach($languages as $i => &$language) {
			// Do not display language without frontend UI
			if (!JLanguage::exists($language->lang_code)) {
				unset($languages[$i]);
			}
            if (FALANG_J30) {
                $language_filter = JLanguageMultilang::isEnabled();
            } else {
                $language_filter = $app->getLanguageFilter();
            }


            //set language active before language filter use for sh404 notice
            $language->active =  $language->lang_code == $lang->getTag();
            if ($language_filter) {
                if (isset($associations[$language->lang_code]) && $menu->getItem($associations[$language->lang_code])) {
                    $itemid = $associations[$language->lang_code];
                    if ($app->getCfg('sef')=='1') {
                        $language->link = JRoute::_('index.php?lang='.$language->sef.'&Itemid='.$itemid);
                    }
                    else {
                        $language->link = 'index.php?lang='.$language->sef.'&Itemid='.$itemid;
                    }
                }
                else {
                    //sef case
                    if ($app->getCfg('sef')=='1') {

                         //$uri->setVar('lang',$language->sef);
                         $router = JApplication::getRouter();
                         $tmpuri = clone($uri);

                         $router->parse($tmpuri);

                         $vars = $router->getVars();
                         //workaround to fix index language
                         $vars['lang'] = $language->sef;

                        //case of category article
                        if (!empty($vars['view']) && $vars['view'] == 'article' && !empty($vars['option']) && $vars['option'] == 'com_content') {

                            if (FALANG_J30){
                                JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
                                $model =& JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
                                $appParams = JFactory::getApplication()->getParams();
                            } else {
                                JModel::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
                                $model =& JModel::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
                                $appParams = JFactory::getApplication()->getParams();
                            }


                            $model->setState('params', $appParams);

                            //in sef some link have this url
                            //index.php/component/content/article?id=39
                            //id is not in vars but in $tmpuri
                            if (empty($vars['id'])) {
                                $tmpid = $tmpuri->getVar('id');
                                if (!empty($tmpid)) {
                                    $vars['id'] = $tmpuri->getVar('id');
                                } else {
                                    continue;
                                }
                            }

                            $item =& $model->getItem($vars['id']);

                            //get alias of content item without the id , so i don't have the translation
                            $db = JFactory::getDbo();
                            $query = $db->getQuery(true);
                            $query->select('alias')->from('#__content')->where('id = ' . (int) $item->id);
                            $db->setQuery($query);
                            $alias = $db->loadResult();

                            $vars['id'] = $item->id.':'.$alias;
                            $vars['catid'] =$item->catid.':'.$item->category_alias;
                        }

                        $url = 'index.php?'.JURI::buildQuery($vars);
                        $language->link = JRoute::_($url);

                        //TODO check performance 3 queries by languages -1
                        /**
                         * Replace the slug from the language switch with correctly translated slug.
                         * $language->lang_code language de la boucle (icone lien)
                         * $lang->getTag() => language en cours sur le site
                         * $default_lang langue par default du site
                         */
                        if($lang->getTag() != $language->lang_code && !empty($vars['Itemid']))
                        {
                            $fManager = FalangManager::getInstance();
                            $id_lang = $fManager->getLanguageID($language->lang_code);
                            $db = JFactory::getDbo();
                            // get translated path if exist
                            $query = $db->getQuery(true);
                            $query->select('fc.value')
                                ->from('#__falang_content fc')
                                ->where('fc.reference_id = '.(int)$vars['Itemid'])
                                ->where('fc.language_id = '.(int) $id_lang )
                                ->where('fc.reference_field = \'path\'')
                                ->where('fc.reference_table = \'menu\'');
                            $db->setQuery($query);
                            $translatedPath = $db->loadResult();

                            // $translatedPath not exist if not translated or site default language
                            // don't pass id to the query , so no translation given by falang
                            $query = $db->getQuery(true);
                            $query->select('m.path')
                                ->from('#__menu m')
                                ->where('m.id = '.(int)$vars['Itemid']);
                            $db->setQuery($query);
                            $originalPath = $db->loadResult();

                            $pathInUse = null;
                            //si on est sur une page traduite on doit récupérer la traduction du path en cours
                            if ($default_lang != $lang->getTag() ) {
                                $id_lang = $fManager->getLanguageID($lang->getTag());
                                // get translated path if exist
                                $query = $db->getQuery(true);
                                $query->select('fc.value')
                                    ->from('#__falang_content fc')
                                    ->where('fc.reference_id = '.(int)$vars['Itemid'])
                                    ->where('fc.language_id = '.(int) $id_lang )
                                    ->where('fc.reference_field = \'path\'')
                                    ->where('fc.reference_table = \'menu\'');
                                $db->setQuery($query);
                                $pathInUse = $db->loadResult();

                            }

                            if (!isset($translatedPath)) {
                                $translatedPath = $originalPath;
                            }

                            // not exist if not translated or site default language
                            if (!isset($pathInUse)) {
                                $pathInUse = $originalPath ;
                            }

                            //make replacement in the url

                            //si language de boucle et language site
                            if($language->lang_code == $default_lang) {
                                if (isset($pathInUse) && isset($originalPath)){
                                    $language->link = str_replace($pathInUse, $originalPath, $language->link);
                                }
                            } else {
                                if (isset($pathInUse) && isset($translatedPath)){
                                    $language->link = str_replace($pathInUse, $translatedPath, $language->link);
                                }
                            }

                        }
                    }
                    //default case
             else {
                        //we can't remove default language in the link
                        $uri->setVar('lang',$language->sef);
                        $language->link = 'index.php?'.$uri->getQuery();
                    }
                }
            }
            else {
                $language->link = 'index.php';
            }

		}
		return $languages;
	}

    public static function isFalangDriverActive() {
        $db = JFactory::getDBO();
        if (!is_a($db,"JFalangDatabase")){
           return false;
        }
           return true;
    }

}

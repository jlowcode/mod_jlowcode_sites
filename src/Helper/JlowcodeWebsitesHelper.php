<?php
/**
 * jLowcode Websites
 * 
 * @package     Joomla.Module
 * @subpackage  JlowcodeWebsites
 * @copyright   Copyright (C) 2025 Jlowcode Org - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Jlowcode\Module\JlowcodeWebsites\Site\Helper;

defined('_JEXEC') || die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

/**
 * Helper for mod_jlowcode_websites
 * 
 */
class JlowcodeWebsitesHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    private $websiteTable = 'sites';
    private $tableRepeatable;

    /**
     * Returns the layout data.
     * 
     * @param       Registry                    $params     Module params
     * @param       CMSApplicationInterface     $app        Application
     * 
     * @return      stdClass
     */
    public function getWebsite(Registry $params, CMSApplicationInterface $app)
    {
        if (!$app instanceof SiteApplication) {
            return [];
        }

        $this->setTableRepeatable();
        if(empty($this->tableRepeatable)) {
            return [];
        }

        $isSubPage = $this->checkIsSubPage($app);
        if(!$isSubPage) {
            return [];
        }

        $websiteId = $this->getWebsiteId($app);
        $websiteData = $this->getWebsiteData($websiteId, $app);

        if(!$websiteData) {
            return;
        }

        $this->renderMenu($websiteData, $app);

        return $websiteData;
    }

    /**
     * This method render the website menu using the mod-menu module
     * 
     * @param       object                      &$websiteData       Website data to add the menu
     * @param       CMSApplicationInterface     $app                Application
     * 
     * @return      null
     */
    private function renderMenu(&$websiteData, $app)
    {
        $module          = new \stdClass();
        $module->id      = 0;
        $module->module  = 'mod_menu';
        $module->params  = new Registry([
            'menutype' => $this->getMenuType($websiteData->id_parent_menutype),
            'layout'   => 'default',
            'startLevel' => 2
        ]);

        $websiteData->menu = ModuleHelper::renderModule($module);
    }

    /**
     * This method get the menu type related. It uses the menu type id
     * 
     * @param       int     $idMenuType     Menu id to search
     * 
     * @return      string
     */
    private function getMenuType($idMenuType)
    {
        $modelMenu = Factory::getApplication()->bootComponent('com_menus')->getMVCFactory()->createModel('Menu', 'Administrator');
        $modelMenu->getState(); 	//We need do this to set __state_set before the save

        return $modelMenu->getItem($idMenuType)->menutype;
    }

    /**
     * This method get the data from database to render the website
     * 
     * @param       int                         $websiteId      Website id for search
     * @param       CMSApplicationInterface     $app            Application
     * 
     * @return      object
     */
    private function getWebsiteData($websiteId, $app)
    {
        $db = $this->getDatabase();

        if(!$websiteId) {
            return;
        }

        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->qn($this->websiteTable))
            ->where($db->qn('id') . ' = ' . $db->q($websiteId));
        $db->setQuery($query);
        $data = $db->loadObjectList('id')[$websiteId];

        return $data;
    }

    /**
     * This method return the website id. It use the current list id
     * 
     * @param       CMSApplicationInterface     $app        Application
     * 
     * @return      int
     */
    private function getWebsiteId(CMSApplicationInterface $app)
    {
        $db = $this->getDatabase();

        $input = $app->input;
        $listId = $input->getInt('listid');

        $query = $db->getQuery(true);
        $query->select($db->qn('parent_id'))
            ->from($db->qn($this->tableRepeatable))
            ->where($db->qn('menu_list') . ' = ' . $db->q($listId));
        $db->setQuery($query);
        $websiteId = $db->loadResult();

        return (int) $websiteId;
    }

    /**
     * This method verify if the actual list is a sub page of any website. It use the current list id
     * 
     * @param       CMSApplicationInterface     $app        Application
     * 
     * @return      bool
     */
    private function checkIsSubPage(CMSApplicationInterface $app)
    {
        $db = $this->getDatabase();

        $input = $app->input;
        $listId = $input->getInt('listid');

        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
            ->from($db->qn($this->tableRepeatable))
            ->where($db->qn('menu_list') . ' = ' . $db->q($listId));
        $db->setQuery($query);
        $isSubPage = $db->loadResult();

        return (bool) $isSubPage;
    }

    /**
     * This method search for $this->table in #__fabrik_lists and set the repeatable table for use in querys
     * 
     * @return      null
     */
    private function setTableRepeatable()
    {
		$joinModel = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel('Join', 'FabrikFEModel');
		$listModel = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel('List', 'FabrikFEModel');

        $db = $this->getDatabase();

        $query = $db->getQuery(true);
        $query->select($db->qn('id'))
            ->from($db->qn('#__fabrik_lists'))
            ->where($db->qn('db_table_name') . ' = ' . $db->q($this->websiteTable));
        $db->setQuery($query);
        $websitesListId = $db->loadResult();

        if(empty($websitesListId)) {
            return;
        }

		$listModel->setId($websitesListId);
        $formModel = $listModel->getFormModel();
        $groups = $formModel->getPublishedGroups();
        $idGroup = array_values($groups)[1]->id;           // Repeat group must be the second group created
        $joinModel->setId($groups[$idGroup]->join_id);

        $this->tableRepeatable = $joinModel->getJoin()->table_join;
    }
}
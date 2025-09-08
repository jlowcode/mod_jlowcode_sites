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
use Gantry\Framework\Gantry;

/**
 * Helper for mod_jlowcode_websites
 * 
 */
class JlowcodeWebsitesHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    private $websiteTable;
    private $menuItensTable;

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

        $this->setTableMenuItensList();
        $this->setTableWebsiteList();
        if(empty($this->websiteTable) || empty($this->menuItensTable)) {
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
     * This method render the website menu
     * 
     * @param       object                      &$websiteData       Website data to add the menu
     * @param       CMSApplicationInterface     $app                Application
     * 
     * @return      null
     */
    private function renderMenu(&$websiteData, $app)
    {
        $gantry = Gantry::instance();
        $theme = $gantry['theme'];
        $menu = $gantry['menu'];

        $menuName = $this->getMenuType($websiteData->id_parent_menutype);
        $menuConfig = [
            'menu' => $menuName,
            'startLevel' => 2,
            'enabled' => 1,
            'maxLevels' => 0,
            'renderTitles' => 0,
            'base' => $websiteData->id_separator_menu_item
        ];
        $items = $this->getMenuItems($websiteData->url);
        $config = $menu->config();
        $config->set('settings.type', 'custom');
        $menu->addCustom(array(), $items);

        $config->merge(array('items' => $items));
        $config->merge(array('ordering' => $items));
        $config->get('items');
        $instanceMenu = $menu->instance(array(), $config);

        $websiteData->menu =  $theme->render('@particles/menu.html.twig', [
            'menu' => $instanceMenu,
            'particle' => $menuConfig
        ]);
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

        $listId = $this->getListId($app);

        $query = $db->getQuery(true);
        $query->select($db->qn('site'))
            ->from($db->qn($this->menuItensTable))
            ->where($db->qn('menu_list') . ' = ' . $db->q($listId));
        $db->setQuery($query);
        $websiteId = $db->loadResult();

        return (int) $websiteId;
    }

    private function getMenuItems($menuType)
    {
        $modelItem = Factory::getApplication()->bootComponent('com_menus')->getMVCFactory()->createModel('Item', 'Administrator');
        $app = Factory::getApplication();

        $modelItem->getState(); 	//We need do this to set __state_set before the save
	    $menu = $app->getMenu();
        $url = 'index.php?option=com_fabrik&view=list&listid=' . $this->getListId($app);
        $attributes = ['menutype'];
        $values = [$menuType];
        $menuItems = $menu->getItems($attributes, $values, false);

        $items = array();
        $removeItensWithThisAlias = array();

        // Get the alias of itens with -form or -details to remove the menu itens that have the same alias without this sufix
        foreach ($menuItems as $item) {
            if(stripos($item->alias, '-form') !== false || stripos($item->alias, '-details') !== false) {
                $removeItensWithThisAlias[] = str_ireplace('-form', '', str_ireplace('-details', '', $item->alias));
            }

            $dataMenuItems[] = (array) $modelItem->getItem($item->id);
        }

        // This is to avoid duplicate itens in menu
        foreach ($removeItensWithThisAlias as $alias) {
            foreach ($dataMenuItems as $item) {
                if($item['alias'] != $alias) {
                    $items[] = $item;
                }
            }
        }

        return $items;
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
        $listId = $this->getListId($app);

        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
            ->from($db->qn($this->menuItensTable))
            ->where($db->qn('menu_list') . ' = ' . $db->q($listId));
        $db->setQuery($query);
        $isSubPage = $db->loadResult();

        return (bool) $isSubPage;
    }

    /**
     * This method set the table of menu itens list for use in query
     * 
     * @return      null
     */
    private function setTableMenuItensList()
    {
        $listModelMenuItens = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel('List', 'FabrikFEModel');
        $listModelMenuItens->setId($this->getIdListMenuItens());

        $formModelMenuItens = $listModelMenuItens->getFormModel();
        $this->menuItensTable = $formModelMenuItens->getTableName();
    }

    /**
     * This method set the table of website list for use in query
     * 
     * @return      null
     */
    private function setTableWebsiteList()
    {
        $listModelWebsite = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel('List', 'FabrikFEModel');
        $listModelWebsite->setId($this->getIdListWebsite());

        $formModelWebsite = $listModelWebsite->getFormModel();
        $this->websiteTable = $formModelWebsite->getTableName();
    }

    /**
     * This method get the id of the website list
     * 
     * @return      int
     */
    private function getIdListWebsite()
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true);
        $query->select($db->qn('id'))
            ->from($db->qn('#__fabrik_lists'))
            ->where($db->qn('db_table_name') . ' = ' . $db->q('sites'));
        $db->setQuery($query);
        $websitesListId = $db->loadResult();

        return $websitesListId;
    }

    /**
     * This method get the id of the menu itens list
     * 
     * @return      int
     */
    private function getIdListMenuItens()
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true);
        $query->select($db->qn('id'))
            ->from($db->qn('#__fabrik_lists'))
            ->where($db->qn('db_table_name') . ' = ' . $db->q('itens_do_menu'));
        $db->setQuery($query);
        $menuItensListId = $db->loadResult();

        return $menuItensListId;
    }

    /**
     * This method get the list id from input or from form id
     * 
     * @param       CMSApplicationInterface     $app        Application
     * 
     * @return      int
     */
    private function getListId($app)
    {
        $formModel = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel('Form', 'FabrikFEModel');

        $input = $app->input;
        $listId = $input->getInt('listid');

        if(!$listId) {
            $formId = $input->getInt('formid');
            $formModel->setId($formId);
            $listId = $formModel->getListModel()->getId();
        }

        return $listId;
    }
}
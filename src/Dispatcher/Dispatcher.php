<?php

/**
 * jLowcode Websites
 * 
 * @package     Joomla.Module
 * @subpackage  JlowcodeWebsites
 * @copyright   Copyright (C) 2025 Jlowcode Org - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Jlowcode\Module\JlowcodeWebsites\Site\Dispatcher;

defined('_JEXEC') || die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Dispatcher class for mod_jlowcode_websites
 *
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return      array
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $data['website'] = $this->getHelperFactory()->getHelper('JlowcodeWebsitesHelper')->getWebsite($data['params'], $data['app']);

        return $data;
    }
}
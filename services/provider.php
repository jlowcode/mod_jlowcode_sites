<?php
/**
 * jLowcode Websites
 * 
 * @package     Joomla.Module
 * @subpackage  JlowcodeWebsites
 * @copyright   Copyright (C) 2025 Jlowcode Org - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The jlowcodeSite module service provider.
 * 
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Jlowcode\\Module\\JlowcodeWebsites'));
        $container->registerServiceProvider(new HelperFactory('\\Jlowcode\\Module\\JlowcodeWebsites\\Site\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
<?php

/**
 * jLowcode Websites
 * 
 * @package     Joomla.Module
 * @subpackage  JlowcodeWebsites
 * @copyright   Copyright (C) 2025 Jlowcode Org - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_jlowcode_websites', 'mod_jlowcode_websites/template.css');

if(empty($website)) {
    return;
}
?>

<div class="website">
    <?php if(!empty($website->website_banner)) : ?>
        <div class="banner-website">
            <img src="<?php echo $website->website_banner ?>" alt="<?php echo Text::_("MOD_JLOWCODE_WEBSITES_BANNER_SITE_ALT") ?>"/>
        </div>
    <?php endif; ?>
    <div class="menu-website">
        <?php echo $website->menu ?>
    </div>
</div>
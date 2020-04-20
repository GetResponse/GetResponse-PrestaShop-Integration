<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     Getresponse <grintegrations@getresponse.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_16_2_0($object)
{
    return (add_hooks_1620($object) && add_sql_1620($object) && add_tabs($object));
}

function add_sql_1620($object)
{
    $sql = array();
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'getresponse_ecommerce` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) DEFAULT NULL,
            `gr_id_shop` varchar(16) DEFAULT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'getresponse_products` (
            `id_product` int(11) unsigned NOT NULL,
            `gr_id_product` varchar(32) DEFAULT NULL,
            UNIQUE KEY (`id_product`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'getresponse_subscribers` (
            `id_user` int(11) unsigned NOT NULL,
            `id_campaign` varchar(16) DEFAULT NULL,
            `gr_id_user` varchar(16) DEFAULT NULL,
            `email` varchar(128) DEFAULT NULL,
            UNIQUE KEY `id_user` (`id_user`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'cart` ADD `cart_hash` varchar(32);';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'cart` ADD `gr_id_cart` varchar(32);';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'orders` ADD `gr_id_order` varchar(32);';

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'getresponse_settings` 
    ADD `active_tracking` enum(\'yes\',\'no\', \'disabled\') NOT NULL DEFAULT \'disabled\'';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'getresponse_settings` ADD `tracking_snippet` text';

    //Install SQL
    foreach ($sql as $s) {
        try {
            Db::getInstance()->Execute($s);
        } catch (Exception $e) {
        }
    }

    return true;
}

function add_hooks_1620($object)
{
    return ($object->registerHook('cart')
        && $object->registerHook('postUpdateOrderStatus')
        && $object->registerHook('hookOrderConfirmation'));
}

function add_tabs($object)
{
    $object->createSubTabs((int) Tab::getIdFromClassName('AdminGetresponse'));
    return true;
}

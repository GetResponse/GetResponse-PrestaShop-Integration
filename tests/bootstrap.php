<?php
/**
 * 2007-2018 PrestaShop
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
 * @copyright 2007-2019 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Tools.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Configuration.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/ImageType.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Translate.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Link.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Category.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Tools.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Product.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/ProductGenerator.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Cart.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Customer.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/MockParams/CustomerParams.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Currency.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Order.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/OrderState.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Address.php';
require_once dirname(__FILE__) . '/Unit/PrestashopMock/Country.php';
require_once dirname(__FILE__) . '/../classes/GetResponseRepository.php';
require_once dirname(__FILE__) . '/../classes/GrApiException.php';

define('__PS_BASE_URI__', 'http://my-prestashop.com/');

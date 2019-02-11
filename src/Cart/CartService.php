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

namespace GetResponse\Cart;

use Cart;
use Configuration;
use Currency;
use CurrencyCore;
use Customer;
use GetResponse\Product\ProductFactory;
use GrShareCode\Cart\Command\AddCartCommand as GrAddCartCommand;
use GrShareCode\Cart\Cart as GrCart;
use GrShareCode\Cart\CartService as GrCartService;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\Product\ProductsCollection;
use Product;

/**
 * Class CartService
 */
class CartService
{
    /** @var GrCartService */
    private $grCartService;
    /** @var string */
    private $cartUrl;

    /**
     * @param GrCartService $grCartService
     * @param string $cartUrl
     */
    public function __construct(GrCartService $grCartService, $cartUrl)
    {
        $this->grCartService = $grCartService;
        $this->cartUrl = $cartUrl;
    }

    /**
     * @param Cart $cart
     * @param string $contactListId
     * @param string $grShopId
     * @throws GetresponseApiException
     */
    public function sendCart(Cart $cart, $contactListId, $grShopId)
    {
        $products = $cart->getProducts();

        $productCollection = $this->getOrderProductsCollection($products);

        $grCart = new GrCart(
            (string)$cart->id,
            $productCollection,
            $this->getCurrencyIsoCode((int)$cart->id_currency),
            $cart->getOrderTotal(false),
            $cart->getOrderTotal(true),
            $this->cartUrl
        );

        $customer = new Customer($cart->id_customer);
        $email = $customer->email;

        $this->grCartService->sendCart(
            new GrAddCartCommand($grCart, $email, $contactListId, $grShopId)
        );
    }

    /**
     * @param array $products
     * @return ProductsCollection
     */
    private function getOrderProductsCollection(array $products)
    {
        $productsCollection = new ProductsCollection();

        foreach ($products as $product) {
            $prestashopProduct = new Product($product['id_product']);

            if (empty($prestashopProduct->reference)) {
                continue;
            }
            $productService = new ProductFactory();

            $getresponseProduct = $productService->createShareCodeProductFromProduct(
                $prestashopProduct,
                $product['quantity'],
                Configuration::get('PS_LANG_DEFAULT')
            );

            $productsCollection->add($getresponseProduct);
        }

        return $productsCollection;
    }

    /**
     * @param int $currencyId
     * @return string
     */
    private function getCurrencyIsoCode($currencyId)
    {
        $isoCode = (new Currency($currencyId))->iso_code;

        return !empty($isoCode) ? $isoCode : CurrencyCore::getDefaultCurrency()->iso_code;
    }
}

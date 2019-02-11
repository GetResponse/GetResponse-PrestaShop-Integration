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

namespace GetResponse\Tests\Unit\Cart;

use Cart;
use GetResponse\Cart\CartService;
use GetResponse\Product\ProductFactory;
use GetResponse\Tests\Unit\BaseTestCase;
use GrShareCode\Cart\CartService as GrCartService;
use GrShareCode\Cart\Command\AddCartCommand as GrAddCartCommand;
use GrShareCode\Cart\Cart as GrCart;
use GrShareCode\Product\ProductsCollection;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class CartServiceTest
 * @package GetResponse\Tests\Unit\Cart
 */
class CartServiceTest extends BaseTestCase
{
    /** @var GrCartService | PHPUnit_Framework_MockObject_MockObject */
    private $grCartService;
    /** @var CartService */
    private $sut;
    /** @var string */
    private $cartUrl;

    protected function setUp()
    {
        $this->grCartService = $this->getMockWithoutConstructing(GrCartService::class);
        $this->cartUrl = 'http://store.com/cart';
        $this->sut = new CartService($this->grCartService, $this->cartUrl);
    }

    /**
     * @test
     */
    public function shouldRemoveCartWhenNoProducts()
    {
        $params = [
            'id' => 'grId',
            'id_currency' => 1,
            'id_customer' => 1,
            'total_with_tax' => (float)10,
            'total' => (float)8,
            'products' => []
        ];

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $cart = new Cart($params);

        $grCart = new GrCart(
            $params['id'],
            new ProductsCollection(),
            'PLN',
            $params['total'],
            $params['total_with_tax'],
            $this->cartUrl
        );

        $grAddCartCommand = new GrAddCartCommand($grCart, 'customer@getresponse.com', $contactListId, $grShopId);

        $this->grCartService
            ->expects(self::once())
            ->method('sendCart')
            ->with($grAddCartCommand);


        $this->sut->sendCart($cart, $contactListId, $grShopId);
    }


    /**
     * @test
     */
    public function shouldSkipProductsWithEmptySku()
    {
        $product1 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_1_WITH_SKU);
        $product1['quantity'] = 1;

        $product2 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_3_WITHOUT_SKU);
        $product2['quantity'] = 2;

        $params = [
            'id' => 'grId',
            'id_currency' => 1,
            'id_customer' => 1,
            'total_with_tax' => (float)10,
            'total' => (float)8,
            'products' => [$product1, $product2]
        ];

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $cart = new Cart($params);

        $getresponseProduct = (new ProductFactory())->createShareCodeProductFromProduct(
            new \Product(\ProductGenerator::PROD_1_WITH_SKU),
            1,
            1
        );
        $productsCollection = new ProductsCollection();
        $productsCollection->add($getresponseProduct);


        $grCart = new GrCart(
            $params['id'],
            $productsCollection,
            'PLN',
            $params['total'],
            $params['total_with_tax'],
            $this->cartUrl
        );

        $grAddCartCommand = new GrAddCartCommand($grCart, 'customer@getresponse.com', $contactListId, $grShopId);

        $this->grCartService
            ->expects(self::once())
            ->method('sendCart')
            ->with($grAddCartCommand);


        $this->sut->sendCart($cart, $contactListId, $grShopId);
    }


    /**
     * @test
     */
    public function shouldSendCart()
    {
        $product1 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_1_WITH_SKU);
        $product1['quantity'] = 1;

        $product2 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_2_WITH_SKU);
        $product2['quantity'] = 2;

        $params = [
            'id' => 'grId',
            'id_currency' => 1,
            'id_customer' => 1,
            'total_with_tax' => 10.0,
            'total' => 8.0,
            'products' => [$product1, $product2]
        ];

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $cart = new Cart($params);

        $productsCollection = new ProductsCollection();
        $productsCollection->add((new ProductFactory())->createShareCodeProductFromProduct(
            new \Product(\ProductGenerator::PROD_1_WITH_SKU),
            1,
            1
        ));
        $productsCollection->add((new ProductFactory())->createShareCodeProductFromProduct(
            new \Product(\ProductGenerator::PROD_2_WITH_SKU),
            2,
            1
        ));

        $grCart = new GrCart(
            $params['id'],
            $productsCollection,
            'PLN',
            $params['total'],
            $params['total_with_tax'],
            $this->cartUrl
        );

        $grAddCartCommand = new GrAddCartCommand($grCart, 'customer@getresponse.com', $contactListId, $grShopId);

        $this->grCartService
            ->expects(self::once())
            ->method('sendCart')
            ->with($grAddCartCommand);

        $this->sut->sendCart($cart, $contactListId, $grShopId);
    }
}

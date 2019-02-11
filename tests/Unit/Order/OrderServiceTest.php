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

namespace GetResponse\Tests\Unit\Order;

use GetResponse\Order\OrderFactory;
use GetResponse\Order\OrderService;
use GetResponse\Product\ProductFactory;
use GetResponse\Tests\Unit\BaseTestCase;
use GrShareCode\Address\Address;
use GrShareCode\Order\Command\AddOrderCommand;
use GrShareCode\Order\Order as GrOrder;
use GrShareCode\Order\OrderService as GrOrderService;
use GrShareCode\Product\Category\Category;
use GrShareCode\Product\Category\CategoryCollection;
use GrShareCode\Product\ProductsCollection;
use GrShareCode\Product\Variant\Images\Image;
use GrShareCode\Product\Variant\Images\ImagesCollection;
use GrShareCode\Product\Variant\Variant;
use GrShareCode\Product\Variant\VariantsCollection;
use Order;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class OrderServiceTest
 * @package GetResponse\Tests\Unit\Order
 */
class OrderServiceTest extends BaseTestCase
{

    /** @var GrOrderService | PHPUnit_Framework_MockObject_MockObject */
    private $grOrderService;

    /** @var OrderService */
    private $sut;

    protected function setUp()
    {
        $this->grOrderService = $this->getMockWithoutConstructing(GrOrderService::class);
        $this->sut = new OrderService(
            $this->grOrderService,
            new OrderFactory(new ProductFactory())
        );
    }

    /**
     * @test
     */
    public function shouldNotSendOrderIfNoProductFounded()
    {
        $order = new Order([
            'products' => [],
        ]);

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $this->grOrderService
            ->expects(self::never())
            ->method('addOrder');

        $this->sut->sendOrder($order, $contactListId, $grShopId);
    }

    /**
     * @test
     */
    public function shouldNotSendOrderIfProductSkuIsEmpty()
    {
        $product = \ProductGenerator::genProductParams(\ProductGenerator::PROD_3_WITHOUT_SKU);
        $product['product_quantity'] = 5;

        $order = new Order(['products' => [$product]]);

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $this->grOrderService
            ->expects(self::never())
            ->method('addOrder');

        $this->sut->sendOrder($order, $contactListId, $grShopId);
    }

    /**
     * @test
     */
    public function shouldSendOrder()
    {
        $product1 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_1_WITH_SKU);
        $product1['product_quantity'] = 2;

        $product2 = \ProductGenerator::genProductParams(\ProductGenerator::PROD_2_WITH_SKU);
        $product2['product_quantity'] = 1;

        $productService = new ProductFactory();
        $productsCollection = new ProductsCollection();
        $productsCollection->add(
            $productService->createShareCodeProductFromProduct(new \Product(\ProductGenerator::PROD_1_WITH_SKU), 2, 1)
        );
        $productsCollection->add(
            $productService->createShareCodeProductFromProduct(new \Product(\ProductGenerator::PROD_2_WITH_SKU), 1, 1)
        );

        $params = [
            'products' => [$product1, $product2],
            'id' => 'id',
            'current_state' => 'pending',
            'date_add' => '2018-10-10 12:12:12',
            'id_cart' => 5,
            'id_customer' => 1,
            'total_paid_tax_excl' => '12',
            'total_paid_tax_incl' => '14'
        ];

        $contactListId = 'contactListId';
        $grShopId = 'grShopId';

        $order = new GrOrder(
            $params['id'],
            12.0,
            'PLN',
            $productsCollection
        );

        $order->setTotalPriceTax(2.0);
        $order->setOrderUrl('http://my-prestashop.com/?controller=order-detail&id_order=id');
        $order->setStatus('pending');
        $order->setExternalCartId('5');
        $order->setShippingPrice(0.0);
        $order->setProcessedAt('2018-10-10T12:12:12+0000');

        $expected = new AddOrderCommand(
            $order,
            'customer@getresponse.com',
            'contactListId',
            'grShopId'
        );


        $this->grOrderService
            ->expects(self::once())
            ->method('addOrder')
            ->with($expected);

        $this->sut->sendOrder(new Order($params), $contactListId, $grShopId);
    }

    /**
     * @return CategoryCollection
     */
    private function getCategoriesCollection()
    {
        $categoryCollection = new CategoryCollection();
        $categoryCollection->add(
            (new Category('categoryName10'))
                ->setParentId('1')
                ->setExternalId('10')
                ->setUrl('http://my-prestashop.com/category/10')
        );
        $categoryCollection->add(
            (new Category('categoryName11'))
                ->setParentId('2')
                ->setExternalId('11')
                ->setUrl('http://my-prestashop.com/category/11')
        );

        return $categoryCollection;
    }

    /**
     * @return VariantsCollection
     */
    private function getVariantsCollection1()
    {
        $variantsCollection = new VariantsCollection();
        $variantsCollection->add(
            (new Variant(
                1,
                'Tshirt with getResponse logo.',
                5.3,
                8.0,
                'this is sku number'
            ))->setQuantity(2)
            ->setUrl('http://my-prestashop.com/product/1')
            ->setDescription('Product short description')
            ->setImages($this->getImagesCollection())
        );

        return $variantsCollection;
    }

    /**
     * @return VariantsCollection
     */
    private function getVariantsCollection2()
    {
        $variantsCollection = new VariantsCollection();
        $variantsCollection->add(
            (new Variant(
                4,
                'Tshirt with getResponse logo4.',
                5.3,
                8.0,
                'this is sku number4'
            ))->setQuantity(1)
                ->setUrl('http://my-prestashop.com/product/4')
                ->setDescription('Product short description4')
                ->setImages($this->getImagesCollection())
        );

        return $variantsCollection;
    }

    /**
     * @return ImagesCollection
     */
    private function getImagesCollection()
    {
        $imagesCollection = new ImagesCollection();
        $imagesCollection->add(
            new Image('http://my-prestashop.com/images/1', 1)
        );
        $imagesCollection->add(
            new Image('http://my-prestashop.com/images/2', 2)
        );

        return $imagesCollection;
    }

    /**
     * @return Address
     */
    private function getAddress()
    {
        $address = new Address('POL', 'Adam Kowalski');
        $address->setCountryName('Poland');
        $address
            ->setFirstName('Adam')
            ->setLastName('Kowalski')
            ->setAddress1('Arkońska 24')
            ->setAddress2('Building 5')
            ->setCity('Gdańsk')
            ->setZip('81-190')
            ->setPhone('123-123-123')
            ->setCompany('GetResponse');

        return $address;
    }
}

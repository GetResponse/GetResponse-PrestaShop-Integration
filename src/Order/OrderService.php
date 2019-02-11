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

namespace GetResponse\Order;

use Configuration;
use Customer;
use GetResponse\Product\ProductFactory;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\Order\Command\AddOrderCommand as GrAddOrderCommand;
use GrShareCode\Order\OrderService as GrOrderService;
use GrShareCode\Product\ProductsCollection;
use Order;
use Product;

/**
 * Class OrderService
 * @package GetResponse\Order
 */
class OrderService
{
    /** @var GrOrderService */
    private $grOrderService;
    /** @var OrderFactory */
    private $orderFactory;

    /**
     * @param GrOrderService $grOrderService
     * @param OrderFactory $orderFactory
     */
    public function __construct(GrOrderService $grOrderService, OrderFactory $orderFactory)
    {
        $this->grOrderService = $grOrderService;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param Order $order
     * @param string $contactListId
     * @param string $grShopId
     * @throws GetresponseApiException
     */
    public function sendOrder(Order $order, $contactListId, $grShopId)
    {
        $productCollection = $this->getOrderProductsCollection($order->getProducts());

        if (!$productCollection->getIterator()->count()) {
            return;
        }

        $addOrderCommand = new GrAddOrderCommand(
            $this->orderFactory->createShareCodeOrderFromOrder($order),
            (new Customer($order->id_customer))->email,
            $contactListId,
            $grShopId
        );

        $this->grOrderService->addOrder($addOrderCommand);
    }

    /**
     * @param $products
     * @return ProductsCollection
     */
    private function getOrderProductsCollection($products)
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
                (int)$product['product_quantity'],
                Configuration::get('PS_LANG_DEFAULT')
            );

            $productsCollection->add($getresponseProduct);
        }

        return $productsCollection;
    }
}

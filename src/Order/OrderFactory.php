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
use GetResponse\Product\ProductFactory;
use GrShareCode\Address\Address as ShareCodeAddress;
use GrShareCode\Order\Order as ShareCodeOrder;
use GrShareCode\Product\ProductsCollection;

/**
 * Class ShareCodeOrderFactory
 * @package GetResponse\Order
 */
class OrderFactory
{
    /** ProductFactory */
    private $productFactory;

    public function __construct(ProductFactory $productFactory)
    {
        $this->productFactory = $productFactory;
    }

    /**
     * @param \Order $order
     * @return ShareCodeOrder
     */
    public function createShareCodeOrderFromOrder(\Order $order)
    {
        $products = $order->getProducts();

        $productsCollection = new ProductsCollection();

        foreach ($products as $product) {
            $prestashopProduct = new \Product($product['id_product']);

            $getresponseProduct = $this->productFactory->createShareCodeProductFromProduct(
                $prestashopProduct,
                (int)$product['product_quantity'],
                Configuration::get('PS_LANG_DEFAULT')
            );

            $productsCollection->add($getresponseProduct);
        }

        $shareCodeOrder = new ShareCodeOrder(
            (string)$order->id,
            (float)$order->total_paid_tax_excl,
            $this->getCurrencyIsoCode((int)$order->id_currency),
            $productsCollection
        );

        $orderUrl = \Tools::getHttpHost(true) . __PS_BASE_URI__;
        $orderUrl.= '?controller=order-detail&id_order=' . $order->id;

        $processedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $order->date_add)
            ->format(\DateTime::ISO8601);

        $shareCodeOrder->setTotalPriceTax((float)($order->total_paid_tax_incl - $order->total_paid_tax_excl));
        $shareCodeOrder->setOrderUrl($orderUrl);
        $shareCodeOrder->setStatus($this->getOrderStatus($order));
        $shareCodeOrder->setExternalCartId((string)$order->id_cart);
        $shareCodeOrder->setShippingPrice((float)$order->total_shipping_tax_incl);
        $shareCodeOrder->setProcessedAt($processedAt);

        if ($order->id_address_delivery) {
            $shareCodeOrder->setShippingAddress(
                $this->createShareCodeAddress(new \Address($order->id_address_delivery))
            );
        }

        if ($order->id_address_invoice) {
            $shareCodeOrder->setBillingAddress(
                $this->createShareCodeAddress(new \Address($order->id_address_invoice))
            );
        }

        return $shareCodeOrder;
    }

    /**
     * @param $currencyId
     * @return string
     */
    private function getCurrencyIsoCode($currencyId)
    {
        $isoCode = (new \Currency($currencyId))->iso_code;
        return !empty($isoCode) ? $isoCode : \CurrencyCore::getDefaultCurrency()->iso_code;
    }

    /**
     * @param \Order $order
     * @return string
     */
    protected function getOrderStatus(\Order $order)
    {
        $status = (new \OrderState((int)$order->getCurrentState(), $order->id_lang))->name;
        return empty($status) ? 'new' : $status;
    }

    /**
     * @param \Address $address
     * @return ShareCodeAddress
     */
    private function createShareCodeAddress(\Address $address)
    {
        $shareCodeAddress = new ShareCodeAddress(
            (new \Country($address->id_country))->iso_code,
            $address->firstname . ' ' . $address->lastname
        );
        $shareCodeAddress->setCountryName($address->country);
        $shareCodeAddress
            ->setFirstName($address->firstname)
            ->setLastName($address->lastname)
            ->setAddress1($address->address1)
            ->setAddress2($address->address2)
            ->setCity($address->city)
            ->setZip($address->postcode)
            ->setPhone($address->phone)
            ->setCompany($address->company);

        return $shareCodeAddress;
    }
}

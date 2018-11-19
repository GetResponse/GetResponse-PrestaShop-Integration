<?php
namespace GetResponse\Order;

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

            if (empty($prestashopProduct->reference)) {
                continue;
            }
            $getresponseProduct = $this->productFactory->createShareCodeProductFromProduct(
                $prestashopProduct,
                (int)$product['product_quantity']
            );

            $productsCollection->add($getresponseProduct);
        }

        $shareCodeOrder = new ShareCodeOrder(
            (string)$order->id,
            (float)$order->total_paid_tax_excl,
            $this->getCurrencyIsoCode((int)$order->id_currency),
            $productsCollection
        );

        $shareCodeOrder->setTotalPriceTax((float)($order->total_paid_tax_incl - $order->total_paid_tax_excl));
        $shareCodeOrder->setOrderUrl(\Tools::getHttpHost(true) . __PS_BASE_URI__ . '?controller=order-detail&id_order=' . $order->id);
        $shareCodeOrder->setStatus($this->getOrderStatus($order));
        $shareCodeOrder->setExternalCartId((string)$order->id_cart);
        $shareCodeOrder->setShippingPrice((float)$order->total_shipping_tax_incl);
        $shareCodeOrder->setProcessedAt(\DateTime::createFromFormat('Y-m-d H:i:s', $order->date_add)->format(\DateTime::ISO8601));

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
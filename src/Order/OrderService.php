<?php
namespace GetResponse\Order;

use GetResponse\Account\AccountService;
use GetResponse\Product\ProductService;
use GrShareCode\Address\Address as GrAddress;
use GrShareCode\Api\ApiTypeException;
use GrShareCode\GetresponseApiException;
use GrShareCode\Order\Order as GrOrder;
use GrShareCode\Order\OrderService as GrOrderService;
use GrShareCode\Product\ProductsCollection;
use GrShareCode\CountryCodeConverter as GrCountryCodeConverter;
use GrShareCode\Order\AddOrderCommand as GrAddOrderCommand;
use Link;
use Order;
use OrderState;
use PrestaShopException;
use Product;
use Tools;
use Address;
use Cart;
use Category;
use Country;
use Currency;
use Customer;
use DateTime;


/**
 * Class OrderService
 * @package GetResponse\Order
 */
class OrderService
{
    /** @var GrOrderService */
    private $grOrderService;

    /** @var AccountService */
    private $accountService;

    /**
     * @param GrOrderService $grOrderService
     * @param AccountService $accountService
     */
    public function __construct(GrOrderService $grOrderService, AccountService $accountService)
    {
        $this->grOrderService = $grOrderService;
        $this->accountService = $accountService;
    }

    /**
     * @param Order $order
     * @param string $contactListId
     * @param string $grShopId
     * @throws ApiTypeException
     * @throws GetresponseApiException
     */
    public function sendOrder(Order $order, $contactListId, $grShopId)
    {
        $products = $order->getProducts();

        $productCollection = $this->getOrderProductsCollection($products);

        if (!$productCollection->getIterator()->count()) {
            return;
        }

        $grOrder = new GrOrder(
            (string)$order->id,
            $productCollection,
            (float)$order->total_paid_tax_excl,
            (float)$order->total_paid_tax_incl,
            Tools::getHttpHost(true) . __PS_BASE_URI__ . '?controller=order-detail&id_order=' . $order->id,
            (new Currency((int)$order->id_currency))->iso_code,
            $this->getOrderStatus($order),
            (string)$order->id_cart,
            '',
            (float)$order->total_shipping_tax_incl,
            $this->getOrderStatus($order),
            DateTime::createFromFormat('Y-m-d H:i:s', $order->date_add),
            $this->getOrderShippingAddress($order),
            $this->getOrderBillingAddress($order)
        );

        $email = (new Customer($order->id_customer))->email;

        $addOrderCommand = new GrAddOrderCommand($grOrder, $email, $contactListId, $grShopId);

        $this->grOrderService->sendOrder($addOrderCommand);
    }


    /**
     * @param $products
     * @return ProductsCollection
     * @throws PrestaShopException
     */
    protected function getOrderProductsCollection($products)
    {
        $productsCollection = new ProductsCollection();

        foreach ($products as $product) {

            $prestashopProduct = new Product($product['id_product']);

            $productService = new ProductService();
            $getresponseProduct = $productService->createProductFromPrestaShopProduct($prestashopProduct,
                $product['quantity']);

            $productsCollection->add($getresponseProduct);
        }

        return $productsCollection;
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function getOrderStatus(Order $order)
    {
        $status = (new OrderState((int)$order->getCurrentState(), $order->id_lang))->name;

        return empty($status) ? 'new' : $status;
    }

    /**
     * @param Order $order
     * @return GrAddress
     */
    protected function getOrderShippingAddress(Order $order)
    {
        $address = new Address($order->id_address_delivery);
        $country = new Country($address->id_country);

        return new GrAddress(
            GrCountryCodeConverter::getCountryCodeInISO3166Alpha3($country->iso_code),
            $this->normalizeToString($country->name)
        );
    }

    /**
     * @param Order $order
     * @return GrAddress
     */
    protected function getOrderBillingAddress(Order $order)
    {
        $address = new Address($order->id_address_invoice);
        $country = new Country($address->id_country);

        return new GrAddress(
            GrCountryCodeConverter::getCountryCodeInISO3166Alpha3($country->iso_code),
            $this->normalizeToString($country->name)
        );
    }

    /**
     * @param string $text
     * @return mixed
     */
    private function normalizeToString($text)
    {
        return is_array($text) ? reset($text) : $text;
    }

}
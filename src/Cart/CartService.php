<?php

namespace GetResponse\Cart;

use Cart;
use Currency;
use Customer;
use GetResponse\Account\AccountService;
use GetResponse\Product\ProductService;
use GrShareCode\Cart\Cart as GrCart;
use GrShareCode\Cart\CartService as GrCartService;
use GrShareCode\GetresponseApiException;
use GrShareCode\Product\ProductsCollection;
use Product;
use GrShareCode\Cart\AddCartCommand as GrAddCartCommand;

/**
 * Class CartService
 */
class CartService
{
    /** @var GrCartService */
    private $grCartService;

    /**
     * @param GrCartService $grCartService
     */
    public function __construct(GrCartService $grCartService)
    {
        $this->grCartService = $grCartService;
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

        if (!$productCollection->getIterator()->count()) {
            return;
        }

        $grCart = new GrCart(
            (string)$cart->id,
            $productCollection,
            (new Currency((int)$cart->id_currency))->iso_code,
            (float)$cart->getOrderTotal(false),
            (float)$cart->getOrderTotal(true)
        );

        $customer = new Customer($cart->id_customer);
        $email = $customer->email;

        $this->grCartService->sendCart(
            new GrAddCartCommand($grCart, $email, $contactListId, $grShopId)
        );
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
            $getresponseProduct = $productService->createProductFromPrestaShopProduct($prestashopProduct, $product['quantity']);

            $productsCollection->add($getresponseProduct);
        }

        return $productsCollection;
    }
}
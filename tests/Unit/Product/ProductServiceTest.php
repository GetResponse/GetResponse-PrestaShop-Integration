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

namespace GetResponse\Tests\Unit\Product;

use GetResponse\Product\ProductImagesFactory;
use GetResponse\Product\ProductFactory;
use GetResponse\Product\ProductVariantFactory;
use GetResponse\Tests\Unit\BaseTestCase;
use Product;

/**
 * Class ProductServiceTest
 * @package GetResponse\Tests\Unit\Product
 */
class ProductServiceTest extends BaseTestCase
{

    /** @var ProductFactory */
    private $productService;

    protected function setUp()
    {
        $this->productService = new ProductFactory();
    }

    /**
     * @test
     */
    public function shouldCreateProductFromPrestashopProduct()
    {
        $languageId = 1;

        $productParams = \ProductGenerator::genProductParams(\ProductGenerator::PROD_1_WITH_SKU);

        $product = new Product(\ProductGenerator::PROD_1_WITH_SKU, $languageId);
        $quantity = 2;

        $grProduct = $this->productService->createShareCodeProductFromProduct($product, $quantity, 1);

        $this->assertEquals($productParams['id'], $grProduct->getExternalId());
        $this->assertEquals($productParams['name'][$languageId], $grProduct->getName());
        $this->assertEquals('http://my-prestashop.com/product/' . $productParams['id'], $grProduct->getUrl());

        $imagesCollection = (new ProductImagesFactory)->createFromImages(
            $product->getImages($languageId),
            $product->link_rewrite[$languageId]
        );
        $variantProduct = (new ProductVariantFactory)->createFromProduct($product, $imagesCollection, $quantity, $languageId);
        $this->assertEquals($variantProduct, $grProduct->getVariants()->getIterator()->current());
    }
}

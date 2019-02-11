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

use GetResponse\Product\ProductVariantFactory;
use GetResponse\Tests\Unit\BaseTestCase;
use GrShareCode\Product\Variant\Images\Image;
use GrShareCode\Product\Variant\Images\ImagesCollection;
use GrShareCode\Product\Variant\Variant;
use Product;

/**
 * Class ProductVariantFactoryTest
 * @package GetResponse\Tests\Unit\Product
 */
class ProductVariantFactoryTest extends BaseTestCase
{

    /** @var ProductVariantFactory */
    private $productVariantFactory;

    /**
     * @test
     */
    public function shouldCreateProduct()
    {
        $languageId = 1;

        $productParams = \ProductGenerator::genProductParams(\ProductGenerator::PROD_1_WITH_SKU);
        $product = new Product(\ProductGenerator::PROD_1_WITH_SKU);

        $imagesCollection = new ImagesCollection();
        $imagesCollection->add(new Image('source1', 1));
        $imagesCollection->add(new Image('source2', 2));
        $quantity = 2;

        $variant = $this->productVariantFactory->createFromProduct($product, $imagesCollection, $quantity, $languageId);

        $expectedVariant = new Variant(
            $productParams['id'],
            $productParams['name'][$languageId],
            $productParams['price'],
            $productParams['price_tax'],
            $productParams['reference']
        );

        $expectedVariant
            ->setQuantity($quantity)
            ->setImages($imagesCollection)
            ->setUrl('http://my-prestashop.com/product/' . $productParams['id'])
            ->setDescription($productParams['description_short'][$languageId]);

        $this->assertEquals($expectedVariant, $variant);
    }

    /**
     * @test
     */
    public function shouldCreateProductWithoutShortDescription()
    {
        $languageId = 1;

        $productParams = \ProductGenerator::genProductParams(\ProductGenerator::PROD_3_WITHOUT_SHORT_DESCRIPTION);
        $product = new Product(\ProductGenerator::PROD_3_WITHOUT_SHORT_DESCRIPTION);

        $imagesCollection = new ImagesCollection();
        $imagesCollection->add(new Image('source1', 1));
        $imagesCollection->add(new Image('source2', 2));
        $quantity = 2;

        $variant = $this->productVariantFactory->createFromProduct($product, $imagesCollection, $quantity, $languageId);

        $expectedVariant = new Variant(
            $productParams['id'],
            $productParams['name'][$languageId],
            $productParams['price'],
            $productParams['price_tax'],
            $productParams['reference']
        );

        $expectedVariant
            ->setQuantity($quantity)
            ->setImages($imagesCollection)
            ->setUrl('http://my-prestashop.com/product/' . $productParams['id']);

        $this->assertEquals($expectedVariant, $variant);
    }

    protected function setUp()
    {
        $this->productVariantFactory = new ProductVariantFactory();
    }
}

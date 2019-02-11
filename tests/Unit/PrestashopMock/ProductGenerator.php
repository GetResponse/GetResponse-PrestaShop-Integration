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
 *
 * Class ProductGenerator
 */

class ProductGenerator
{
    const PROD_1_WITH_SKU = 1;
    const PROD_2_WITH_SKU = 2;
    const PROD_3_WITHOUT_SKU = 3;
    const PROD_3_WITHOUT_SHORT_DESCRIPTION = 4;
    const LANGUAGE_ID = 1;

    private static $products = [
        1 => [
            'id' => 1,
            'id_product' => 1,
            'name' => [self::LANGUAGE_ID => 'skarpetki'],
            'reference' => 'sku-skarpetki',
            'description_short' => [self::LANGUAGE_ID => 'Desc for product skarpetki'],
            'price' => 10.0,
            'price_tax' => 12.0,
            'images' => [['id_image' => '1', 'position' => 1], ['id_image' => '2', 'position' => 2]],
            'categories' => [
                ['name' => 'categoryName10', 'id' => '10', 'id_parent' => '1'],
                ['name' => 'categoryName11', 'id' => '11', 'id_parent' => '2']
            ]
        ],
        2 => [
            'id' => 2,
            'id_product' => 2,
            'name' => [self::LANGUAGE_ID => 'czapka'],
            'reference' => 'sku-czapka',
            'description_short' => [self::LANGUAGE_ID => 'Desc for product czapka'],
            'price' => 9.95,
            'price_tax' => 11.0,
            'images' => [['id_image' => '1', 'position' => 1], ['id_image' => '2', 'position' => 2]],
            'categories' => [
                ['name' => 'categoryName10', 'id' => '10', 'id_parent' => '1'],
                ['name' => 'categoryName11', 'id' => '11', 'id_parent' => '2']
            ]
        ],
        3 => [
            'id' => 3,
            'id_product' => 3,
            'name' => [self::LANGUAGE_ID => 'ball'],
            'reference' => '',
            'description_short' => [self::LANGUAGE_ID => 'Desc for product ball'],
            'price' => 31.0,
            'price_tax' => 35.0,
            'images' => [['id_image' => '1', 'position' => 1], ['id_image' => '2', 'position' => 2]],
            'categories' => [
                ['name' => 'categoryName10', 'id' => '10', 'id_parent' => '1'],
                ['name' => 'categoryName11', 'id' => '11', 'id_parent' => '2']
            ]
        ],
        4 => [
            'id' => 4,
            'id_product' => 4,
            'name' => [self::LANGUAGE_ID => 'majtki'],
            'reference' => 'majtki',
            'description_short' => [self::LANGUAGE_ID => ''],
            'price' => 32.0,
            'price_tax' => 33.0,
            'images' => [['id_image' => '2', 'position' => 4], ['id_image' => '3', 'position' => 5]],
            'categories' => [
                ['name' => 'categoryName10', 'id' => '10', 'id_parent' => '1'],
                ['name' => 'categoryName11', 'id' => '11', 'id_parent' => '2']
            ]
        ]
    ];

    /**
     * @param int $id
     * @return array
     */
    public static function genProductParams($id)
    {
        return self::$products[$id];
    }
}

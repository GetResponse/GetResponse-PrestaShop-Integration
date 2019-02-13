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

namespace GetResponse\Product;

use GrShareCode\Product\Variant\Images\Image;
use GrShareCode\Product\Variant\Images\ImagesCollection;
use ImageType;
use Link;
use Tools;

/**
 * Class ProductImagesFactory
 * @package GetResponse\Product
 */
class ProductImagesFactory
{

    /**
     * @param array $productImages
     * @param string $productLinkRewrite
     * @return ImagesCollection
     */
    public function createFromImages(array $productImages, $productLinkRewrite)
    {
        $imagesCollection = new ImagesCollection();

        foreach ($productImages as $productImage) {
            $imagePath = (new Link())->getImageLink(
                $productLinkRewrite,
                $productImage['id_image'],
                ImageType::getFormatedName('home')
            );
            $protocol = Tools::getProtocol(Tools::usingSecureMode());
            $imagesCollection->add(new Image($protocol . $imagePath, (int)$productImage['position']));
        }

        return $imagesCollection;
    }
}

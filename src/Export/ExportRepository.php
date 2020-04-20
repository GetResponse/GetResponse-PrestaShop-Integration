<?php
/**
 * 2007-2020 PrestaShop
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
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace GetResponse\Export;

use Db;

/**
 * Class ExportRepository
 * @package GetResponse\Export
 */
class ExportRepository
{

    /** @var Db */
    private $db;

    /** @var int */
    private $idShop;

    /**
     * @param Db $db
     * @param int $shopId
     */
    public function __construct($db, $shopId)
    {
        $this->db = $db;
        $this->idShop = $shopId;
    }

    /**
     * @param bool $newsletterGuests
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public function getContacts($newsletterGuests = false)
    {
        if (version_compare(_PS_VERSION_, '1.7') === -1) {
            $newsletterTableName = _DB_PREFIX_ . 'newsletter';
            $newsletterModule = 'blocknewsletter';
        } else {
            $newsletterTableName = _DB_PREFIX_ . 'emailsubscription';
            $newsletterModule = _DB_PREFIX_ . 'emailsubscription';
        }
        $ngWhere = '';

        if ($newsletterGuests && $this->checkModuleStatus($newsletterModule)) {
            $ngWhere = 'UNION SELECT
                    0 as id,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    n.email as email
                FROM
                    ' . $newsletterTableName . ' n
                WHERE
                    n.active = 1
                AND
                    id_shop = ' . (int) $this->idShop . '
            ';
        }

        $sql = 'SELECT
                    cu.id_customer as id,
                    cu.firstname,
                    cu.lastname,
                    cu.birthday,
                    concat(ad.address1, \' \', ad.address2) as address,
                    ad.postcode as postal,
                    cu.company,
                    pl.name as country,
                    ad.city,
                    ad.phone,
                    cu.email as email
                FROM
                    ' . _DB_PREFIX_ . 'customer as cu
                LEFT JOIN
                    ' . _DB_PREFIX_ . 'address ad ON cu.id_customer = ad.id_customer
                LEFT JOIN
                    ' . _DB_PREFIX_ . 'country co ON ad.id_country = co.id_country
                LEFT JOIN
                	' . _DB_PREFIX_ . 'country_lang pl on co.id_country = pl.id_country AND pl.id_lang = 1
                WHERE
                    cu.newsletter = 1
                    AND cu.deleted = 0
                AND
                    cu.id_shop = ' . (int) $this->idShop . '
                    GROUP BY cu.email
                ' . $ngWhere;

        $contacts = $this->db->executeS($sql);

        if (empty($contacts)) {
            return [];
        }

        foreach ($contacts as $id => $contact) {
            $contacts[$id]['category'] = $this->getContactCategory($contact['email']);
        }
        return $contacts;
    }


    /**
     * @param string $moduleName
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function checkModuleStatus($moduleName)
    {
        if (empty($moduleName)) {
            return false;
        }

        $sql = '
        SELECT
            `active`
        FROM
            ' . _DB_PREFIX_ . 'module
        WHERE
            `name` = "' . pSQL($moduleName) . '"';

        if ($results = $this->db->executeS($sql)) {
            if (isset($results[0]['active']) && 1 === (int) $results[0]['active']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $email
     * @return string
     * @throws \PrestaShopDatabaseException
     */
    private function getContactCategory($email)
    {
        $sql = '
        SELECT
            group_concat(DISTINCT cp.`id_category` separator ", ") as category
        FROM
            ' . _DB_PREFIX_ . 'customer as cu
        LEFT JOIN
            ' . _DB_PREFIX_ . 'address ad ON cu.`id_customer` = ad.`id_customer`
        LEFT JOIN
            ' . _DB_PREFIX_ . 'country co ON ad.`id_country` = co.`id_country`
        LEFT JOIN
            ' . _DB_PREFIX_ . 'orders o ON o.`id_customer` = cu.`id_customer`
        LEFT JOIN
            ' . _DB_PREFIX_ . 'order_detail od ON (od.`id_order` = o.`id_order` 
            AND o.`id_shop` = ' . (int) $this->idShop . ')
        LEFT JOIN
            ' . _DB_PREFIX_ . 'category_product cp ON (cp.`id_product` = od.`product_id` 
            AND od.`id_shop` = ' . (int) $this->idShop . ')
        LEFT JOIN
            ' . _DB_PREFIX_ . 'category_lang cl ON (cl.`id_category` = cp.`id_category` 
            AND cl.`id_shop` = ' .
            (int) $this->idShop . ' AND cl.`id_lang` = cu.`id_lang`)
        WHERE
            cu.`newsletter` = 1
            AND cu.`email` = "' . pSQL($email) . '"
            AND cu.`id_shop` = ' . (int) $this->idShop;

        $categories = $this->db->executeS($sql);

        if (empty($categories)) {
            return '';
        }
        return $categories[0]['category'];
    }

    /**
     * @param $customerId
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public function getOrders($customerId)
    {
        $sql = '
        SELECT
            `id_order`,
            `id_cart`
        FROM
            ' . _DB_PREFIX_ . 'orders
        WHERE
            `id_shop` = ' . (int) $this->idShop . ' AND
            `id_customer` = ' . (int) $customerId;

        if ($results = $this->db->executeS($sql)) {
            return $results;
        }

        return array();
    }
}

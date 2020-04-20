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

use GetResponse\Ecommerce\Ecommerce;
use GetResponse\Ecommerce\EcommerceService;
use GetResponse\Ecommerce\EcommerceServiceFactory;
use GetResponse\Ecommerce\EcommerceValidator;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\Shop\Command\AddShopCommand;
use GrShareCode\Shop\Command\DeleteShopCommand;
use GrShareCode\Shop\Shop;

require_once 'AdminGetresponseController.php';

class AdminGetresponseEcommerceController extends AdminGetresponseController
{
    /** @var EcommerceService */
    private $ecommerceService;

    /**
     * @throws PrestaShopException
     * @throws ApiTypeException
     * @throws GetresponseApiException
     */
    public function __construct()
    {
        parent::__construct();
        $this->addJquery();
        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/gr-ecommerce.js');
        $this->name = 'GREcommerce';
        $this->ecommerceService = EcommerceServiceFactory::create();
    }

    public function initPageHeaderToolbar()
    {
        if (!in_array($this->display, ['edit', 'add'])) {
            $this->page_header_toolbar_btn['new_shop'] = [
                'href' => self::$currentIndex . '&action=add&token=' . $this->getToken(),
                'desc' => $this->l('Add new shop', null, null, false),
                'icon' => 'process-icon-new'
            ];
        }
        parent::initPageHeaderToolbar();
    }

    /**
     * Get Admin Token
     * @return string
     */
    public function getToken()
    {
        return Tools::getAdminTokenLite('AdminGetresponseEcommerce');
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('GetResponse');
        $this->toolbar_title[] = $this->l('GetResponse Ecommerce');
    }

    /**
     * @return bool|ObjectModel|void
     * @throws GetresponseApiException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('delete' . $this->name)) {
            $this->ecommerceService->deleteShop(new DeleteShopCommand(Tools::getValue('shopId')));
            $this->confirmations[] = $this->l('Store removed');
        }

        if (Tools::isSubmit('submit' . $this->name) && Tools::getValue('ecommerce') !== false) {
            $ecommerce = Ecommerce::createFromPost(Tools::getAllValues());
            $validator = new EcommerceValidator($ecommerce);

            if (!$validator->isValid()) {
                $this->errors = $validator->getErrors();

                return;
            }

            $this->ecommerceService->updateEcommerceDetails($ecommerce);
            $this->confirmations[] = $this->l('Ecommerce settings saved');
        }


        if (Tools::getValue('action') == 'add') {
            $this->display = 'add';

            if (Tools::isSubmit('submit' . $this->name) && Tools::getValue('form_name') == 'add_store') {
                $shopName = Tools::getValue('shop_name');

                if (empty($shopName)) {
                    $this->errors[] = $this->l('Store name can not be empty');
                    return;
                }

                try {
                    $this->ecommerceService->createShop(
                        new AddShopCommand(
                            $shopName,
                            $this->context->language->iso_code,
                            $this->context->currency->iso_code
                        )
                    );
                    $this->display = 'list';
                    $this->confirmations[] = $this->l('Store added');
                } catch (GetresponseApiException $e) {
                    $this->display = 'add';
                    $this->errors[] = $this->l($e->getMessage());
                }
            }
        }
        parent::postProcess();
    }

    /**
     * @return string
     * @throws GetresponseApiException
     */
    public function renderList()
    {
        return $this->generateForm() . $this->generateShopList();
    }

    /**
     * @return string
     * @throws GetresponseApiException
     */
    private function generateForm()
    {
        $settings = $this->ecommerceService->getEcommerceSettings();
        $shops = [['shopId' => '', 'name' => $this->l('Select a store')]];

        /** @var Shop $shop */
        foreach ($this->ecommerceService->getAllShops() as $shop) {
            $shops[] = [
                'shopId' => $shop->getId(),
                'name' => $shop->getName(),
            ];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l((!$settings->isEnabled() ? 'Enable ' : '') . 'GetResponse Ecommerce')
                ],
                'description' =>
                    $this->l(
                        'GetResponse helps you track and collect ecommerce data. 
                        You can stay informed about customers’ behaviour and spending habits.'
                    ) . '<br>' .
                    $this->l(
                        'Use this data to create marketing automation workflows that react to 
                        purchases, abandoned carts, or the amounts of money spent.'
                    ),
                'input' => [
                    [
                        'label' => $this->l('Send ecommerce data to GetResponse'),
                        'name' => 'ecommerce',
                        'type' => 'switch',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'ecommerce_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'ecommerce_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Shop'),
                        'class' => 'gr-select',
                        'name' => 'shop',
                        'required' => true,
                        'options' => [
                            'query' => $shops,
                            'id' => 'shopId',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Target list'),
                        'class' => 'gr-select',
                        'name' => 'list',
                        'required' => true,
                        'options' => [
                            'query' => $this->getCampaignsOptions(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'name' => 'EcommerceConfiguration'
                    ]
            ]
        ];

        /** @var HelperFormCore $helper */
        $helper = new HelperForm();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submit' . $this->name;
        $helper->token = $this->getToken();
        $helper->title = $this->l('Enable GetResponse Ecommerce');
        $helper->fields_value = ['ecommerce' => 0, 'shop' => '', 'listId' => ''];

        if ($settings->isEnabled()) {
            $helper->fields_value = [
                'ecommerce' => 1,
                'shop' => $settings->getShopId(),
                'list' => $settings->getListId()
            ];
        } else {
            $helper->fields_value = [
                'ecommerce' => 0,
                'shop' => null,
                'list' => null
            ];
        }

        return $helper->generateForm(array($fieldsForm));
    }

    /**
     * @return string
     * @throws GetresponseApiException
     */
    private function generateShopList()
    {
        /** @var HelperListCore $helper */
        $helper = new HelperList();
        $helper->no_link = true;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'shopId';
        $helper->actions = array('delete');
        $helper->title = $this->l('Stores');
        $helper->table = $this->name;
        $helper->token = $this->getToken();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        $fieldsList = [
            'shopId' => [
                'title' => $this->l('ID'),
                'type' => 'text'
            ],
            'name' => [
                'title' => $this->l('Shop name'),
                'type' => 'text'
            ]
        ];

        $shopList = [];

        /** @var Shop $shop */
        foreach ($this->ecommerceService->getAllShops() as $shop) {
            $shopList[] = [
                'shopId' => $shop->getId(),
                'name' => $shop->getName(),
            ];
        }

        return $helper->generateList($shopList, $fieldsList);
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        $fieldsForm = [
        'form' => [
            'legend' => [
                'title' => $this->l('Add new store'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Store name'),
                    'required' => true,
                    'name' => 'shop_name',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'form_name'
                ],
                [
                    'type' => 'hidden',
                    'name' => 'back_url'
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'name' => 'NewAutomationConfiguration'
            ],
            'reset' => [
                'title' => $this->l('Cancel'),
                'icon' => 'process-icon-cancel'
            ],
            'show_cancel_button' => true
        ]];

        /** @var HelperFormCore $helper */
        $helper = new HelperForm();
        $helper->currentIndex = AdminController::$currentIndex . '&action=add';
        $helper->submit_action = 'submit' . $this->name;
        $helper->token = $this->getToken();

        $helper->fields_value = [
            'shop_name' => '',
            'form_name' => 'add_store',
            'back_url' => self::$currentIndex . '&token=' . $this->getToken(),
        ];

        return $helper->generateForm([$fieldsForm]);
    }
}

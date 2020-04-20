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

require_once 'AdminGetresponseController.php';

use GetResponse\ContactList\ContactListService;
use GetResponse\ContactList\ContactListServiceFactory;
use GetResponse\CustomFieldsMapping\CustomFieldMappingCollection;
use GetResponse\Ecommerce\EcommerceService;
use GetResponse\Ecommerce\EcommerceServiceFactory;
use GetResponse\Export\ExportServiceFactory;
use GetResponse\Export\ExportSettings;
use GetResponse\Export\ExportValidator;
use GetResponse\Helper\FlashMessages;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\ContactList\ContactList;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\Shop\Shop;

class AdminGetresponseExportController extends AdminGetresponseController
{
    /** @var ContactListService */
    public $contactListService;

    /** @var EcommerceService */
    private $ecommerceService;

    /**
     * @throws ApiTypeException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        $this->addJquery();
        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/gr-export.js');
        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/gr-custom-fields.js');
        $this->contactListService = ContactListServiceFactory::create();
        $this->ecommerceService = EcommerceServiceFactory::create();
        $this->name = 'AdminGetresponseExport';
    }

    public function initContent()
    {
        $this->display = 'view';
        $this->toolbar_title[] = $this->l('GetResponse');
        $this->toolbar_title[] = $this->l('Export Customer Data on Demand');
        parent::initContent();
    }

    public function initPageHeaderToolbar()
    {
        $link = (new LinkCore())->getAdminLink('AdminGetresponseAddNewContactList');
        $link .= '&referer=' . $this->controller_name;

        $this->page_header_toolbar_btn['add_campaign'] = [
            'href' => $link,
            'desc' => $this->l('Add new contact list'),
            'icon' => 'process-icon-new'
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * @return bool|ObjectModel|void
     * @throws GetresponseApiException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ApiTypeException
     */
    public function postProcess()
    {
        if (Tools::isSubmit($this->name)) {
            $exportSettings = new ExportSettings(
                Tools::getValue('campaign'),
                Tools::getValue('addToCycle_1', 0) == 1 ? Tools::getValue('autoresponder_day', null) : null,
                $this->getCustomFieldsFromPost(),
                Tools::getValue('newsletter', 0) == 1,
                Tools::getValue('exportEcommerce_1', 0) == 1,
                Tools::getValue('shop')
            );

            $validator = new ExportValidator($exportSettings);
            if (!$validator->isValid()) {
                $this->errors = $validator->getErrors();
                return;
            }

            $exportService = ExportServiceFactory::create();
            $exportService->export($exportSettings);

            FlashMessages::add(FlashMessages::TYPE_CONFIRMATION, $this->l('Customer data exported'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGetresponseExport'));
        }
        parent::postProcess();
    }


    /**
     * Render main view
     * @return string
     * @throws GetresponseApiException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderView()
    {
        $this->context->smarty->assign([
            'selected_tab' => 'export_customers',
            'export_customers_form' => $this->renderExportForm(),
            //'export_customers_list' => $this->renderCustomList(),
            'campaign_days' => json_encode($this->getCampaignDays($this->contactListService->getAutoresponders())),
            'cycle_day' => null,
            'token' => $this->getToken(),
        ]);

        return parent::renderView();
    }

    /**
     * @return string
     * @throws GetresponseApiException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderExportForm()
    {
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
                    'title' => $this->l('Export Your Customer Information From PrestaShop to your GetResponse Account')
                ],
                'description' => $this->l('Use this option for one time export of your existing customers.'),
                'input' => [
                    ['type' => 'hidden', 'name' => 'autoresponders'],
                    ['type' => 'hidden', 'name' => 'cycle_day_selected'],
                    [
                        'type' => 'select',
                        'name' => 'campaign',
                        'required' => true,
                        'label' => $this->l('Contact list'),
                        'options' => [
                            'query' => $this->getCampaigns(),
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'label' => $this->l('Include newsletter subscribers'),
                        'name' => 'newsletter',
                        'type' => 'switch',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'newsletter_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'newsletter_off', 'value' => 0, 'label' => $this->l('No')]
                        ]
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => '',
                        'name' => 'addToCycle',
                        'values' => [
                            'query' => [
                                ['id' => 1, 'val' => 1, 'name' => $this->l(' Add to autoresponder cycle')]
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Autoresponder day'),
                        'class' => 'gr-select',
                        'name' => 'autoresponder_day',
                        'data-default' => $this->l('no autoresponders'),
                        'options' => [
                            'query' => [['id' => '', 'name' => $this->l('no autoresponders')]],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => '',
                        'name' => 'exportEcommerce',
                        'values' => [
                            'query' => [
                                [
                                    'id' => 1,
                                    'val' => 1,
                                    'name' => $this->l(' Include ecommerce data in this export')
                                ]
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Store'),
                        'class' => 'gr-select',
                        'name' => 'shop',
                        'required' => false,
                        'options' => [
                            'query' => $shops,
                            'id' => 'shopId',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'label' => $this->l('Update contacts info'),
                        'name' => 'contactInfo',
                        'type' => 'switch',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'update_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'update_off', 'value' => 0, 'label' => $this->l('No')]
                        ],
                        'desc' =>
                            $this->l('
                                Select this option if you want to overwrite contact details that 
                                already exist in your GetResponse database.
                            ') .
                            '<br>' .
                            $this->l('Clear this option to keep existing data intact.')
                    ],
                    [
                        'type' => 'html',
                        'name' => 'customs',
                        'html_content' => $this->renderCustomList(new CustomFieldMappingCollection()),
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Export'),
                    'icon' => 'process-icon-download',
                    'name' => $this->name
                ]
            ]
        ];

        /** @var HelperFormCore $helper */
        $helper = new HelperFormCore();
        $helper->currentIndex = AdminController::$currentIndex;
        $helper->token = $this->getToken();
        $helper->fields_value = [
            'campaign' => false,
            'autoresponder_day' => false,
            'contactInfo' => Tools::getValue('mapping', 0),
            'newsletter' => 0,
            'autoresponders' => json_encode([]),
            'cycle_day_selected' => 0,
            'shop' => Tools::getValue('shop')
        ];

        return $helper->generateForm([$fieldsForm]) . $this->renderList();
    }

    /**
     * @return array
     * @throws GetresponseApiException
     */
    private function getCampaigns()
    {
        $campaigns = [
            [
                'id' => 0,
                'name' => $this->l('Select a list')
            ]
        ];

        /** @var ContactList $contactList */
        foreach ($this->contactListService->getContactLists() as $contactList) {
            $campaigns[] = [
                'id' => $contactList->getId(),
                'name' => $contactList->getName()
            ];
        }

        return $campaigns;
    }

    /**
     * Get Admin Token
     * @return string
     */
    public function getToken()
    {
        return Tools::getAdminTokenLite('AdminGetresponseExport');
    }
}

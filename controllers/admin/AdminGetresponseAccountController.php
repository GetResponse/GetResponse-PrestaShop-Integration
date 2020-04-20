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

use GetResponse\Account\AccountDto;
use GetResponse\Account\AccountServiceFactory;
use GetResponse\Account\AccountSettings;
use GetResponse\Account\AccountValidator;
use GetResponse\WebTracking\TrackingCodeServiceFactory;
use GetResponse\WebTracking\WebTracking;
use GetResponse\WebTracking\WebTrackingServiceFactory;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\Api\Exception\GetresponseApiException;

require_once 'AdminGetresponseController.php';

class AdminGetresponseAccountController extends AdminGetresponseController
{
    public function __construct()
    {
        parent::__construct();

        $this->meta_title = $this->l('GetResponse Integration');
        $this->identifier = 'AdminGetresponseAccountController';

        $this->context->smarty->assign(array(
            'gr_tpl_path' => _PS_MODULE_DIR_ . 'getresponse/views/templates/admin/',
            'action_url' => $this->context->link->getAdminLink('AdminGetresponseAccount'),
            'base_url',
            __PS_BASE_URI__
        ));
    }

    public function initContent()
    {
        try {
            $accountService = AccountServiceFactory::create();
            $this->display = $accountService->isConnectedToGetResponse() ? 'view' : 'edit';
            $this->show_form_cancel_button = false;
            parent::initContent();
        } catch (GetresponseApiException $e) {
            $this->display = 'edit';
            $this->show_form_cancel_button = false;
            parent::initContent();
        }
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('GetResponse Account');
    }

    /**
     * @return bool|ObjectModel|void
     * @throws ApiTypeException
     * @throws GetresponseApiException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('connectToGetResponse')) {
            $this->connectToGetResponse();
        } elseif (Tools::isSubmit('disconnectFromGetResponse')) {
            try {
                $accountService = AccountServiceFactory::create();
                $accountService->disconnectFromGetResponse();
                $this->confirmations[] = $this->l('GetResponse account disconnected');
            } catch (ApiTypeException $e) {
                $this->display = 'edit';
                $this->show_form_cancel_button = false;
            }
        }
        parent::postProcess();
    }


    private function connectToGetResponse()
    {
        $accountDto = AccountDto::fromRequest([
            'apiKey' => Tools::getValue('api_key'),
            'enterprisePackage' => Tools::getValue('is_enterprise'),
            'domain' => Tools::getValue('domain'),
            'accountType' => Tools::getValue('account_type')
        ]);

        $validator = new AccountValidator($accountDto);
        if (!$validator->isValid()) {
            $this->errors = $validator->getErrors();

            return;
        }

        try {
            $accountService = AccountServiceFactory::createFromAccountDto($accountDto);

            if ($accountService->isConnectionAvailable()) {
                $accountService->updateApiSettings(
                    $accountDto->getApiKey(),
                    $accountDto->getAccountTypeForSettings(),
                    $accountDto->getDomain()
                );

                $webTrackingService = WebTrackingServiceFactory::create();
                $trackingCodeService = TrackingCodeServiceFactory::create();
                $trackingCode = $trackingCodeService->getTrackingCode();

                $trackingStatus = $trackingCode->isFeatureEnabled()
                    ? WebTracking::TRACKING_INACTIVE
                    : WebTracking::TRACKING_DISABLED;

                $webTrackingService->saveTracking(new WebTracking($trackingStatus));
                $this->confirmations[] = $this->l('GetResponse account connected');
            } else {
                $msg = !$accountDto->isEnterprisePackage()
                    ? 'The API key or domain seems incorrect.'
                    : 'The API key seems incorrect.';

                $msg .= ' Please check if you typed or pasted it correctly.
                    If you recently generated a new key, please make sure you are using the right one';

                $this->errors[] = $this->l($msg);
            }
        } catch (GetresponseApiException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * @return mixed
     * @throws ApiTypeException
     * @throws GetresponseApiException
     * @throws PrestaShopDatabaseException
     */
    public function renderView()
    {
        $accountService = AccountServiceFactory::create();

        if ($accountService->isConnectedToGetResponse()) {
            $accountDetails = $accountService->getAccountDetails();

            $this->context->smarty->assign([
                'gr_acc_name' => $accountDetails->getFullName(),
                'gr_acc_email' => $accountDetails->getEmail(),
                'gr_acc_company' => $accountDetails->getCompanyName(),
                'gr_acc_phone' => $accountDetails->getPhone(),
                'gr_acc_address' => $accountDetails->getFullAddress(),
            ]);
        }

        $accountSettings = AccountServiceFactory::create()->getAccountSettings();
        $webTrackingService = WebTrackingServiceFactory::create();

        $this->context->smarty->assign([
            'selected_tab' => 'api',
            'is_connected' => $accountService->isConnectedToGetResponse(),
            'active_tracking' => $webTrackingService->getWebTracking()->isTrackingActive(),
            'api_key' => $accountSettings->getHiddenApiKey()
        ]);

        return parent::renderView();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Connect your site and GetResponse'),
                    'icon' => 'icon-gears'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API key'),
                        'name' => 'api_key',
                        'desc' =>
                            $this->l(
                                'Your API key is part of the settings of your GetResponse account.
                            Log in to GetResponse and go to'
                            ) .
                            ' <strong> ' . $this->l('My profile > Integration & API > API') . ' </strong> ' .
                            $this->l('to find the key')
                        ,
                        'empty_message' => $this->l('You need to enter API key. This field can\'t be empty.'),
                        'required' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enterprise package'),
                        'name' => 'is_enterprise',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->l('Account type'),
                        'name' => 'account_type',
                        'required' => false,
                        'values' => [
                            [
                                'id' => 'account_pl',
                                'value' => AccountSettings::ACCOUNT_TYPE_360_PL,
                                'label' => $this->l('GetResponse Enterprise PL')
                            ],
                            [
                                'id' => 'account_en',
                                'value' => AccountSettings::ACCOUNT_TYPE_360_US,
                                'label' => $this->l('GetResponse Enterprise COM')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Your domain'),
                        'name' => 'domain',
                        'required' => false,
                        'id' => 'domain',
                        'desc' => $this->l('Enter your domain without protocol (https://) eg: "example.com"'),
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'action',
                        'values' => 'api',
                        'default' => 'api'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Connect'),
                    'name' => 'connectToGetResponse',
                    'icon' => 'icon-getresponse-connect icon-link'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->submit_action = 'connectToGetResponse';
        $helper->token = Tools::getAdminTokenLite('AdminGetresponseAccount');
        $helper->fields_value = [
            'api_key' => Tools::getValue('api_key'),
            'is_enterprise' => Tools::getValue('is_enterprise'),
            'domain' => Tools::getValue('domain'),
            'account_type' => Tools::getValue('account_type'),
            'action' => 'api',
        ];

        return $helper->generateForm(array($fields_form));
    }
}

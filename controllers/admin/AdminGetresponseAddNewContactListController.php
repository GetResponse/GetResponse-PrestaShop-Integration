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

use GetResponse\ContactList\AddContactListDto;
use GetResponse\ContactList\AddContactListValidator;
use GetResponse\ContactList\ContactListService;
use GetResponse\ContactList\ContactListServiceFactory;
use GetResponse\Helper\FlashMessages;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\ContactList\FromFields;
use GrShareCode\ContactList\SubscriptionConfirmation\SubscriptionConfirmationBody;
use GrShareCode\ContactList\SubscriptionConfirmation\SubscriptionConfirmationSubject;
use GrShareCode\Api\Exception\GetresponseApiException;

class AdminGetresponseAddNewContactListController extends AdminGetresponseController
{
    /** @var ContactListService */
    private $contactListService;

    /**
     * @throws PrestaShopException
     * @throws ApiTypeException
     */
    public function __construct()
    {
        parent::__construct();
        $this->addJquery();
        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/gr-registration.js');
        $this->name = 'GRAddNewContactList';
        $this->context->smarty->assign([
            'gr_tpl_path' => _PS_MODULE_DIR_ . 'getresponse/views/templates/admin/',
            'action_url' => $this->context->link->getAdminLink('AdminGetresponseSubscribeRegistration'),
            'base_url',
            __PS_BASE_URI__
        ]);

        $this->contactListService = ContactListServiceFactory::create();
    }

    public function initContent()
    {
        $this->display = 'view';
        $this->toolbar_title[] = $this->l('GetResponse');
        $this->toolbar_title[] = $this->l('Add New Contact list');
        parent::initContent();
    }

    /**
     * @throws GetresponseApiException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('addCampaignForm')) {
            $addContactListDto = new AddContactListDto(
                Tools::getValue('campaign_name'),
                Tools::getValue('from_field'),
                Tools::getValue('replyto'),
                Tools::getValue('subject'),
                Tools::getValue('body')
            );

            $validator = new AddContactListValidator($addContactListDto);
            if (!$validator->isValid()) {
                $this->errors = $validator->getErrors();
                return;
            }

            try {
                $this->contactListService->createContactList($addContactListDto, $this->context->language->iso_code);
                FlashMessages::add(FlashMessages::TYPE_CONFIRMATION, $this->l('List created'));
                Tools::redirectAdmin($this->context->link->getAdminLink(Tools::getValue('referer')));
            } catch (GrApiException $e) {
                $this->errors[] = $this->l('Contact list could not be added! (' . $e->getMessage() . ')');
            }
        }
    }

    /**
     * @return string
     * @throws GetresponseApiException
     */
    public function renderView()
    {
        $this->context->smarty->assign([
            'selected_tab' => 'subscribe_via_registration',
            'token' => $this->getToken(),
            'subscribe_via_registration_form' => $this->renderAddContactListForm()
        ]);

        return parent::renderView();
    }

    /**
     * Get Admin Token
     * @return string
     */
    public function getToken()
    {
        return Tools::getAdminTokenLite('AdminGetresponseAddNewContactList');
    }

    /**
     * @return string
     * @throws GetresponseApiException
     */
    public function renderAddContactListForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Add new contact list'),
                    'icon' => 'icon-gears'
                ],
                'input' => [
                    'contact_list' => [
                        'label' => $this->l('List name'),
                        'name' => 'campaign_name',
                        'hint' => $this->l('You need to enter a name that\'s at least 3 characters long'),
                        'type' => 'text',
                        'required' => true
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('From field'),
                        'name' => 'from_field',
                        'required' => true,
                        'options' => [
                            'query' => $this->getOptionForFromFields(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Reply-to'),
                        'name' => 'replyto',
                        'required' => true,
                        'options' => [
                            'query' => $this->getOptionForReplayTo(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Confirmation subject'),
                        'name' => 'subject',
                        'required' => true,
                        'options' => [
                            'query' => $this->getOptionForSubject(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Confirmation body'),
                        'name' => 'body',
                        'required' => true,
                        'desc' =>
                            $this->l(
                                'The confirmation message body and subject depend on System >> 
                            Configuration >> General >> Locale Options.'
                            ) .
                            '<br>' .
                            $this->l(
                                'By default all lists you create in Prestashop have double opt-in enabled.
                            You can change this later in your list settings.'
                            ),
                        'options' => [
                            'query' => $this->getOptionForBody(),
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'addCampaignForm',
                    'icon' => 'process-icon-save'
                ]
            ]
        ];

        /** @var HelperFormCore $helper */
        $helper = new HelperForm();
        $helper->currentIndex = AdminController::$currentIndex . '&referer=' . Tools::getValue('referer');
        $helper->token = $this->getToken();
        $helper->fields_value = [
            'campaign_name' => Tools::getValue('campaign_name'),
            'from_field' => Tools::getValue('from_field'),
            'replyto' => Tools::getValue('replyto'),
            'subject' => Tools::getValue('subject'),
            'body' => Tools::getValue('body'),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @return array
     * @throws GetresponseApiException
     */
    private function getOptionForFromFields()
    {
        $options = [[
            'id_option' => '',
            'name' => $this->l('Select from field')
        ]];

        /** @var FromFields $fromField */
        foreach ($this->contactListService->getFromFields() as $fromField) {
            $options[] = [
                'id_option' => $fromField->getId(),
                'name' => $fromField->getName() . '(' . $fromField->getEmail() . ')'
            ];
        }

        return $options;
    }

    /**
     * @return array
     * @throws GetresponseApiException
     */
    private function getOptionForReplayTo()
    {
        $options= [[
            'id_option' => '',
            'name' => $this->l('Select reply-to address')
        ]];

        /** @var FromFields $fromField */
        foreach ($this->contactListService->getFromFields() as $fromField) {
            $options[] = [
                'id_option' => $fromField->getId(),
                'name' => $fromField->getName() . '(' . $fromField->getEmail() . ')'
            ];
        }

        return $options;
    }

    /**
     * @return array
     * @throws GetresponseApiException
     */
    private function getOptionForSubject()
    {
        $options = [[
            'id_option' => '',
            'name' => $this->l('Select confirmation message subject')
        ]];

        /** @var SubscriptionConfirmationSubject $subject */
        foreach ($this->contactListService->getSubscriptionConfirmationSubject() as $subject) {
            $options[] = [
                'id_option' => $subject->getId(),
                'name' => $subject->getSubject()
            ];
        }

        return $options;
    }

    /**
     * @return array
     * @throws GetresponseApiException
     */
    public function getOptionForBody()
    {
        $options = [[
            'id_option' => '',
            'name' => $this->l('Select confirmation message body template')
        ]];

        /** @var SubscriptionConfirmationBody $confirmationBody */
        foreach ($this->contactListService->getSubscriptionConfirmationBody() as $confirmationBody) {
            $options[] = [
                'id_option' => $confirmationBody->getId(),
                'name' => $confirmationBody->getName() . ' ' . $confirmationBody->getContentPlain()
            ];
        }

        return $options;
    }
}

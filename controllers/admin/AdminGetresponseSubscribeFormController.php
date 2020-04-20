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

use GetResponse\WebForm\WebForm;
use GetResponse\WebForm\WebFormServiceFactory;
use GetResponse\WebForm\WebFormValidator;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\WebForm\FormNotFoundException;
use GrShareCode\WebForm\WebForm as GetResponseForm;
use GrShareCode\WebForm\WebFormCollection;

require_once 'AdminGetresponseController.php';

class AdminGetresponseSubscribeFormController extends AdminGetresponseController
{
    /** @var \GetResponse\WebForm\WebFormService */
    private $webFormService;

    /** @var WebFormCollection */
    private $getResponseWebFormCollection;

    /**
     * AdminGetresponseSubscribeFormController constructor.
     * @throws PrestaShopException
     * @throws ApiTypeException
     * @throws GetresponseApiException
     */
    public function __construct()
    {
        parent::__construct();
        $this->addJquery();
        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/gr-webform.js');

        $this->context->smarty->assign(array(
            'gr_tpl_path' => _PS_MODULE_DIR_ . 'getresponse/views/templates/admin/',
            'action_url' => $this->context->link->getAdminLink('AdminGetresponseSubscribeForm'),
            'selected_tab' => 'subscribe_via_registration'
        ));

        $this->webFormService = WebFormServiceFactory::create();
        $this->getResponseWebFormCollection = $this->webFormService->getGetResponseFormCollection();
    }

    public function initContent()
    {
        $this->display = 'edit';
        $this->show_form_cancel_button = false;
        $this->toolbar_title[] = $this->l('GetResponse');
        $this->toolbar_title[] = $this->l('Add Contacts via GetResponse Forms');

        parent::initContent();
    }

    /**
     * @return bool|ObjectModel|void
     * @throws GetresponseApiException
     * @throws FormNotFoundException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitSubscribeForm')) {
            $webForm = WebForm::createFromPost(Tools::getAllValues());

            $validator = new WebFormValidator($webForm);
            if (!$validator->isValid()) {
                $this->errors = $validator->getErrors();

                return;
            }

            $this->webFormService->updateWebForm($webForm);

            $this->confirmations[] = $webForm->isActive()
                ? $this->l('Form published')
                : $this->l('Form unpublished');
        }
        parent::postProcess();
    }

    public function renderForm()
    {
        $helper = new HelperForm();
        $helper->submit_action = 'submitSubscribeForm';
        $helper->token = Tools::getAdminTokenLite('AdminGetresponseSubscribeForm');
        $helper->tpl_vars = array(
            'fields_value' => $this->getFormFieldsValue()
        );

        $optionList = $this->getFormsOptions();

        return $helper->generateForm(array($this->getFormFields($optionList)));
    }

    /**
     * @return array
     */
    private function getFormFieldsValue()
    {
        $webForm = $this->webFormService->getWebForm();

        return [
            'position' => $webForm->getSidebar(),
            'form' => $webForm->getId(),
            'style' => $webForm->getStyle(),
            'subscription' => $webForm->getStatus() === WebForm::STATUS_ACTIVE ? 1 : 0
        ];
    }

    /**
     * @param WebFormCollection $webforms
     * @return array
     */
    private function getFormsOptions()
    {
        $options = [
            [
                'id_option' => '',
                'name' => 'Select a form you want to display'
            ]
        ];

        /** @var GetResponseForm $form */
        foreach ($this->getResponseWebFormCollection as $form) {
            $disabled = $form->isEnabled() ? '' : $this->l('(DISABLED IN GR)');
            $options[] = [
                'id_option' => $form->getWebFormId(),
                'name' => $form->getName() . ' (' . $form->getCampaignName() . ') ' . $disabled
            ];
        }

        return $options;
    }

    /**
     * @param array $options
     * @return array
     */
    private function getFormFields($options = [])
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Add Your GetResponse Forms (or Exit Popups) to Your Shop'),
                    'icon' => 'icon-gears'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Add contacts to GetResponse via forms (or exit popups)'),
                        'name' => 'subscription',
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Enabled')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Disabled')]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Form'),
                        'name' => 'form',
                        'required' => true,
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Block position'),
                        'name' => 'position',
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id_option' => '', 'name' => $this->l('Select where to place the form')],
                                ['id_option' => 'home', 'name' => $this->l('Homepage')],
                                ['id_option' => 'left', 'name' => $this->l('Left sidebar')],
                                ['id_option' => 'right', 'name' => $this->l('Right sidebar')],
                                ['id_option' => 'top', 'name' => $this->l('Top')],
                                ['id_option' => 'footer', 'name' => $this->l('Footer')],
                            ],
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Style'),
                        'name' => 'style',
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id_option' => 'webform', 'name' => $this->l('Web Form')],
                                ['id_option' => 'prestashop', 'name' => 'Prestashop'],
                            ],
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'saveWebForm',
                    'icon' => 'process-icon-save'
                ]
            ]
        ];
    }
}

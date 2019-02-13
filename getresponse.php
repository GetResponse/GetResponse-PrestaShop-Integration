<?php
/**
 * This module integrate GetResponse and PrestaShop Allows subscribe via checkout page and export your contacts.
 *
 * @author Getresponse <grintegrations@getresponse.com>
 * @copyright GetResponse
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_ . '/getresponse/vendor/autoload.php';
include_once _PS_MODULE_DIR_ . '/getresponse/classes/GrApiException.php';
include_once _PS_MODULE_DIR_ . '/getresponse/classes/GetResponseRepository.php';
include_once _PS_MODULE_DIR_ . '/getresponse/classes/GetResponseNotConnectedException.php';

class Getresponse extends Module
{
    const X_APP_ID = '2cd8a6dc-003f-4bc3-ba55-c2e4be6f7500';
    const VERSION = '16.5.2';

    /** @var GetResponseRepository */
    private $repository;

    public function __construct()
    {
        $this->name = 'getresponse';
        $this->tab = 'emailing';
        $this->version = '16.5.2';
        $this->author = 'GetResponse';
        $this->need_instance = 0;
        $this->module_key = '7e6dc54b34af57062a5e822bd9b8d5ba';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->displayName = $this->l('GetResponse');
        $this->description = 'Add your Prestashop contacts to GetResponse. Automatically follow-up new subscriptions with engaging email marketing campaigns';
        $this->confirmUninstall = $this->l(GetResponse\Config\ConfigService::CONFIRM_UNINSTALL);

        parent::__construct();

        $this->repository = new GetResponseRepository(Db::getInstance(), GetResponse\Helper\Shop::getUserShopId());


        if (!function_exists('curl_init')) {
            $this->context->smarty->assign([
                'flash_message' => [
                    'message' => $this->l('Curl library not found'),
                    'status' => 'danger'
                ]
            ]);
        }
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (isset($this->context->controller->module)
            && $this->context->controller->module->name === $this->name
            && $confirmations = GetResponse\Helper\FlashMessages::getConfirmations()) {
            $this->context->smarty->assign('conf', $confirmations[0]);
        }

        $this->context->controller->addCss($this->_path . 'views/css/tab.css');
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install() || !$this->installTab()) {
            return false;
        }

        foreach (GetResponse\Config\ConfigService::USED_HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        // Update Version Number
        if (!Configuration::updateValue('GR_VERSION', $this->version)) {
            return false;
        }

        $this->repository->prepareDatabase();

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Getresponse';
        $tab->name = array();
        $tab->id_parent = strpos(_PS_VERSION_, '1.6') === 0 ? 0 : (int)Tab::getIdFromClassName('AdminAdmin');
        $tab->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'GetResponse';
        }
        $tab->add();
        $this->createSubTabs($tab->id);

        return true;
    }

    /**
     * @param int $tabId
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createSubTabs($tabId)
    {
        $langs = Language::getLanguages();
        foreach (GetResponse\Config\ConfigService::BACKOFFICE_TABS as $tab) {
            $newtab = new Tab();
            $newtab->class_name = $tab['class_name'];
            $newtab->id_parent = isset($tab['parent']) ? $tab['parent'] : $tabId;
            $newtab->module = $this->name;
            $newtab->position = 0;
            foreach ($langs as $l) {
                $newtab->name[$l['id_lang']] = $this->l($tab['name']);
            }
            $newtab->add();
        }

        return true;
    }

    /**
     * @throws PrestaShopException
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminGetresponseAccount'));
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        if (!parent::uninstall() || !$this->uninstallTab()) {
            return false;
        }

        foreach (GetResponse\Config\ConfigService::USED_HOOKS as $hook) {
            if (!$this->unregisterHook($hook)) {
                return false;
            }
        }

        if (!Configuration::deleteByName('GR_VERSION')) {
            return false;
        }

        $this->repository->clearDatabase();

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab()
    {
        $result = true;
        foreach (GetResponse\Config\ConfigService::INSTALLED_CLASSES as $class) {
            $idTab = (int)Tab::getIdFromClassName($class);
            if (false === $idTab) {
                return false;
            }
            $tab = new Tab($idTab);
            $result = $tab->delete() && $result;
        }

        return $result;
    }

    /**
     * @param $params
     */
    public function hookCart($params)
    {
        try {
            $cartHook = new GetResponse\Hook\NewCart();
            $cartHook->sendCart(
                $params['cart'],
                \GetResponse\Account\AccountServiceFactory::create()->getAccountSettings()
            );
        } catch (Exception $e) {
            $this->handleHookException($e, 'createCart');
        }
    }

    /**
     * @param $params
     */
    public function hookNewOrder($params)
    {
        $this->sendOrderToGr($params['order']);
    }

    /**
     * @param Order $order
     */
    private function sendOrderToGr(Order $order)
    {
        try {
            $orderHook = new GetResponse\Hook\NewOrder();
            $orderHook->sendOrder($order, \GetResponse\Account\AccountServiceFactory::create()->getAccountSettings());
        } catch (Exception $e) {
            $this->handleHookException($e, 'createOrder');
        }
    }

    /**
     * @param array $params
     */
    public function hookHookOrderConfirmation($params)
    {
        $this->sendOrderToGr($params['order']);
    }

    /**
     * @param array $params
     */
    public function hookPostUpdateOrderStatus($params)
    {
        if (isset($params['order'])) {
            $this->sendOrderToGr($params['order']);
        }
    }

    /**
     * @param array $params
     */
    public function hookCreateAccount($params)
    {
        $this->createSubscriber($params['newCustomer'], false);
    }

    /**
     * @param Customer $contact
     * @param bool $fromNewsletter
     */
    public function createSubscriber(Customer $contact, $fromNewsletter = false)
    {
        try {
            $service = \GetResponse\Settings\Registration\RegistrationServiceFactory::createService();
            $settings = $service->getSettings();

            if (!$settings->isActive() || 1 != $contact->newsletter) {
                return;
            }

            $addContactSettings = \GetResponse\Contact\AddContactSettings::createFromConfiguration($settings);

            $contactService = \GetResponse\Contact\ContactServiceFactory::createFromSettings();
            $contactService->addContact(
                \GetResponse\Customer\CustomerFactory::createFromPsCustomerObject($contact),
                $addContactSettings,
                $fromNewsletter == false
            );
        } catch (Exception $e) {
            $this->handleHookException($e, 'createSubscriber');
        }
    }

    /**
     * @return string
     */
    public function hookDisplayRightColumn()
    {
        return $this->displayWebForm('right');
    }

    /**
     * @param string $position
     * @return string
     */
    private function displayWebForm($position)
    {

        try {
            $formDisplay = new \GetResponse\Hook\FormDisplay(
                \GetResponse\WebForm\WebFormServiceFactory::createFromSettings(
                    \GetResponse\Account\AccountServiceFactory::create()->getAccountSettings()
                )
            );
            $assignData = $formDisplay->displayWebForm($position);

            if (!empty($assignData)) {
                $this->smarty->assign($assignData);

                return $this->display(__FILE__, 'views/templates/admin/common/webform.tpl');
            }

            return '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    public function hookDisplayLeftColumn()
    {
        return $this->displayWebForm('left');
    }

    /**
     * @return string
     */
    public function hookDisplayHeader()
    {
        try {
            $trackingService = \GetResponse\WebTracking\WebTrackingServiceFactory::create();
            $webTracking = $trackingService->getWebTracking();

            if (null === $webTracking || !$webTracking->isTrackingActive()) {
                return '';
            }

            $this->smarty->assign(
                ['gr_tracking_snippet' => $this->getSnippetUrl($webTracking->getSnippet())]
            );
            return $this->display(__FILE__, 'views/templates/admin/common/tracking_snippet.tpl');
        } catch (\GrShareCode\Api\Authorization\ApiTypeException $e) {
            $this->handleHookException($e, 'hookDisplayHeader');
            return '';
        }
    }

    private function getSnippetUrl($snippet)
    {
        if (empty($snippet)) {
            return '';
        }

        preg_match('/src="([^"]*)"/', $snippet, $url);

        if (isset($url[1])) {
            return $url[1];
        }

        return '';
    }

    /**
     * @return string
     */
    public function hookDisplayTop()
    {
        return $this->displayWebForm('top');
    }

    /**
     * @return string
     */
    public function hookDisplayFooter()
    {
        try {
            $this->createNewsletterSubscriber();

            $trackingService = \GetResponse\WebTracking\WebTrackingServiceFactory::create();
            $webTracking = $trackingService->getWebTracking();

            if (null === $webTracking) {
                return '';
            }

            $email = false;

            if (isset($this->context->customer)
                && !empty($this->context->customer->email)
                && $webTracking->isTrackingActive()
            ) {
                $email = $this->context->customer->email;
            }

            return $this->displayWebForm('footer') . $this->displayMailTracker($email);
        } catch (Exception $e) {
            $this->handleHookException($e, 'hookDisplayFooter');
            return '';
        }
    }

    /**
     * @throws GetResponseNotConnectedException
     * @throws PrestaShopDatabaseException
     */
    private function createNewsletterSubscriber()
    {
        if (Tools::isSubmit('submitNewsletter')
            && '0' == Tools::getValue('action')
            && Validate::isEmail(Tools::getValue('email'))
        ) {
            $service = \GetResponse\Settings\Registration\RegistrationServiceFactory::createService();
            if (!$service->getSettings()->isNewsletterActive()) {
                return;
            }

            $customer = new \Customer();
            $customer->newsletter = 1;
            $customer->firstname = 'Friend';
            $customer->lastname = '';
            $customer->email = Tools::getValue('email');

            $this->createSubscriber($customer, true);
        }
    }

    /**
     * @param string $email
     * @return mixed
     */
    private function displayMailTracker($email)
    {
        if (!empty($email)) {
            $this->smarty->assign(array('tracking_email' => $email));

            return $this->display(__FILE__, 'views/templates/admin/common/tracking_mail.tpl');
        }

        return '';
    }

    /**
     * @return string
     */
    public function hookDisplayHome()
    {
        return $this->displayWebForm('home');
    }

    /**
     * @param string $message
     */
    private function logGetResponseError($message)
    {
        PrestaShopLoggerCore::addLog($message, 2, null, 'GetResponse', 'GetResponse');
    }

    /**
     * @param Exception $exception
     * @param string $hookName
     */
    private function handleHookException(Exception $exception, $hookName)
    {
        if ($exception instanceof GetResponseNotConnectedException) {
            return;
        }

        if ($exception instanceof GrShareCode\Api\Exception\GetresponseApiException) {
            $errorMessage = sprintf(
                'GetResponse error: %s: GetresponseApiException: %s',
                $hookName,
                $exception->getMessage()
            );
            $this->logGetResponseError($errorMessage);
            return;
        }

        if ($exception instanceof GrShareCode\Validation\Assert\InvalidArgumentException) {
            $errorMessage = sprintf(
                'GetResponse error: %s: InvalidArgumentException: %s',
                $hookName,
                $exception->getMessage()
            );
            $this->logGetResponseError($errorMessage);
            return;
        }

        if ($exception instanceof PrestaShopDatabaseException) {
            $errorMessage = sprintf(
                'GetResponse error: %s: PrestaShopDatabaseException: %s',
                $hookName,
                $exception->getMessage()
            );
            $this->logGetResponseError($errorMessage);
            return;
        }

        if ($exception instanceof PrestaShopException) {
            $errorMessage = sprintf(
                'GetResponse error: %s: PrestaShopException: %s',
                $hookName,
                $exception->getMessage()
            );
            $this->logGetResponseError($errorMessage);
            return;
        }

        if ($exception instanceof Exception) {
            $errorMessage = sprintf('GetResponse error: %s: Exception: %s', $hookName, $exception->getMessage());
            $this->logGetResponseError($errorMessage);
            return;
        }
    }
}

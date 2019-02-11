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

namespace GetResponse\Export;

use Configuration;
use GetResponse\Contact\ContactCustomFieldCollectionFactory;
use GetResponse\Customer\CustomerFactory;
use GetResponse\Order\OrderFactory;
use GrShareCode\Export\Command\ExportContactCommand;
use GrShareCode\Export\ExportContactService;
use GrShareCode\GrShareCodeException;
use GrShareCode\Order\OrderCollection;
use InvalidArgumentException;
use Order;
use PrestaShopDatabaseException;
use PrestaShopLoggerCore;

/**
 * Class ExportService
 * @package GetResponse\Export
 */
class ExportService
{
    /** @var ExportRepository */
    private $exportRepository;
    /** @var ExportContactService */
    private $shareCodeExportContactService;
    /** @var OrderFactory */
    private $orderFactory;
    /** @var ContactCustomFieldCollectionFactory */
    private $contactCustomFieldCollectionFactory;

    /**
     * @param ExportRepository $exportRepository
     * @param ExportContactService $shareCodeExportContactService
     * @param OrderFactory $orderFactory
     * @param ContactCustomFieldCollectionFactory $contactCustomFieldCollectionFactory
     */
    public function __construct(
        ExportRepository $exportRepository,
        ExportContactService $shareCodeExportContactService,
        OrderFactory $orderFactory,
        ContactCustomFieldCollectionFactory $contactCustomFieldCollectionFactory
    ) {
        $this->exportRepository = $exportRepository;
        $this->shareCodeExportContactService = $shareCodeExportContactService;
        $this->orderFactory = $orderFactory;
        $this->contactCustomFieldCollectionFactory = $contactCustomFieldCollectionFactory;
    }

    /**
     * @param ExportSettings $exportSettings
     * @throws PrestaShopDatabaseException
     */
    public function export(ExportSettings $exportSettings)
    {
        $contacts = $this->exportRepository->getContacts($exportSettings->isNewsletterSubsIncluded());

        if (!count($contacts)) {
            return;
        }

        $grExportSettings = GrExportSettingsFactory::createFromExportSettings($exportSettings);

        foreach ($contacts as $contact) {
            if (0 == $contact['id']) {
                // flow for newsletters subscribers
                $customer = CustomerFactory::createFromNewsletter($contact['email']);
                $orderCollection = new OrderCollection();
            } else {
                $customer = CustomerFactory::createFromArray($contact);
                $orderCollection = $this->buildOrderCollection($contact['id']);
            }

            $contactCustomFieldCollection = $this->contactCustomFieldCollectionFactory
                ->createFromContactAndCustomFieldMapping(
                    $customer,
                    $exportSettings->getCustomFieldMappingCollection()
                );

            try {
                $this->shareCodeExportContactService->exportContact(
                    new ExportContactCommand(
                        $customer->getEmail(),
                        $customer->getName(),
                        $grExportSettings,
                        $contactCustomFieldCollection,
                        $orderCollection
                    )
                );
            } catch (GrShareCodeException $e) {
                PrestaShopLoggerCore::addLog(
                    'Getresponse export error: ' . $e->getMessage(),
                    2,
                    null,
                    'GetResponse',
                    'GetResponse'
                );
            }
        }
    }

    /**
     * @param int $contactId
     * @return OrderCollection
     */
    private function buildOrderCollection($contactId)
    {
        $customerOrders = $this->exportRepository->getOrders($contactId);
        $orderCollection = new OrderCollection();

        foreach ($customerOrders as $customerOrder) {
            try {
                $orderCollection->add(
                    $this->orderFactory->createShareCodeOrderFromOrder(
                        new Order($customerOrder['id_order'], Configuration::get('PS_LANG_DEFAULT'))
                    )
                );
            } catch (InvalidArgumentException $e) {
                PrestaShopLoggerCore::addLog(
                    'Getresponse Order Failed: ' . $e->getMessage(),
                    2,
                    null,
                    'GetResponse',
                    'GetResponse'
                );
            }
        }

        return $orderCollection;
    }
}

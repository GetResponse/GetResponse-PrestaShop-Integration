<?php
namespace GetResponse\CustomFields;

use Db;
use GetResponse\Account\AccountSettingsRepository;
use GetResponse\Api\ApiFactory;
use GetResponseRepository;
use GrShareCode\Api\ApiTypeException;
use GrShareCode\CustomField\CustomFieldService as GrCustomFieldService;
use GrShareCode\GetresponseApiClient;
use GrShop;

/**
 * Class CustomFieldsServiceFactory
 */
class CustomFieldsServiceFactory
{
    /**
     * @return CustomFieldService
     * @throws ApiTypeException
     */
    public static function create()
    {
        $accountSettingsRepository = new AccountSettingsRepository(Db::getInstance(), GrShop::getUserShopId());
        $api = ApiFactory::createFromSettings($accountSettingsRepository->getSettings());
        $repository = new GetResponseRepository(Db::getInstance(), GrShop::getUserShopId());
        $apiClient = new GetresponseApiClient($api, $repository);

        return new CustomFieldService(
            new GrCustomFieldService($apiClient)
        );
    }
}
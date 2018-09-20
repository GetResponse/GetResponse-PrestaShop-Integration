<?php
namespace GetResponse\Account;

use Db;
use GrShop;

/**
 * Class AccountStatusFactory
 * @package GetResponse\Account
 */
class AccountStatusFactory
{
    /**
     * @return AccountStatus
     */
    public static function create()
    {
        return new AccountStatus(
            new AccountSettingsRepository(Db::getInstance(), GrShop::getUserShopId())
        );
    }
}
<?php
namespace GetResponse\Export;

/**
 * Class ExportDto
 * @package GetResponse\Export
 */
class ExportSettings
{
    /** @var string */
    private $contactListId;

    /** @var int|null */
    private $cycleDay;

    /** @var bool */
    private $updateContactInfo;

    /** @var bool */
    private $newsletterSubsIncluded;

    /** @var bool */
    private $ecommerce;

    /**
     * @param string $contactListId
     * @param int|null $cycleDay
     * @param bool $updateContactInfo
     * @param bool $newsletterSubsIncluded
     * @param bool $ecommerce
     */
    public function __construct(
        $contactListId,
        $cycleDay,
        $updateContactInfo,
        $newsletterSubsIncluded,
        $ecommerce
    ) {
        $this->contactListId = $contactListId;
        $this->cycleDay = $cycleDay;
        $this->updateContactInfo = $updateContactInfo;
        $this->newsletterSubsIncluded = $newsletterSubsIncluded;
        $this->ecommerce = $ecommerce;
    }

    /**
     * @return string
     */
    public function getContactListId()
    {
        return $this->contactListId;
    }

    /**
     * @return int|null
     */
    public function getCycleDay()
    {
        return $this->cycleDay;
    }

    /**
     * @return bool
     */
    public function isUpdateContactInfo()
    {
        return $this->updateContactInfo;
    }

    /**
     * @return bool
     */
    public function isNewsletterSubsIncluded()
    {
        return $this->newsletterSubsIncluded;
    }

    /**
     * @return bool
     */
    public function isEcommerce()
    {
        return $this->ecommerce;
    }
}
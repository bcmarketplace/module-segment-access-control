<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Model;

use BCMarketplace\SegmentAccessControl\Api\Data\OrganizationGroupInterface;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Framework\Model\AbstractModel;

/**
 * Organization Group Model
 */
class OrganizationGroup extends AbstractModel implements OrganizationGroupInterface
{
    /**
     * @var string
     */
    protected $_cacheTag = Constants::CACHE_TAG_SEGMENT;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'organization_group';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\BCMarketplace\SegmentAccessControl\Model\ResourceModel\OrganizationGroup::class);
    }

    /**
     * Get Segment ID
     *
     * @return int|null
     */
    public function getSegmentId()
    {
        return $this->getData(self::SEGMENT_ID);
    }

    /**
     * Set Segment ID
     *
     * @param int $segmentId
     * @return $this
     */
    public function setSegmentId($segmentId)
    {
        return $this->setData(self::SEGMENT_ID, $segmentId);
    }

    /**
     * Get Company Role ID
     *
     * @return int|null
     */
    public function getCompanyRoleId()
    {
        return $this->getData(self::COMPANY_ROLE_ID);
    }

    /**
     * Set Company Role ID
     *
     * @param int $companyRoleId
     * @return $this
     */
    public function setCompanyRoleId($companyRoleId)
    {
        return $this->setData(self::COMPANY_ROLE_ID, $companyRoleId);
    }

    /**
     * Get Category IDs
     *
     * @return string|null
     */
    public function getCategoryIds()
    {
        return $this->getData(self::CATEGORY_IDS);
    }

    /**
     * Set Category IDs
     *
     * @param string $categoryIds
     * @return $this
     */
    public function setCategoryIds($categoryIds)
    {
        return $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    /**
     * Get Status
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Is Admin
     *
     * @return int|null
     */
    public function getIsAdmin()
    {
        return $this->getData(self::IS_ADMIN);
    }

    /**
     * Set Is Admin
     *
     * @param int $isAdmin
     * @return $this
     */
    public function setIsAdmin($isAdmin)
    {
        return $this->setData(self::IS_ADMIN, $isAdmin);
    }

    /**
     * Get Company ID
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->getData(self::COMPANY_ID);
    }

    /**
     * Set Company ID
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId)
    {
        return $this->setData(self::COMPANY_ID, $companyId);
    }

    /**
     * Get Company Admin ID
     *
     * @return int|null
     */
    public function getCompanyAdminId()
    {
        return $this->getData(self::COMPANY_ADMIN_ID);
    }

    /**
     * Set Company Admin ID
     *
     * @param int $companyAdminId
     * @return $this
     */
    public function setCompanyAdminId($companyAdminId)
    {
        return $this->setData(self::COMPANY_ADMIN_ID, $companyAdminId);
    }

    /**
     * Get Creation Time
     *
     * @return string|null
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * Set Creation Time
     *
     * @param string $creationTime
     * @return $this
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * Get Update Time
     *
     * @return string|null
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Set Update Time
     *
     * @param string $updateTime
     * @return $this
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }
}


<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Model;

use BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Framework\Model\AbstractModel;

/**
 * Segment Manage Model
 */
class Manage extends AbstractModel implements ManageInterface
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
    protected $_eventPrefix = Constants::EVENT_PREFIX;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage::class);
    }

    /**
     * Get segment ID
     *
     * @return int|void|null
     */
    public function getSegmentId()
    {
        return $this->getData(self::SEGMENT_ID);
    }

    /**
     * Is active
     *
     * @return bool|void|null
     */
    public function isActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * Get name
     *
     * @return string|void|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Get store id
     *
     * @return int|void|null
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Get customer group
     *
     * @return int|void|null
     */
    public function getCustomerGroup()
    {
        return $this->getData(self::CUSTOMER_GROUP);
    }

    /**
     * Get creation time
     *
     * @return string|void|null
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * Get update time
     *
     * @return array|mixed|string|null
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Set EntityId.
     */
    public function setSegmentId($segmentId)
    {
        return $this->setData(self::SEGMENT_ID, $segmentId);
    }

    /**
     * Set is active
     *
     * @param $isActive
     * @return Manage|\BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Set name
     *
     * @param $name
     * @return ManageInterface|Manage
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set store id
     *
     * @param $storeid
     * @return ManageInterface|Manage
     */
    public function setStoreId($storeid)
    {
        return $this->setData(self::STORE_ID, $storeid);
    }

    /**
     * Set customer group
     *
     * @param $customergroup
     * @return ManageInterface|Manage
     */
    public function setCustomerGroup($customergroup)
    {
        return $this->setData(self::CUSTOMER_GROUP, $customergroup);
    }

    /**
     * Set creation time
     *
     * @param $creationTime
     * @return ManageInterface|Manage
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * Set update time
     *
     * @param $updateTime
     * @return ManageInterface|Manage
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * Prepare segment's statuses
     *
     * @return array
     */
    public function getAvailableStatuses(): array
    {
        return [
            Constants::STATUS_ENABLED => __('Enabled'),
            Constants::STATUS_DISABLED => __('Disabled')
        ];
    }
}

<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Api\Data;

/**
 * Segment manage interface.
 */
interface ManageInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const SEGMENT_ID            = 'segment_id';
    const IS_ACTIVE                = 'is_active';
    const NAME                     = 'name';
    const STORE_ID                 = 'store_id';
    const CUSTOMER_GROUP           = 'customer_group';
    const CREATION_TIME            = 'creation_time';
    const UPDATE_TIME              = 'update_time';

    /**#@-*/

    /**
     * Get Segment ID
     *
     * @return int|null
     */
    public function getSegmentId();

    /**
     * Is active
     *
     * @return bool|null
     */
    public function isActive();

    /**
     * Get Segment Name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get Store ID
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Get Customer Group
     *
     * @return int|null
     */
    public function getCustomerGroup();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreationTime();

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdateTime();

    /**
     * Set Segment ID
     *
     * @param int $segmentid
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setSegmentId($segmentid);

    /**
     * Set is active
     *
     * @param int|bool $isActive
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setIsActive($isActive);

    /**
     * Set Segment Name
     *
     * @param string $name
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setName($name);

    /**
     * Set Store ID
     *
     * @param int $storeid
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setStoreId($storeid);

    /**
     * Set Customer Group
     *
     * @param int $customergroup
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setCustomerGroup($customergroup);

    /**
     * Set creation time
     *
     * @param string $creationTime
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setCreationTime($creationTime);

    /**
     * Set update time
     *
     * @param string $updateTime
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     */
    public function setUpdateTime($updateTime);
}

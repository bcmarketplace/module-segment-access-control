<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Api\Data;

/**
 * Organization Group Interface
 */
interface OrganizationGroupInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const SEGMENT_ID = 'segment_id';
    const COMPANY_ROLE_ID = 'company_role_id';
    const CATEGORY_IDS = 'category_ids';
    const STATUS = 'status';
    const IS_ADMIN = 'is_admin';
    const COMPANY_ID = 'company_id';
    const COMPANY_ADMIN_ID = 'company_admin_id';
    const CREATION_TIME = 'creation_time';
    const UPDATE_TIME = 'update_time';
    /**#@-*/

    /**
     * Get Segment ID
     *
     * @return int|null
     */
    public function getSegmentId();

    /**
     * Set Segment ID
     *
     * @param int $segmentId
     * @return $this
     */
    public function setSegmentId($segmentId);

    /**
     * Get Company Role ID
     *
     * @return int|null
     */
    public function getCompanyRoleId();

    /**
     * Set Company Role ID
     *
     * @param int $companyRoleId
     * @return $this
     */
    public function setCompanyRoleId($companyRoleId);

    /**
     * Get Category IDs (comma separated)
     *
     * @return string|null
     */
    public function getCategoryIds();

    /**
     * Set Category IDs (comma separated)
     *
     * @param string $categoryIds
     * @return $this
     */
    public function setCategoryIds($categoryIds);

    /**
     * Get Status
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Set Status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get Is Admin
     *
     * @return int|null
     */
    public function getIsAdmin();

    /**
     * Set Is Admin
     *
     * @param int $isAdmin
     * @return $this
     */
    public function setIsAdmin($isAdmin);

    /**
     * Get Company ID
     *
     * @return int|null
     */
    public function getCompanyId();

    /**
     * Set Company ID
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId);

    /**
     * Get Company Admin ID
     *
     * @return int|null
     */
    public function getCompanyAdminId();

    /**
     * Set Company Admin ID
     *
     * @param int $companyAdminId
     * @return $this
     */
    public function setCompanyAdminId($companyAdminId);

    /**
     * Get Creation Time
     *
     * @return string|null
     */
    public function getCreationTime();

    /**
     * Set Creation Time
     *
     * @param string $creationTime
     * @return $this
     */
    public function setCreationTime($creationTime);

    /**
     * Get Update Time
     *
     * @return string|null
     */
    public function getUpdateTime();

    /**
     * Set Update Time
     *
     * @param string $updateTime
     * @return $this
     */
    public function setUpdateTime($updateTime);
}


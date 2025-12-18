<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Segment Manage CRUD interface.
 */
interface ManageRepositoryInterface
{
    /**
     * Save Segment.
     *
     * @param \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface $manage
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface $manage);

    /**
     * Retrieve Segment.
     *
     * @param int $manageId
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($manageId);

    /**
     * Retrieve Segment matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\ManageSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Segment.
     *
     * @param \BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface $manage
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface $manage);

    /**
     * Delete Segment by ID.
     *
     * @param int $manageId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($manageId);
}

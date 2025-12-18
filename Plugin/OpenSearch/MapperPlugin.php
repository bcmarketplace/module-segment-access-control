<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Plugin\OpenSearch;

use BCMarketplace\SegmentAccessControl\Helper\Data as SegmentHelper;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory as SegmentCollectionFactory;
use BCMarketplace\SegmentAccessControl\Service\Cache\SegmentCacheService;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as PermissionsCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\OpenSearch\SearchAdapter\Mapper;
use Magento\Framework\Search\RequestInterface;

/**
 * Plugin to modify OpenSearch queries to filter by segment-based category permissions
 * Optimized with caching and batch operations
 */
class MapperPlugin
{
    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var SegmentCollectionFactory
     */
    private SegmentCollectionFactory $segmentCollectionFactory;

    /**
     * @var PermissionsCollectionFactory
     */
    private PermissionsCollectionFactory $permissionsCollectionFactory;

    /**
     * @var SegmentHelper
     */
    private SegmentHelper $segmentHelper;

    /**
     * @var SegmentCacheService
     */
    private SegmentCacheService $cacheService;

    /**
     * @param Session $customerSession
     * @param SegmentCollectionFactory $segmentCollectionFactory
     * @param PermissionsCollectionFactory $permissionsCollectionFactory
     * @param SegmentHelper $segmentHelper
     * @param SegmentCacheService $cacheService
     */
    public function __construct(
        Session $customerSession,
        SegmentCollectionFactory $segmentCollectionFactory,
        PermissionsCollectionFactory $permissionsCollectionFactory,
        SegmentHelper $segmentHelper,
        SegmentCacheService $cacheService
    ) {
        $this->customerSession = $customerSession;
        $this->segmentCollectionFactory = $segmentCollectionFactory;
        $this->permissionsCollectionFactory = $permissionsCollectionFactory;
        $this->segmentHelper = $segmentHelper;
        $this->cacheService = $cacheService;
    }

    /**
     * Modify OpenSearch query to filter by segment categories
     *
     * @param Mapper $subject
     * @param array $query
     * @param RequestInterface $request
     * @return array
     */
    public function afterBuildQuery(Mapper $subject, array $query, RequestInterface $request): array
    {
        if (!$this->segmentHelper->isEnabled()) {
            return $query;
        }

        $categoryIds = $this->getSegmentCategories();
        
        if (empty($categoryIds)) {
            return $query;
        }

        // Add category filter to OpenSearch query
        // Ensure query structure exists
        if (!isset($query['body'])) {
            $query['body'] = [];
        }
        if (!isset($query['body']['query'])) {
            $query['body']['query'] = [];
        }
        if (!isset($query['body']['query']['bool'])) {
            $query['body']['query']['bool'] = [];
        }
        if (!isset($query['body']['query']['bool']['must'])) {
            $query['body']['query']['bool']['must'] = [];
        }

        // Add terms filter for category IDs
        $query['body']['query']['bool']['must'][] = [
            'terms' => [
                'category_ids' => $categoryIds
            ]
        ];

        return $query;
    }

    /**
     * Get customer group IDs from segments
     * Cached for performance
     *
     * @return array
     */
    private function getSegmentUserGroups(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return [];
        }

        $segmentFilter = $customer->getData(Constants::ATTRIBUTE_SEGMENT_FILTER);
        if (empty($segmentFilter)) {
            return [];
        }

        // Normalize segment IDs
        $segmentIds = $this->normalizeSegmentIds($segmentFilter);
        if (empty($segmentIds)) {
            return [];
        }

        // Generate cache key
        $cacheKey = $this->cacheService->generateIdentifier(
            'segment_user_groups',
            ['segment_ids' => $segmentIds]
        );

        // Try to load from cache
        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            return unserialize($cached, ['allowed_classes' => false]);
        }

        // Batch load segments
        $segments = $this->segmentCollectionFactory->create()
            ->addFieldToSelect([Constants::COLUMN_SEGMENT_ID, Constants::COLUMN_GROUP])
            ->addFieldToFilter(Constants::COLUMN_SEGMENT_ID, ['in' => $segmentIds]);

        $customerGroupIds = [];
        foreach ($segments as $segment) {
            $groupId = (int)$segment->getGroup();
            if ($groupId > 0 && !in_array($groupId, $customerGroupIds, true)) {
                $customerGroupIds[] = $groupId;
            }
        }

        // Cache the result
        $this->cacheService->save(
            $customerGroupIds,
            $cacheKey,
            [Constants::CACHE_TAG_SEGMENTS]
        );

        return $customerGroupIds;
    }

    /**
     * Get categories accessible by segment customer groups
     * Cached for performance
     *
     * @return array
     */
    private function getSegmentCategories(): array
    {
        $customerGroupIds = $this->getSegmentUserGroups();
        
        if (empty($customerGroupIds)) {
            return [];
        }

        // Generate cache key
        sort($customerGroupIds);
        $cacheKey = $this->cacheService->generateIdentifier(
            'segment_categories',
            ['group_ids' => $customerGroupIds]
        );

        // Try to load from cache
        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            return unserialize($cached, ['allowed_classes' => false]);
        }

        // Batch load permissions
        // Get categories where permission is "use parent" (-1) for the customer groups
        // This matches the original logic from AtlanticBT_OpenSearchCatalogPermissions
        $permissions = $this->permissionsCollectionFactory->create()
            ->addFieldToFilter(Constants::COLUMN_CUSTOMER_GROUP_ID, ['in' => $customerGroupIds])
            ->addFieldToFilter(Constants::PERMISSION_GRANT_CATEGORY_VIEW, (string)Constants::PERMISSION_USE_PARENT);

        $categoryIds = [];
        foreach ($permissions as $permission) {
            $categoryId = (int)$permission->getCategoryId();
            if ($categoryId > 0 && !in_array($categoryId, $categoryIds, true)) {
                $categoryIds[] = $categoryId;
            }
        }

        // Cache the result
        $this->cacheService->save($categoryIds, $cacheKey, ['category_permissions']);

        return $categoryIds;
    }

    /**
     * Normalize segment IDs from string to array
     *
     * @param string $segmentFilter
     * @return array
     */
    private function normalizeSegmentIds(string $segmentFilter): array
    {
        if (str_contains($segmentFilter, Constants::DELIMITER_COMMA)) {
            return array_filter(
                array_map('intval', explode(Constants::DELIMITER_COMMA, $segmentFilter)),
                fn($id) => $id > 0
            );
        }

        $id = (int)$segmentFilter;
        return $id > 0 ? [$id] : [];
    }
}


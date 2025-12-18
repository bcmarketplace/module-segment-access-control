<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Helper;

use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory as SegmentCollectionFactory;
use BCMarketplace\SegmentAccessControl\Service\Cache\SegmentCacheService;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\Model\Permission as CategoryPermission;
use Magento\Framework\App\Helper\Context;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection as PermissionCollection;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Helper class for segment access control
 * Optimized for performance with caching and batch operations
 */
class Data extends AbstractHelper
{
    /**
     * @var PermissionCollectionFactory
     */
    private PermissionCollectionFactory $permissionCollectionFactory;

    /**
     * @var SegmentCollectionFactory
     */
    private SegmentCollectionFactory $segmentCollectionFactory;

    /**
     * @var SegmentCacheService
     */
    private SegmentCacheService $cacheService;

    /**
     * @param Context $context
     * @param PermissionCollectionFactory $permissionCollectionFactory
     * @param SegmentCollectionFactory $segmentCollectionFactory
     * @param SegmentCacheService $cacheService
     */
    public function __construct(
        Context $context,
        PermissionCollectionFactory $permissionCollectionFactory,
        SegmentCollectionFactory $segmentCollectionFactory,
        SegmentCacheService $cacheService
    ) {
        $this->permissionCollectionFactory = $permissionCollectionFactory;
        $this->segmentCollectionFactory = $segmentCollectionFactory;
        $this->cacheService = $cacheService;
        parent::__construct($context);
    }

    /**
     * Get config value
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigValue(string $field, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get general config
     *
     * @param string $code
     * @param int|null $storeId
     * @return mixed
     */
    public function getGeneralConfig(string $code, ?int $storeId = null)
    {
        return $this->getConfigValue(
            Constants::XML_PATH_SEGMENT_GENERAL . $code,
            $storeId
        );
    }

    /**
     * Get all customer groups associated with the product
     * Optimized to reduce N+1 queries by batching category permission lookups
     *
     * @param Product $product
     * @return array{0: array, 1: array} Returns [permissions, customerGroups]
     * @throws NoSuchEntityException
     */
    public function getCustomerGroupIdsFromProduct(Product $product): array
    {
        $categoryIds = $product->getCategoryIds();
        
        if (empty($categoryIds)) {
            return [[], []];
        }

        // Generate cache key
        $cacheKey = $this->cacheService->generateIdentifier(
            'product_customer_groups',
            ['category_ids' => $categoryIds]
        );

        // Try to load from cache
        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            return unserialize($cached, ['allowed_classes' => false]);
        }

        // Batch load permissions for all categories at once
        $permissionCollection = $this->permissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter(
            Constants::COLUMN_CATEGORY_ID,
            ['in' => $categoryIds]
        );

        $customerGroups = [];
        $returnedPermissions = [];

        // Process permissions efficiently
        foreach ($permissionCollection as $permission) {
            $categoryId = $permission->getCategoryId();
            $customerGroupId = $permission->getCustomerGroupId();
            
            if ($customerGroupId && 
                $permission->getData(Constants::PERMISSION_GRANT_CATEGORY_VIEW) === (string)CategoryPermission::PERMISSION_ALLOW
            ) {
                if (!in_array($customerGroupId, $customerGroups, true)) {
                    $customerGroups[] = $customerGroupId;
                }
                if (!isset($returnedPermissions[$categoryId])) {
                    $returnedPermissions[$categoryId] = $permission;
                }
            }
        }

        $result = [$returnedPermissions, $customerGroups];
        
        // Cache the result
        $this->cacheService->save($result, $cacheKey, ['product_permissions']);

        return $result;
    }

    /**
     * Get category permissions
     * Cached for performance
     *
     * @param int $categoryId
     * @return PermissionCollection
     */
    public function getPermissionsByCategoryId(int $categoryId): PermissionCollection
    {
        $cacheKey = $this->cacheService->generateIdentifier(
            'category_permissions',
            ['category_id' => $categoryId]
        );

        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            $collection = $this->permissionCollectionFactory->create();
            $collection->setData(unserialize($cached, ['allowed_classes' => false]));
            return $collection;
        }

        $permissionCollection = $this->permissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter(Constants::COLUMN_CATEGORY_ID, $categoryId);
        
        $this->cacheService->save(
            $permissionCollection->getData(),
            $cacheKey,
            ['category_permissions']
        );

        return $permissionCollection;
    }

    /**
     * Get segment by customer group id
     * Cached for performance
     *
     * @param int $customerGroupId
     * @return array
     */
    public function getSegmentByCustomerGroupId(int $customerGroupId): array
    {
        $cacheKey = $this->cacheService->generateIdentifier(
            'segment_by_group',
            ['group_id' => $customerGroupId]
        );

        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            return unserialize($cached, ['allowed_classes' => false]);
        }

        $segments = $this->segmentCollectionFactory->create();
        $segments->addFieldToFilter(Constants::COLUMN_GROUP, $customerGroupId);
        $result = $segments->getData();

        $this->cacheService->save($result, $cacheKey, [Constants::CACHE_TAG_SEGMENTS]);

        return $result;
    }

    /**
     * Get segments by IDs
     * Cached for performance
     *
     * @param array $segmentIds
     * @return \BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\Collection
     */
    public function getSegmentsByIds(array $segmentIds)
    {
        if (empty($segmentIds)) {
            return $this->segmentCollectionFactory->create();
        }

        // Sort IDs for consistent cache key
        sort($segmentIds);
        $cacheKey = $this->cacheService->generateIdentifier(
            'segments_by_ids',
            ['ids' => $segmentIds]
        );

        $cached = $this->cacheService->load($cacheKey);
        if ($cached !== false) {
            $collection = $this->segmentCollectionFactory->create();
            $collection->setData(unserialize($cached, ['allowed_classes' => false]));
            return $collection;
        }

        $segments = $this->segmentCollectionFactory->create();
        $segments->addFieldToFilter(Constants::COLUMN_SEGMENT_ID, ['in' => $segmentIds]);

        $this->cacheService->save(
            $segments->getData(),
            $cacheKey,
            [Constants::CACHE_TAG_SEGMENTS]
        );

        return $segments;
    }

    /**
     * Update product permissions based on segments
     * Optimized with proper string handling and early returns
     *
     * @param Product $product
     * @param array|string|null $segmentIds
     * @return Product
     * @throws NoSuchEntityException
     */
    public function setProductPermissionBySegment(Product $product, array|string|null $segmentIds): Product
    {
        if (empty($segmentIds)) {
            return $product;
        }

        // Normalize segment IDs to array
        $segments = $this->normalizeSegmentIds($segmentIds);
        
        if (empty($segments)) {
            return $product;
        }

        [$permissions, $customerGroups] = $this->getCustomerGroupIdsFromProduct($product);
        
        // Early return if no customer groups
        if (empty($customerGroups)) {
            return $product;
        }

        // Batch load segments and customer groups
        $segmentCollection = $this->segmentCollectionFactory->create();
        $segmentCollection->addFieldToFilter(Constants::COLUMN_GROUP, ['in' => $customerGroups])
            ->addFieldToFilter(Constants::COLUMN_SEGMENT_ID, ['in' => $segments]);

        if ($segmentCollection->getSize() === 0) {
            return $product;
        }

        // Create lookup map for O(1) access
        $segmentGroupMap = [];
        foreach ($segmentCollection as $segment) {
            $segmentGroupMap[$segment->getGroup()] = $segment;
        }

        // Apply permissions efficiently
        foreach ($permissions as $permission) {
            $customerGroupId = $permission->getCustomerGroupId();
            
            if (isset($segmentGroupMap[$customerGroupId])) {
                $product->setData(
                    Constants::PERMISSION_GRANT_CATEGORY_VIEW,
                    $permission->getData(Constants::PERMISSION_GRANT_CATEGORY_VIEW)
                );
                $product->setData(
                    Constants::PERMISSION_GRANT_PRODUCT_PRICE,
                    $permission->getData(Constants::PERMISSION_GRANT_PRODUCT_PRICE)
                );
                $product->setData(
                    Constants::PERMISSION_GRANT_CHECKOUT_ITEMS,
                    $permission->getData(Constants::PERMISSION_GRANT_CHECKOUT_ITEMS)
                );
                return $product;
            }
        }

        return $product;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->getGeneralConfig(Constants::CONFIG_ENABLED);
    }

    /**
     * Normalize segment IDs to array
     *
     * @param array|string|null $segmentIds
     * @return array
     */
    private function normalizeSegmentIds(array|string|null $segmentIds): array
    {
        if (is_array($segmentIds)) {
            return array_filter(array_map('intval', $segmentIds));
        }

        if (is_string($segmentIds)) {
            if (str_contains($segmentIds, Constants::DELIMITER_COMMA)) {
                return array_filter(
                    array_map('intval', explode(Constants::DELIMITER_COMMA, $segmentIds))
                );
            }
            $id = (int) $segmentIds;
            return $id > 0 ? [$id] : [];
        }

        return [];
    }
}

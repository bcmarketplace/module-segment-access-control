<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Plugin\OpenSearch;

use BCMarketplace\SegmentAccessControl\Helper\Data as SegmentHelper;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\CatalogPermissions\App\Config;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Customer\Model\Session;
use Magento\OpenSearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\OpenSearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Add category permissions filters to fulltext collection
 * Optimized to work with segment-based access control
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AddCategoryPermissionsToFulltextCollection
{
    /**
     * Flag to check that category permissions filters already added
     */
    private const PERMISSION_FILTER_ADDED_FLAG = 'permission_filter_added';

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var StoreManager
     */
    private StoreManager $storeManager;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $fieldNameResolver;

    /**
     * @var AttributeProvider
     */
    private AttributeProvider $attributeAdapterProvider;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var EngineResolverInterface
     */
    private EngineResolverInterface $engineResolver;

    /**
     * @var SegmentHelper
     */
    private SegmentHelper $segmentHelper;

    /**
     * @param Session $customerSession
     * @param StoreManager $storeManager
     * @param ResolverInterface $fieldNameResolver
     * @param AttributeProvider $attributeAdapterProvider
     * @param Config $config
     * @param EngineResolverInterface $engineResolver
     * @param SegmentHelper $segmentHelper
     */
    public function __construct(
        Session $customerSession,
        StoreManager $storeManager,
        ResolverInterface $fieldNameResolver,
        AttributeProvider $attributeAdapterProvider,
        Config $config,
        EngineResolverInterface $engineResolver,
        SegmentHelper $segmentHelper
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->config = $config;
        $this->engineResolver = $engineResolver;
        $this->segmentHelper = $segmentHelper;
    }

    /**
     * Add catalog permissions before load collection
     *
     * @param Collection $productCollection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(Collection $productCollection, bool $printQuery = false, bool $logQuery = false): array
    {
        if (!$productCollection->isLoaded()) {
            $this->applyPermissionFilter($productCollection);
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Add catalog permissions before get faceted data
     *
     * @param Collection $productCollection
     * @param string $field
     * @return array
     */
    public function beforeGetFacetedData(Collection $productCollection, string $field): array
    {
        $this->applyPermissionFilter($productCollection);

        return [$field];
    }

    /**
     * Add catalog permissions before get select count
     *
     * @param Collection $productCollection
     * @return void
     */
    public function beforeGetSelectCountSql(Collection $productCollection): void
    {
        if (!$productCollection->isLoaded()) {
            $this->applyPermissionFilter($productCollection);
        }
    }

    /**
     * Add catalog permissions to filter
     * Skip when segments are enabled (handled by MapperPlugin)
     *
     * @param Collection $productCollection
     * @return void
     */
    private function applyPermissionFilter(Collection $productCollection): void
    {
        // Skip if segments are enabled (segments handle permissions differently)
        if ($this->segmentHelper->isEnabled()) {
            return;
        }

        if ($this->avoidApplyPermissions($productCollection)) {
            return;
        }

        $categoryPermissionAttribute = $this->attributeAdapterProvider->getByAttributeCode('category_permission');
        $categoryPermissionKey = $this->fieldNameResolver->getFieldName(
            $categoryPermissionAttribute,
            [
                'storeId' => $this->storeManager->getStore()->getId(),
                'customerGroupId' => $this->customerSession->getCustomerGroupId(),
            ]
        );

        $productCollection->addFieldToFilter('category_permissions_field', $categoryPermissionKey);
        $productCollection->addFieldToFilter('category_permissions_value', Permission::PERMISSION_DENY);

        $productCollection->setFlag(self::PERMISSION_FILTER_ADDED_FLAG, true);
    }

    /**
     * Whether to avoid apply permission to collection
     *
     * @param Collection $productCollection
     * @return bool
     */
    private function avoidApplyPermissions(Collection $productCollection): bool
    {
        return $productCollection->getFlag(self::PERMISSION_FILTER_ADDED_FLAG) || !$this->config->isEnabled();
    }
}


<?php
declare(strict_types=1);

/**
 * This is addressing the access the permission for catalog product based on segment values instead of customer group
 * v2.4.7
 *
 * */
namespace BCMarketplace\SegmentAccessControl\Plugin\Sales\CustomerData;

use BCMarketplace\SegmentAccessControl\Helper\Data as SegmentHelper;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Plugin\Sales\CustomerData\CheckCatalogPermissionAfterLastOrderedItemsPlugin as BaseCheckCatalogPermissionAfterLastOrderedItemsPlugin;
use Magento\Customer\Model\Session;
use Magento\Sales\CustomerData\LastOrderedItems;
use Magento\Store\Model\StoreManagerInterface;

class CheckCatalogPermissionAfterLastOrderedItemsPlugin extends BaseCheckCatalogPermissionAfterLastOrderedItemsPlugin
{

    private ConfigInterface $permissionConfig;
    private ConfigInterface $permissionsConfig;
    private Session $customerSession;
    private StoreManagerInterface $storeManager;
    private Index $permissionIndex;
    private SegmentHelper $segmentHelper;

    public function __construct(
        Index $permissionIndex,
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        SegmentHelper $segmentHelper
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->permissionIndex = $permissionIndex;
        $this->segmentHelper = $segmentHelper;
        parent::__construct($permissionIndex, $permissionsConfig, $customerSession, $storeManager);
    }
    public function afterGetSectionData(LastOrderedItems $subject, array $result): array
    {
        if(!$this->segmentHelper->isEnabled()){
            return parent::afterGetSectionData($subject, $result);
        }
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        $storeId = (int) $this->storeManager->getStore()->getId();
        if ($result && $result['items']) {
            foreach ($result['items'] as $key => $item) {
                $productId = $item[Constants::ARRAY_KEY_PRODUCT_ID] ?? null;
                if (!$productId) {
                    continue;
                }

                $permissions = $this->permissionIndex->getIndexForProduct(
                    $productId,
                    $customerGroupId,
                    $storeId
                );
                if ($permissions) {
                    $filteredPermissions = $this->getCatalogPermission($permissions, $item);
                    $grantCatalogCategoryViewPermission = $this->getGrantCategoryViewValue($filteredPermissions);
                    if (!empty($filteredPermissions) && $grantCatalogCategoryViewPermission !== "-1") {
                        unset($result['items'][$key]);
                    }
                } else {
                    unset($result['items'][$key]);
                }
            }
        }
        return $result;
    }

    /**
     * Filter out the permissions that pertain to the segments that current customer belongs to
     *
     * @param array $permissions
     * @param array $item
     * @return array
     */
    private function getCatalogPermission(array $permissions, array $item): array
    {
        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return [];
        }

        $segmentFilterValue = $customer->getData(Constants::ATTRIBUTE_SEGMENT_FILTER);
        
        if (empty($segmentFilterValue)) {
            return [];
        }

        $segmentIds = explode(Constants::DELIMITER_COMMA, $segmentFilterValue);
        $segmentIds = array_filter(array_map('intval', $segmentIds));
        
        if (empty($segmentIds)) {
            return [];
        }

        $customerGroups = $this->getCustomerGroups($segmentIds);
        
        if (empty($customerGroups)) {
            return [];
        }

        $productId = $item[Constants::ARRAY_KEY_PRODUCT_ID] ?? null;
        if (!$productId) {
            return [];
        }

        return array_filter($permissions, function ($permission) use ($productId, $customerGroups) {
            return isset($permission[Constants::COLUMN_PRODUCT_ID]) &&
                $permission[Constants::COLUMN_PRODUCT_ID] == $productId &&
                isset($permission[Constants::COLUMN_CUSTOMER_GROUP_ID]) &&
                in_array($permission[Constants::COLUMN_CUSTOMER_GROUP_ID], $customerGroups, true) &&
                isset($permission[Constants::PERMISSION_GRANT_CATEGORY_VIEW]);
        });
    }

    /**
     * Get the associated customer group ids from the segment ids
     *
     * @param array $segmentIds
     * @return array
     */
    private function getCustomerGroups(array $segmentIds): array
    {
        if (empty($segmentIds)) {
            return [];
        }

        $customerGroupIds = [];
        $segments = $this->segmentHelper->getSegmentsByIds($segmentIds);
        
        foreach ($segments as $segment) {
            $groupId = (int) $segment->getGroup();
            if ($groupId > 0 && !in_array($groupId, $customerGroupIds, true)) {
                $customerGroupIds[] = $groupId;
            }
        }

        return $customerGroupIds;
    }

    /**
     * Get the permission value of the category view
     *
     * @param array $filteredPermissions
     * @return string|null
     */
    private function getGrantCategoryViewValue(array $filteredPermissions): ?string
    {
        foreach ($filteredPermissions as $permission) {
            if (isset($permission[Constants::PERMISSION_GRANT_CATEGORY_VIEW])) {
                $value = $permission[Constants::PERMISSION_GRANT_CATEGORY_VIEW];
                if ($value === (string) Constants::PERMISSION_USE_PARENT) {
                    return $value;
                }
            }
        }
        
        return null;
    }
}


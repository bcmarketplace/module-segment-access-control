<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Observer;

use BCMarketplace\SegmentAccessControl\Helper\Data as SegmentHelper;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission as CategoryPermission;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver;
use Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;

/**
 * Observer plugin for applying category permissions based on segments
 * Optimized for performance with early returns and efficient loops
 */
class ApplyCategoryPermissionObserverPlugin extends ApplyCategoryPermissionObserver
{
    /**
     * Segment helper data
     *
     * @var SegmentHelper
     */
    protected SegmentHelper $helperData;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param \Magento\CatalogPermissions\Helper\Data $catalogPermData
     * @param ApplyPermissionsOnCategory $applyPermissionsOnCategory
     * @param SegmentHelper $helperData
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $customerSession,
        Index $permissionIndex,
        \Magento\CatalogPermissions\Helper\Data $catalogPermData,
        ApplyPermissionsOnCategory $applyPermissionsOnCategory,
        SegmentHelper $helperData
    ) {
        parent::__construct(
            $permissionsConfig,
            $storeManager,
            $customerSession,
            $permissionIndex,
            $catalogPermData,
            $applyPermissionsOnCategory
        );
        $this->helperData = $helperData;
    }

    /**
     * Apply category permissions for category collection
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer): self
    {
        // Early return if module is disabled
        if (!$this->helperData->isEnabled()) {
            return parent::execute($observer);
        }

        // Early return if permissions are disabled
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $category = $observer->getEvent()->getCategory();
        $customer = $this->_customerSession->getCustomer();
        
        // Early return if customer is not logged in
        if (!$customer || !$customer->getId()) {
            return parent::execute($observer);
        }

        // Get segment filter attribute
        $segmentFilterValue = $customer->getData(Constants::ATTRIBUTE_SEGMENT_FILTER);
        
        // Early return if no segments assigned
        if (empty($segmentFilterValue)) {
            return parent::execute($observer);
        }

        // Normalize segment IDs
        $segmentIds = $this->normalizeSegmentIds($segmentFilterValue);
        
        if (empty($segmentIds)) {
            return parent::execute($observer);
        }

        // Get customer group IDs from segments
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customerGroupId = $customer->getGroupId();
        
        // Get all customer group IDs associated with customer's segments
        $customerGroupIds = $this->_permissionIndex->getCustomerGroupIds(
            (int)$customerGroupId,
            (int)$websiteId,
            false
        );
        
        if (empty($customerGroupIds)) {
            return parent::execute($observer);
        }

        // Get permissions for the category using all customer group IDs
        $categoryId = $category->getId();
        
        // getIndexForCategory accepts int|int[] for categoryId and int for customerGroupId
        // We need to get permissions for each customer group and merge them
        $permissions = [];
        foreach ($customerGroupIds as $groupId) {
            $groupPermissions = $this->_permissionIndex->getIndexForCategory(
                $categoryId,
                (int)$groupId,
                $websiteId
            );
            
            if (is_array($groupPermissions)) {
                if (isset($groupPermissions[0]) && is_array($groupPermissions[0])) {
                    // Multiple permissions returned
                    $permissions = array_merge($permissions, $groupPermissions);
                } else {
                    // Single permission returned
                    $permissions[] = $groupPermissions;
                }
            }
        }

        // Apply permissions efficiently - stop at first allowed permission
        foreach ($permissions as $permission) {
            $category->setPermissions($permission);
            
            // Break early if permission is allowed to avoid overriding
            if (isset($permission[Constants::PERMISSION_GRANT_CATEGORY_VIEW]) &&
                $permission[Constants::PERMISSION_GRANT_CATEGORY_VIEW] === (string)CategoryPermission::PERMISSION_ALLOW
            ) {
                break;
            }
        }

        $this->applyPermissionsOnCategory->execute($category);
        
        if ($observer->getEvent()->getCategory()->getIsHidden()) {
            $observer->getEvent()->getControllerAction()->getResponse()->setRedirect(
                $this->_catalogPermData->getLandingPageUrl()
            );

            throw new \Magento\Framework\Exception\LocalizedException(
                __('You may need more permissions to access this category.')
            );
        }
        
        return $this;
    }

    /**
     * Normalize segment IDs from string to array
     *
     * @param string $segmentFilterValue
     * @return array
     */
    private function normalizeSegmentIds(string $segmentFilterValue): array
    {
        if (str_contains($segmentFilterValue, Constants::DELIMITER_COMMA)) {
            return array_filter(
                array_map('intval', explode(Constants::DELIMITER_COMMA, $segmentFilterValue))
            );
        }
        
        $id = (int) $segmentFilterValue;
        return $id > 0 ? [$id] : [];
    }
}

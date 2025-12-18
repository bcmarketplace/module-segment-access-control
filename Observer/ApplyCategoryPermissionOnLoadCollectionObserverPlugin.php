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
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionOnLoadCollectionObserver;
use Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;

/**
 * Observer plugin for applying category permissions on collection load
 * Optimized for segment-based access control
 */
class ApplyCategoryPermissionOnLoadCollectionObserverPlugin extends ApplyCategoryPermissionOnLoadCollectionObserver
{
    /**
     * Permissions index instance
     *
     * @var Index
     */
    protected $_permissionIndex;

    /**
     * Customer session instance
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * @var ApplyPermissionsOnCategory
     */
    protected $applyPermissionsOnCategory;

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
     * @param ApplyPermissionsOnCategory $applyPermissionsOnCategory
     * @param SegmentHelper $helperData
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $customerSession,
        Index $permissionIndex,
        ApplyPermissionsOnCategory $applyPermissionsOnCategory,
        SegmentHelper $helperData
    ) {
        parent::__construct(
            $permissionsConfig,
            $storeManager,
            $customerSession,
            $permissionIndex,
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
        if (!$this->helperData->isEnabled()) {
            return parent::execute($observer);
        }

        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $permissions = [];
        $categoryCollection = $observer->getEvent()->getCategoryCollection();
        $categoryIds = $categoryCollection->getColumnValues('entity_id');

        if ($categoryIds) {
            $permissions = $this->_permissionIndex->getIndexForCategory(
                $categoryIds,
                $this->_customerSession->getCustomerGroupId(),
                $this->_storeManager->getStore()->getWebsiteId()
            );
        }

        foreach ($permissions as $permission) {
            // Accommodating the permissions by segment
            $categoryId = $permission[Constants::COLUMN_CATEGORY_ID] ?? null;
            if ($categoryId) {
                $category = $categoryCollection->getItemById($categoryId);
                if ($category) {
                    $category->setPermissions($permission);
                }
            }
        }

        foreach ($categoryCollection as $category) {
            $this->applyPermissionsOnCategory->execute($category);
        }

        return $this;
    }
}

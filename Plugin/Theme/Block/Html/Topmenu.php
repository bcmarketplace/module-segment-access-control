<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace BCMarketplace\SegmentAccessControl\Plugin\Theme\Block\Html;

use BCMarketplace\SegmentAccessControl\Helper\Data;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session\Storage as CustomerSessionStorage;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin for \Magento\Theme\Block\Html\Topmenu
 */
class Topmenu extends \Magento\CatalogPermissions\Plugin\Theme\Block\Html\Topmenu
{
    /**
     * @var \BCMarketplace\SegmentAccessControl\Helper\Data
     */
    private $helperData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;


    public function __construct(
        ConfigInterface $catalogPermissionsConfig,
        CustomerSessionStorage $customerSessionStorage,
        \BCMarketplace\SegmentAccessControl\Helper\Data $helperData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($catalogPermissionsConfig,$customerSessionStorage );
        $this->_storeManager = $context->getStoreManager();
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
    }

    /**
     * Add segment IDs to cache key for proper cache variation
     *
     * @param \Magento\Theme\Block\Html\Topmenu $subject
     * @param array $result
     * @return array
     */
    public function afterGetCacheKeyInfo(\Magento\Theme\Block\Html\Topmenu $subject, array $result): array
    {
        try {
            $storeId = $this->_storeManager->getStore()->getId();
            
            if (!$this->helperData->isEnabled()) {
                return parent::afterGetCacheKeyInfo($subject, $result);
            }

            $customer = $this->customerSession->getCustomer();
            if (!$customer || !$customer->getId()) {
                return parent::afterGetCacheKeyInfo($subject, $result);
            }

            $segmentFilterValue = $customer->getData(Constants::ATTRIBUTE_SEGMENT_FILTER);
            
            if (!empty($segmentFilterValue)) {
                $segmentIds = explode(Constants::DELIMITER_COMMA, $segmentFilterValue);
                $segmentIds = array_filter(array_map('intval', $segmentIds));
                sort($segmentIds);
                
                if (!empty($segmentIds)) {
                    $result[Constants::ARRAY_KEY_CUSTOMER_GROUP_ID] = implode('_', $segmentIds);
                    return $result;
                }
            }
        } catch (NoSuchEntityException $e) {
            // Silently fail and return parent result
        }

        return parent::afterGetCacheKeyInfo($subject, $result);
    }
}

<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Plugin;

use BCMarketplace\SegmentAccessControl\Helper\Data;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CustomerRepositoryPlugin
{
    private Data $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        LoggerInterface $logger
    )
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * This will add the default segment to the customer when creating it
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     *
     *
     * @return array
     */
    /**
     * Add default segment to customer when creating it
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return array
     */
    public function beforeSave(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $customer,
        ?string $passwordHash = null
    ): array {
        $storeId = $customer->getStoreId() ?? $this->storeManager->getStore()->getId();
        
        if (!$this->helper->isEnabled()) {
            return [$customer, $passwordHash];
        }

        $segmentFilterAttribute = $customer->getCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER);
        $current = $segmentFilterAttribute instanceof \Magento\Framework\Api\AttributeInterface
            ? $segmentFilterAttribute->getValue()
            : null;

        $segmentApprovalAttribute = $customer->getCustomAttribute(Constants::ATTRIBUTE_SEGMENT_APPROVAL);
        $segmentApproval = $segmentApprovalAttribute instanceof \Magento\Framework\Api\AttributeInterface
            ? $segmentApprovalAttribute->getValue()
            : null;

        $defaultSegment = $this->helper->getGeneralConfig(Constants::CONFIG_DEFAULT_SEGMENT, $storeId);

        if ($defaultSegment && empty($current)) {
            $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER, $defaultSegment);
        } else {
            $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER, $current);
            $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_APPROVAL, $segmentApproval);
        }

        return [$customer, $passwordHash];
    }
}

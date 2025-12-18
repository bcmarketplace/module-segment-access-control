<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get segment class
 */
class Segment implements ArgumentInterface
{
    /** @var CollectionFactory  */
    private $_segmentCollectionFactory;

    /** @var Options */
    protected $_options;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CollectionFactory $segmentCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $segmentCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->_segmentCollectionFactory = $segmentCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array|Options
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllOptions()
    {
        /** @var  Magento\Store\Model\StoreManagerInterface $websiteId */
        $websiteId = $this->storeManager->getWebsite()->getId();
        if (!$this->_options) {
            $this->_options = $this->_segmentCollectionFactory
                ->create()
                ->addFieldToFilter('website_id', $websiteId)
                ->loadData()
                ->toOptionArray();
        }
        return $this->_options;
    }
}

<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Model\Entity\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory;

class SegmentList extends AbstractSource
{
   /** @var CollectionFactory  */
    private $_segmentCollectionFactory;

    /** @var Options */
    protected $_options;

    /**
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        CollectionFactory $segmentCollectionFactory
    ) {
        $this->_segmentCollectionFactory = $segmentCollectionFactory;
    }

    /**
     * @return array|Options
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = $this->_segmentCollectionFactory->create()->loadData()->toOptionArray();
        }
        return $this->_options;
    }
}

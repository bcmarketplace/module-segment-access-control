<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * segment collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'segment_id';


    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\BCMarketplace\SegmentAccessControl\Model\Manage::class, \BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage::class);
    }
}

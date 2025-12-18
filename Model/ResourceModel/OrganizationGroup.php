<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;

/**
 * Organization Group Resource Model
 */
class OrganizationGroup extends AbstractDb
{
    /**
     * Construct
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Constants::TABLE_SEGMENT_ENTITY, Constants::COLUMN_SEGMENT_ID);
    }
}


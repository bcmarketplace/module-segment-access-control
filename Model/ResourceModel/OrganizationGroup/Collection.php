<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Model\ResourceModel\OrganizationGroup;

use BCMarketplace\SegmentAccessControl\Model\OrganizationGroup;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\OrganizationGroup as OrganizationGroupResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Organization Group Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(OrganizationGroup::class, OrganizationGroupResource::class);
    }
}


<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Model\ResourceModel\Company;

use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Company\Model\ResourceModel\Company\Collection as companyCollection;

class Collection extends companyCollection
{
    /**
     * @return $this|Collection
     */
    public function joinAdvancedCustomerEntityTable()
    {
        $this->getSelect()->joinLeft(
            ['advanced_customer_entity' => $this->getTable('company_advanced_customer_entity')],
            'main_table.entity_id = advanced_customer_entity.company_id'
            . ' AND advanced_customer_entity.customer_id = main_table.super_user_id',
            [
                'job_title' => 'advanced_customer_entity.job_title',
                Constants::ATTRIBUTE_SEGMENT_APPROVAL => 'advanced_customer_entity.' . Constants::ATTRIBUTE_SEGMENT_APPROVAL,
                Constants::ATTRIBUTE_SEGMENT_FILTER => 'advanced_customer_entity.' . Constants::ATTRIBUTE_SEGMENT_FILTER
            ]
        );

        $this->addFilterToMap('job_title', 'advanced_customer_entity.job_title');
        $this->addFilterToMap(
            Constants::ATTRIBUTE_SEGMENT_APPROVAL,
            'advanced_customer_entity.' . Constants::ATTRIBUTE_SEGMENT_APPROVAL
        );
        $this->addFilterToMap(
            Constants::ATTRIBUTE_SEGMENT_FILTER,
            'advanced_customer_entity.' . Constants::ATTRIBUTE_SEGMENT_FILTER
        );

        return $this;
    }
}

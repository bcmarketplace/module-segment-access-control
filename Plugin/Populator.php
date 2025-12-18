<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Plugin;

use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Company\Model\Action\Customer\Populator as CompanyPopulator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class Populator
{
    /** @var CustomerRepositoryInterface  */
    protected $_customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory
    ) {
        $this->_customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Populate customer segment attributes from company attributes
     *
     * @param CompanyPopulator $subject
     * @param array $data
     * @param CustomerInterface|null $customer
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function beforePopulate(CompanyPopulator $subject, array $data, CustomerInterface $customer = null): array
    {
        if ($customer === null) {
            $customer = $this->customerFactory->create();
        }

        // Handle segment_filter attribute
        if (isset($data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_FILTER])) {
            $segmentFilterArray = $data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_FILTER];
            
            if (!empty($segmentFilterArray) && is_array($segmentFilterArray)) {
                $segmentFilterString = implode(Constants::DELIMITER_COMMA, array_filter($segmentFilterArray));
                
                if (!empty($segmentFilterString)) {
                    $customerId = $data['customer_id'] ?? null;
                    
                    if ($customerId) {
                        $customer = $this->_customerRepository->getById($customerId);
                        $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER, $segmentFilterString);
                        $this->_customerRepository->save($customer);
                    } else {
                        $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER, $segmentFilterString);
                    }

                    $data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_FILTER] = $segmentFilterString;
                }
            }
        }

        // Handle segment_approval attribute
        if (isset($data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_APPROVAL])) {
            $segmentApprovalArray = $data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_APPROVAL];
            
            if (!empty($segmentApprovalArray) && is_array($segmentApprovalArray)) {
                $segmentApprovalString = implode(Constants::DELIMITER_COMMA, array_filter($segmentApprovalArray));
                
                if (!empty($segmentApprovalString)) {
                    $customerId = $data['customer_id'] ?? null;
                    
                    if ($customerId) {
                        $customer = $this->_customerRepository->getById($customerId);
                        $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_APPROVAL, $segmentApprovalString);
                        $this->_customerRepository->save($customer);
                    } else {
                        $customer->setCustomAttribute(Constants::ATTRIBUTE_SEGMENT_APPROVAL, $segmentApprovalString);
                    }
                    
                    $data['extension_attributes']['company_attributes'][Constants::ATTRIBUTE_SEGMENT_APPROVAL] = $segmentApprovalString;
                }
            }
        }

        return array($data, $customer);
    }
}

<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author: Bhavesh Jalondhara <bhavesh.jalondhara@wagento.com>
 **/

namespace BCMarketplace\SegmentAccessControl\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use BCMarketplace\SegmentAccessControl\Model\Entity\Attribute\Source\SegmentList;
use Magento\Customer\Model\Metadata\CustomerMetadata;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Config;

class CustomerAttributes implements DataPatchInterface
{


    /** @var ModuleDataSetupInterface  */
    private $moduleDataSetup;

    /** @var CustomerSetupFactory  */
    private $customerSetupFactory;

    /** @var Config  */
    private $eavConfig;

    /** @var AttributeSetFactory  */
    private $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param Config $eavConfig
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        Config $eavConfig,
        AttributeSetFactory $attributeSetFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * @return array
     */
    public static function getDependencies() : array
    {
        return [];
    }

    /**
     * @return CustomerAttributes|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $this->eavConfig->getEntityType(CustomerMetadata::ENTITY_TYPE_CUSTOMER);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $eavSetup->addAttribute(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            Constants::ATTRIBUTE_SEGMENT_FILTER,
            [
                'type' => Constants::ATTRIBUTE_TYPE_VARCHAR,
                'group' => Constants::ATTRIBUTE_GROUP_GENERAL,
                'input' => Constants::ATTRIBUTE_INPUT_MULTISELECT,
                'backend' => '',
                'frontend' => '',
                'source' => SegmentList::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'label' => 'Segments',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system' => false,
                'visible_on_front' => false,
                'position' => Constants::DEFAULT_POSITION
            ]
        );

        $eavSetup->addAttributeToSet(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            CustomerMetadata::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            Constants::ATTRIBUTE_SEGMENT_FILTER
        );

        $attribute = $eavSetup->getEavConfig()->getAttribute(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            Constants::ATTRIBUTE_SEGMENT_FILTER
        );
        $attribute->addData([
            'used_in_forms' => [Constants::ATTRIBUTE_FORM_ADMINHTML_CUSTOMER],
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
        ]);
        $attribute->save();

        $eavSetup->addAttribute(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            Constants::ATTRIBUTE_SEGMENT_APPROVAL,
            [
                'type' => Constants::ATTRIBUTE_TYPE_VARCHAR,
                'group' => Constants::ATTRIBUTE_GROUP_GENERAL,
                'input' => Constants::ATTRIBUTE_INPUT_MULTISELECT,
                'backend' => '',
                'frontend' => '',
                'source' => SegmentList::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'label' => 'Supervised segments',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system' => false,
                'visible_on_front' => false,
                'position' => Constants::DEFAULT_POSITION
            ]
        );

        $eavSetup->addAttributeToSet(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            CustomerMetadata::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            Constants::ATTRIBUTE_SEGMENT_APPROVAL
        );

        $attributeApproval = $eavSetup->getEavConfig()->getAttribute(
            CustomerMetadata::ENTITY_TYPE_CUSTOMER,
            Constants::ATTRIBUTE_SEGMENT_APPROVAL
        );
        $attributeApproval->addData([
            'used_in_forms' => [Constants::ATTRIBUTE_FORM_ADMINHTML_CUSTOMER],
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
        ]);
        $attributeApproval->save();

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public function getAliases() : array
    {
        return [];
    }
}

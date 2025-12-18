<?php
declare(strict_types=1);

namespace BCMarketplace\SegmentAccessControl\Model\ResourceModel\Permission;

use BCMarketplace\SegmentAccessControl\Helper\Data;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index as CatalogPermissionsIndex;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\Flat\Collection as FlatCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogPermissions\Helper\Data as Helper;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\Data\Collection\AbstractDb as AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory as SegmentsCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use BCMarketplace\SegmentAccessControl\Helper\Data as helperData;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;


class Index extends CatalogPermissionsIndex
{
    /**
     * Segment collection factory.
     *
     * @var Collection
     */
    private $_segmentCollectionFactory;

    /**
     * Catalog permissions data
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Segment helper data.
     *
     * @var HelperData
     */
    protected $helperData;

    /**
     * Customer repository.
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer session.
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $isExecutedAlready = false;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Helper $helper,
        StoreManagerInterface $storeManager,
        SegmentsCollectionFactory $segmentCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        helperData $helperData,
        TableMaintainer $tableMaintainer,
        $connectionName = null
    ) {
        $this->_segmentCollectionFactory = $segmentCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        $this->tableMaintainer = $tableMaintainer;
        parent::__construct($context,$helper,$storeManager,$connectionName);
    }

    /**
     * Retrieve permission index for category or categories with specified customer group and website id
     *
     * @param int|int[] $categoryId
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getIndexForCategory($categoryId, $customerGroupId = null, $websiteId = null)
    {
        if (!$this->helperData->isEnabled()) {
            return parent::getIndexForCategory($categoryId, $customerGroupId, $websiteId);
        }
        
        $customerGroupIds = [];
        if (!empty($customerGroupId)) {
            $customerGroupIds = $this->getCustomerGroupIds($customerGroupId, $websiteId);
        }

        $connection = $this->getConnection();
        if (!is_array($categoryId)) {
            $categoryId = [$categoryId];
        }

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where(Constants::COLUMN_CATEGORY_ID . ' IN (?)', $categoryId);
            
        if (!empty($customerGroupIds)) {
            $select->where(Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (?)', $customerGroupIds);
        }
        
        if ($websiteId !== null) {
            $select->where(Constants::COLUMN_WEBSITE_ID . ' = ?', $websiteId);
        }

        return (!empty($customerGroupIds) && $websiteId !== null)
            ? $connection->fetchAll($select)
            : $connection->fetchAssoc($select);
    }

    /**
     * Add index to category collection
     *
     * @param CategoryCollection|FlatCollection|AbstractCollection $collection
     * @param int $customerGroupId
     * @param int $websiteId
     * @return $this
     */
    public function addIndexToCategoryCollection(AbstractCollection $collection, $customerGroupId, $websiteId)
    {
        $customer = $this->customerSession->getCustomer();
        $storeId = $customer ? $customer->getStoreId() : null;
        
        if (!$this->helperData->isEnabled() || !$storeId) {
            return parent::addIndexToCategoryCollection($collection, $customerGroupId, $websiteId);
        }
        
        $customerGroupIds = [];
        if (!empty($customerGroupId)) {
            $customerGroupIds = $this->getCustomerGroupIds($customerGroupId, $websiteId);
        }
        
        if (empty($customerGroupIds)) {
            return parent::addIndexToCategoryCollection($collection, $customerGroupId, $websiteId);
        }

        $connection = $this->getConnection();
        $tableAlias = $collection instanceof FlatCollection ? 'main_table' : 'e';

        $collection->getSelect()->group($tableAlias . '.entity_id');

        $collection->getSelect()->joinLeft(
            ['perm' => $this->getMainTable()],
            'perm.' . Constants::COLUMN_CATEGORY_ID . ' = ' . $tableAlias . '.entity_id' . 
            ' AND ' . $connection->quoteInto('perm.' . Constants::COLUMN_WEBSITE_ID . ' = ?', $websiteId) . 
            ' AND ' . $connection->quoteInto('perm.' . Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (?)', $customerGroupIds),
            []
        );

        if (!$this->helper->isAllowedCategoryView()) {
            $collection->getSelect()->where(
                'perm.' . Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' = ?',
                Permission::PERMISSION_ALLOW
            );
        } else {
            $collection->getSelect()->where(
                'perm.' . Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' != ? OR perm.' . 
                Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' IS NULL',
                Permission::PERMISSION_DENY
            );
        }

        return $this;
    }

    /**
     * Retrieve restricted category ids for customer group and website
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getRestrictedCategoryIds($customerGroupId, $websiteId)
    {
        if (!$this->helperData->isEnabled()) {
            return parent::getRestrictedCategoryIds($customerGroupId, $websiteId);
        }
        
        $customerGroupIds = [];
        if ($customerGroupId !== null) {
            $customerGroupIds = $this->getCustomerGroupIds($customerGroupId, $websiteId);
        }

        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            Constants::COLUMN_CATEGORY_ID
        )->where(
            Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' = :grant_catalog_category_view'
        );
        $bind = [];
        if ($customerGroupId !== null) {
            $select->where(Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (:customer_group_id)');
            $bind[':customer_group_id'] = $customerGroupIds;
        }
        if ($websiteId) {
            $select->where(Constants::COLUMN_WEBSITE_ID . ' = :website_id');
            $bind[':website_id'] = $websiteId;
        }
        if (!$this->helper->isAllowedCategoryView()) {
            $bind[':grant_catalog_category_view'] = Permission::PERMISSION_ALLOW;
        } else {
            $bind[':grant_catalog_category_view'] = Permission::PERMISSION_DENY;
        }

        $restrictedCatIds = $connection->fetchCol($select, $bind);

        $select = $connection->select()->from($this->getTable('catalog_category_entity'), 'entity_id');

        if (!empty($restrictedCatIds) && !$this->helper->isAllowedCategoryView()) {
            $select->where('entity_id NOT IN(?)', $restrictedCatIds);
        } elseif (!empty($restrictedCatIds) && $this->helper->isAllowedCategoryView()) {
            $select->where('entity_id IN(?)', $restrictedCatIds);
        } elseif ($this->helper->isAllowedCategoryView()) {
            // category view allowed for all
            $select->where('1 = 0');
        }

        return $connection->fetchCol($select);
    }

    /**
     * Get customer group ids.
     *
     * @param $customerGroupId
     * @param $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    /**
     * Get customer group IDs based on customer's segments
     * Optimized with caching and proper type handling
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @param bool $excludeCurrentCustomerGroup
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerGroupIds(
        int $customerGroupId,
        int $websiteId,
        bool $excludeCurrentCustomerGroup = false
    ): array {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        $customer = $this->customerSession->getCustomer();
        $customerId = $customer ? $customer->getId() : null;
        
        if (!$customerId) {
            return [];
        }

        // Get segment filter attribute using constant
        $segmentFilter = $customer->getCustomAttribute(Constants::ATTRIBUTE_SEGMENT_FILTER);
        $segmentFilterValue = $segmentFilter && $segmentFilter->getValue() 
            ? $segmentFilter->getValue() 
            : $this->helperData->getGeneralConfig(Constants::CONFIG_DEFAULT_SEGMENT);

        if (empty($segmentFilterValue)) {
            return [];
        }

        // Normalize segment IDs
        $segmentIds = $this->normalizeSegmentIds($segmentFilterValue);
        
        if (empty($segmentIds)) {
            return [];
        }

        // Batch load segments
        $segmentCollection = $this->_segmentCollectionFactory->create()
            ->addFieldToSelect([Constants::COLUMN_SEGMENT_ID, Constants::COLUMN_GROUP, Constants::COLUMN_WEBSITE_ID])
            ->addFieldToFilter(Constants::COLUMN_SEGMENT_ID, ['in' => $segmentIds])
            ->addFieldToFilter(Constants::COLUMN_WEBSITE_ID, $websiteId);

        $customerGroupIds = [];
        foreach ($segmentCollection as $segment) {
            $groupId = (int) $segment->getGroup();
            if ($groupId > 0 && !in_array($groupId, $customerGroupIds, true)) {
                $customerGroupIds[] = $groupId;
            }
        }

        // Add current customer group if not excluded and not already present
        if (!$excludeCurrentCustomerGroup && !in_array($customerGroupId, $customerGroupIds, true)) {
            array_unshift($customerGroupIds, $customerGroupId);
        }

        return $customerGroupIds;
    }

    /**
     * Normalize segment IDs from string to array
     *
     * @param string $segmentFilterValue
     * @return array
     */
    private function normalizeSegmentIds(string $segmentFilterValue): array
    {
        if (str_contains($segmentFilterValue, Constants::DELIMITER_COMMA)) {
            return array_filter(
                array_map('intval', explode(Constants::DELIMITER_COMMA, $segmentFilterValue)),
                fn($id) => $id > 0
            );
        }
        
        $id = (int) $segmentFilterValue;
        return $id > 0 ? [$id] : [];
    }

    /**
     * Add index select in product collection
     *
     * @param ProductCollection $collection
     * @param int $customerGroupId
     * @return $this
     */
    public function addIndexToProductCollection(ProductCollection $collection, $customerGroupId)
    {
        if (!$this->helperData->isEnabled()) {
            return parent::addIndexToProductCollection($collection, $customerGroupId);
        }
        $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
        $connection = $this->getConnection();

        $fromPart = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $limitationFilters = $collection->getLimitationFilters();
        $categoryId = $limitationFilters[Constants::ARRAY_KEY_CATEGORY_ID] ?? null;

        $conditions = [$connection->quoteInto('perm.customer_group_id in (?)', $this->getCustomerGroupIds($customerGroupId,$websiteId))];

        if (!$categoryId || $categoryId == $this->storeManager->getStore(
                $collection->getStoreId()
            )->getRootCategoryId()
        ) {
            $conditions[] = 'perm.' . Constants::COLUMN_PRODUCT_ID . ' = cat_index.product_id';
            $conditions[] = $connection->quoteInto('perm.' . Constants::COLUMN_STORE_ID . ' = ?', $collection->getStoreId());
            $joinConditions = join(' AND ', $conditions);
            $tableName = $this->tableMaintainer->resolveMainTableNameProduct($customerGroupId);

            if (!isset($fromPart['perm'])) {
                $collection->getSelect()->joinLeft(
                    ['perm' => $tableName],
                    $joinConditions,
                    [
                        Constants::PERMISSION_GRANT_CATEGORY_VIEW,
                        Constants::PERMISSION_GRANT_PRODUCT_PRICE,
                        Constants::PERMISSION_GRANT_CHECKOUT_ITEMS
                    ]
                );
            }
        } else {
            $conditions[] = 'perm.' . Constants::COLUMN_CATEGORY_ID . ' = cat_index.category_id';
            $conditions[] = $connection->quoteInto(
                'perm.' . Constants::COLUMN_WEBSITE_ID . ' = ?',
                $this->storeManager->getStore($collection->getStoreId())->getWebsiteId()
            );
            $joinConditions = join(' AND ', $conditions);
            $tableName = $this->tableMaintainer->resolveMainTableNameCategory($customerGroupId);

            if (!isset($fromPart['perm'])) {
                $collection->getSelect()->joinLeft(
                    ['perm' => $tableName],
                    $joinConditions,
                    [
                        Constants::PERMISSION_GRANT_CATEGORY_VIEW,
                        Constants::PERMISSION_GRANT_PRODUCT_PRICE,
                        Constants::PERMISSION_GRANT_CHECKOUT_ITEMS
                    ]
                );
            }
        }
        /** need to retrieve distinct products collection because using getCustomerGroupIds in
         * condition will return duplicate products which will generate exception
         */
    $collection->getSelect()->distinct(true)->group('e.entity_id');
        if (isset($fromPart['perm'])) {
            $fromPart['perm']['tableName'] = $tableName;
            $fromPart['perm']['joinCondition'] = $joinConditions;
            $collection->getSelect()->setPart(\Magento\Framework\DB\Select::FROM, $fromPart);
            return $this;
        }

        if (!$this->helper->isAllowedCategoryView()) {
            $collection->getSelect()->where(
                'perm.' . Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' = ?',
                Permission::PERMISSION_ALLOW
            );
        } else {
            $collection->getSelect()->where(
                'perm.' . Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' != ? OR perm.' . 
                Constants::PERMISSION_GRANT_CATEGORY_VIEW . ' IS NULL',
                Permission::PERMISSION_DENY
            );
        }

        $this->addLinkLimitation($collection);

        return $this;
    }

    /**
     * Add permission index to product model
     *
     * @param Product $product
     * @param int $customerGroupId
     * @return $this
     */
    public function addIndexToProduct($product, $customerGroupId)
    {
        if (!$this->helperData->isEnabled()) {
            return parent::addIndexToProduct($product, $customerGroupId);
        }

        $connection = $this->getConnection();
        $websiteId = $this->storeManager->getStore($product->getStoreId())->getWebsiteId();
        $customerGroupIds = $this->getCustomerGroupIds($customerGroupId, $websiteId);

        if (empty($customerGroupIds)) {
            return parent::addIndexToProduct($product, $customerGroupId);
        }

        $permissionFields = [
            Constants::PERMISSION_GRANT_CATEGORY_VIEW,
            Constants::PERMISSION_GRANT_PRODUCT_PRICE,
            Constants::PERMISSION_GRANT_CHECKOUT_ITEMS
        ];

        if ($product->getCategory()) {
            $select = $connection->select()
                ->from(['perm' => $this->getMainTable()], $permissionFields)
                ->where(Constants::COLUMN_CATEGORY_ID . ' = ?', $product->getCategory()->getId())
                ->where(Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (?)', $customerGroupIds)
                ->where(Constants::COLUMN_WEBSITE_ID . ' = ?', $websiteId);
        } else {
            $select = $connection->select()
                ->from(['perm' => $this->getProductTable()], $permissionFields)
                ->where(Constants::COLUMN_PRODUCT_ID . ' = ?', $product->getId())
                ->where(Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (?)', $customerGroupIds)
                ->where(Constants::COLUMN_STORE_ID . ' = ?', $product->getStoreId());
        }

        $permission = $connection->fetchRow($select);
        if ($permission) {
            $product->addData($permission);
        }

        return $this;
    }

    /**
     * Get permission index for products
     *
     * @param int|int[] $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function getIndexForProduct($productId, $customerGroupId, $storeId)
    {
        if (!is_array($productId)) {
            $productId = [$productId];
        }

        if (!$this->helperData->isEnabled()) {
            return parent::getIndexForProduct($productId, $customerGroupId, $storeId);
        }

        $connection = $this->getConnection();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $customerGroupIds = $this->getCustomerGroupIds($customerGroupId, $websiteId);

        $select = $connection->select()->from(
            ['perm' => $this->getProductTable()],
            [
                Constants::COLUMN_PRODUCT_ID,
                Constants::PERMISSION_GRANT_CATEGORY_VIEW,
                Constants::PERMISSION_GRANT_PRODUCT_PRICE,
                Constants::PERMISSION_GRANT_CHECKOUT_ITEMS,
                Constants::COLUMN_CUSTOMER_GROUP_ID
            ]
        )->where(Constants::COLUMN_PRODUCT_ID . ' IN (?)', $productId)
        ->where(Constants::COLUMN_STORE_ID . ' = ?', $storeId);

        if (!empty($customerGroupIds)) {
            $select->where(Constants::COLUMN_CUSTOMER_GROUP_ID . ' IN (?)', $customerGroupIds);
        }

        return empty($customerGroupIds) 
            ? $connection->fetchAll($select) 
            : $connection->fetchAssoc($select);
    }

}

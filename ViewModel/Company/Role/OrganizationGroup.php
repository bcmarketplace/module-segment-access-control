<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\ViewModel\Company\Role;

use BCMarketplace\SegmentAccessControl\Api\Data\OrganizationGroupInterface;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\OrganizationGroup\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ViewModel for Organization Group form fields
 */
class OrganizationGroup implements ArgumentInterface
{
    /**
     * @var CollectionFactory
     */
    private $organizationGroupCollectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrganizationGroupInterface|null
     */
    private $organizationGroup = null;

    /**
     * @param CollectionFactory $organizationGroupCollectionFactory
     * @param RequestInterface $request
     * @param CompanyContext $companyContext
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $organizationGroupCollectionFactory,
        RequestInterface $request,
        CompanyContext $companyContext,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->organizationGroupCollectionFactory = $organizationGroupCollectionFactory;
        $this->request = $request;
        $this->companyContext = $companyContext;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Get role ID from request
     *
     * @return int|null
     */
    public function getRoleId(): ?int
    {
        $roleId = $this->request->getParam('id');
        return $roleId ? (int)$roleId : null;
    }

    /**
     * Get organization group for current role
     *
     * @return OrganizationGroupInterface|null
     */
    public function getOrganizationGroup(): ?OrganizationGroupInterface
    {
        if ($this->organizationGroup === null) {
            $roleId = $this->getRoleId();
            if ($roleId) {
                try {
                    $collection = $this->organizationGroupCollectionFactory->create();
                    $collection->addFieldToFilter('company_role_id', $roleId);
                    $this->organizationGroup = $collection->getFirstItem();
                    if (!$this->organizationGroup->getId()) {
                        $this->organizationGroup = null;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error loading organization group: ' . $e->getMessage());
                    $this->organizationGroup = null;
                }
            }
        }
        return $this->organizationGroup;
    }

    /**
     * Get categories for current store/company in tree format
     *
     * @return array
     */
    public function getCategories(): array
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            
            // Load all active categories
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('parent_id')
                ->addAttributeToFilter('is_active', 1)
                ->setStoreId($storeId)
                ->addAttributeToSort('path')
                ->load();

            // Build category tree structure
            $categoryTree = [];
            $categoryMap = [];
            
            // First pass: create map of all categories
            foreach ($collection as $category) {
                if ($category->getId() == 1) {
                    continue; // Skip root category
                }
                $categoryMap[$category->getId()] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'parent_id' => $category->getParentId(),
                    'level' => $category->getLevel(),
                    'path' => $category->getPath(),
                    'children' => []
                ];
            }
            
            // Second pass: build tree structure
            foreach ($categoryMap as $categoryId => $categoryData) {
                $parentId = $categoryData['parent_id'];
                // Check if parent exists in map and is not root (ID 1)
                if ($parentId && $parentId != 1 && isset($categoryMap[$parentId])) {
                    // Add as child of parent
                    $categoryMap[$parentId]['children'][] = $categoryMap[$categoryId];
                } else {
                    // Top level category (parent is root or doesn't exist in collection)
                    $categoryTree[] = $categoryMap[$categoryId];
                }
            }
            
            // Sort tree by path to maintain order
            usort($categoryTree, function ($a, $b) {
                return strcmp($a['path'], $b['path']);
            });
            
            // Third pass: flatten tree with proper tree indicators
            return $this->flattenCategoryTree($categoryTree);
        } catch (\Exception $e) {
            $this->logger->error('Error loading categories: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Flatten category tree with tree structure indicators
     *
     * @param array $tree
     * @param int $level
     * @param array $prefix
     * @return array
     */
    private function flattenCategoryTree(array $tree, int $level = 0, array $prefix = []): array
    {
        $result = [];
        $count = count($tree);
        
        foreach ($tree as $index => $category) {
            $isLast = ($index === $count - 1);
            
            // Build tree prefix with visual indicators
            $treePrefix = '';
            if ($level > 0) {
                // Add parent connectors (vertical lines for non-last parents)
                foreach ($prefix as $isParentLast) {
                    $treePrefix .= $isParentLast ? '    ' : '│   ';
                }
                // Add current level connector
                $treePrefix .= $isLast ? '└── ' : '├── ';
            }
            
            // Add current category
            $result[] = [
                'value' => $category['id'],
                'label' => $treePrefix . $category['name'],
                'level' => $level
            ];
            
            // Process children recursively
            if (!empty($category['children'])) {
                // Sort children by path to maintain order
                usort($category['children'], function ($a, $b) {
                    return strcmp($a['path'], $b['path']);
                });
                
                $newPrefix = $prefix;
                $newPrefix[] = $isLast;
                $childResults = $this->flattenCategoryTree($category['children'], $level + 1, $newPrefix);
                $result = array_merge($result, $childResults);
            }
        }
        
        return $result;
    }
}


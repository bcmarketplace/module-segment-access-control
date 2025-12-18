<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Plugin\Company;

use BCMarketplace\SegmentAccessControl\Api\Data\OrganizationGroupInterface;
use BCMarketplace\SegmentAccessControl\Api\Data\OrganizationGroupInterfaceFactory;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\OrganizationGroup\CollectionFactory;
use Magento\Company\Api\Data\RoleExtensionInterfaceFactory;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Plugin for RoleRepositoryInterface to handle organization group data
 */
class RoleRepositoryPlugin
{
    /**
     * @var OrganizationGroupInterfaceFactory
     */
    private $organizationGroupFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RoleExtensionInterfaceFactory
     */
    private $roleExtensionFactory;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @param OrganizationGroupInterfaceFactory $organizationGroupFactory
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param CompanyAdminPermission $companyAdminPermission
     * @param LoggerInterface $logger
     * @param RoleExtensionInterfaceFactory $roleExtensionFactory
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        OrganizationGroupInterfaceFactory $organizationGroupFactory,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        CompanyAdminPermission $companyAdminPermission,
        LoggerInterface $logger,
        RoleExtensionInterfaceFactory $roleExtensionFactory,
        CompanyRepositoryInterface $companyRepository
    ) {
        $this->organizationGroupFactory = $organizationGroupFactory;
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
        $this->companyAdminPermission = $companyAdminPermission;
        $this->logger = $logger;
        $this->roleExtensionFactory = $roleExtensionFactory;
        $this->companyRepository = $companyRepository;
    }

    /**
     * After save - save organization group data
     *
     * @param RoleRepositoryInterface $subject
     * @param RoleInterface $result
     * @param RoleInterface $role
     * @return RoleInterface
     */
    public function afterSave(
        RoleRepositoryInterface $subject,
        RoleInterface $result,
        RoleInterface $role
    ): RoleInterface {
        try {
            $roleId = $result->getId();
            if (!$roleId) {
                return $result;
            }

            // Get post data
            $postData = $this->request->getPostValue();
            $categoryIds = $postData['category_ids'] ?? null;
            $isAdmin = isset($postData['is_admin']) ? (int)$postData['is_admin'] : 0;
            $status = isset($postData['status']) ? (int)$postData['status'] : 1;

            // Get company ID from current company admin
            $companyId = null;
            $companyAdminId = null;
            try {
                $currentCustomer = $this->companyAdminPermission->getCurrentCustomer();
                if ($currentCustomer && $currentCustomer->getId()) {
                    $companyAdminId = (int)$currentCustomer->getId();
                    $companyId = $this->getCompanyIdByAdmin($companyAdminId);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error getting company admin: ' . $e->getMessage());
            }

            // If company_id is in post data, use it
            if (isset($postData['company_id']) && $postData['company_id']) {
                $companyId = (int)$postData['company_id'];
            }

            // Convert category IDs array to comma-separated string if needed
            if (is_array($categoryIds)) {
                $categoryIds = implode(',', array_filter($categoryIds));
            }

            // Load existing organization group by role_id
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('company_role_id', $roleId);
            $organizationGroup = $collection->getFirstItem();

            // If no existing record, create new
            if (!$organizationGroup->getId()) {
                $organizationGroup = $this->organizationGroupFactory->create();
            }

            // Set data
            $organizationGroup->setCompanyRoleId($roleId);
            $organizationGroup->setCategoryIds($categoryIds);
            $organizationGroup->setIsAdmin($isAdmin);
            $organizationGroup->setStatus($status);
            if ($companyId) {
                $organizationGroup->setCompanyId($companyId);
            }
            if ($companyAdminId) {
                $organizationGroup->setCompanyAdminId($companyAdminId);
            }

            // Save
            $organizationGroup->save();
        } catch (\Exception $e) {
            $this->logger->error('Error saving organization group: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * After get - add organization group data to extension attributes
     *
     * @param RoleRepositoryInterface $subject
     * @param RoleInterface $result
     * @param int $roleId
     * @return RoleInterface
     */
    public function afterGet(
        RoleRepositoryInterface $subject,
        RoleInterface $result,
        int $roleId
    ): RoleInterface {
        try {
            $organizationGroup = $this->loadOrganizationGroupByRoleId($roleId);
            if ($organizationGroup && $organizationGroup->getId()) {
                $extensionAttributes = $result->getExtensionAttributes();
                if ($extensionAttributes === null) {
                    $extensionAttributes = $this->roleExtensionFactory->create();
                }
                $extensionAttributes->setOrganizationGroup($organizationGroup);
                $result->setExtensionAttributes($extensionAttributes);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error loading organization group: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * After getList - add organization group data to each role
     *
     * @param RoleRepositoryInterface $subject
     * @param SearchResultsInterface $result
     * @return SearchResultsInterface
     */
    public function afterGetList(
        RoleRepositoryInterface $subject,
        SearchResultsInterface $result
    ): SearchResultsInterface {
        try {
            $roles = $result->getItems();
            if (empty($roles)) {
                return $result;
            }

            $roleIds = [];
            foreach ($roles as $role) {
                $roleIds[] = $role->getId();
            }

            // Load all organization groups for these role IDs
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('company_role_id', ['in' => $roleIds]);
            $organizationGroupsByRoleId = [];
            foreach ($collection as $orgGroup) {
                $organizationGroupsByRoleId[$orgGroup->getCompanyRoleId()] = $orgGroup;
            }

            // Add to extension attributes
            foreach ($roles as $role) {
                $roleId = $role->getId();
                if (isset($organizationGroupsByRoleId[$roleId])) {
                    $extensionAttributes = $role->getExtensionAttributes();
                    if ($extensionAttributes === null) {
                        $extensionAttributes = $this->roleExtensionFactory->create();
                    }
                    $extensionAttributes->setOrganizationGroup($organizationGroupsByRoleId[$roleId]);
                    $role->setExtensionAttributes($extensionAttributes);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error loading organization groups for list: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * After delete - delete organization group records
     *
     * @param RoleRepositoryInterface $subject
     * @param bool $result
     * @param int $roleId
     * @return bool
     */
    public function afterDelete(
        RoleRepositoryInterface $subject,
        bool $result,
        int $roleId
    ): bool {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('company_role_id', $roleId);
            foreach ($collection as $organizationGroup) {
                $organizationGroup->delete();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error deleting organization group: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Load organization group by role ID
     *
     * @param int $roleId
     * @return OrganizationGroupInterface|null
     */
    private function loadOrganizationGroupByRoleId(int $roleId): ?OrganizationGroupInterface
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('company_role_id', $roleId);
            $organizationGroup = $collection->getFirstItem();
            return $organizationGroup->getId() ? $organizationGroup : null;
        } catch (\Exception $e) {
            $this->logger->error('Error loading organization group by role ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get company ID by admin customer ID
     *
     * @param int $adminId
     * @return int|null
     */
    private function getCompanyIdByAdmin(int $adminId): ?int
    {
        try {
            $connection = $this->collectionFactory->create()->getConnection();
            $select = $connection->select()
                ->from('company', ['entity_id'])
                ->where('super_user_id = ?', $adminId)
                ->limit(1);
            $companyId = $connection->fetchOne($select);
            return $companyId ? (int)$companyId : null;
        } catch (\Exception $e) {
            $this->logger->error('Error getting company ID by admin: ' . $e->getMessage());
            return null;
        }
    }
}


<?php
declare(strict_types=1);

namespace BCMarketplace\SegmentAccessControl\ViewModel;

use Amasty\MegaMenuLite\Api\Data\Menu\ItemInterface;
use Amasty\MegaMenuLite\Model\Menu\Frontend\GetItemData;
use Amasty\MegaMenuLite\Model\Menu\Frontend\ModifyNodeDataInterface;
use Amasty\MegaMenuLite\Model\Menu\TreeResolver;
use Amasty\MegaMenuLite\Model\OptionSource\Status;
use Amasty\MegaMenuLite\Model\ResourceModel\Menu\Item\Position;
use BCMarketplace\SegmentAccessControl\Helper\Data as HelperData;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Permission\Index as SegmentPermissions;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Tree\Node;
use Magento\Store\Model\StoreManager;
use Magento\CatalogPermissions\Model\PermissionFactory as CategoryPermissionsFactory;
class PluginTree extends \Amasty\MegaMenuLite\ViewModel\Tree {


    /**
     * @var ModifyNodeDataInterface[]
     */
    private $modifyDataPool;

    private $customerGroupIds = [];

    /**
     * @var SegmentPermissions
     */
    private $segmentPermissions;

    /**
     * Customer session.
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Segment helper data.
     *
     * @var HelperData
     */
    protected $helperData;

    protected $isSegmentEnabled = null;

    /**
     * @var TreeResolver
     */
    private $treeResolver;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Node|null
     */
    private $menu = null;

    /**
     * @var array
     */
    private $nodesData = [];

    /**
     * @var CategoryPermissions
     */
    private $CategoryPermissionsFactory;

    public function __construct(
        TreeResolver $treeResolver,
        StoreManager $storeManager,
        Session $customerSession,
        SegmentPermissions $segmentPermissions,
        HelperData $helperData,
        CategoryPermissionsFactory $categoryPermissions,
        array $modifyDataPool = []
    ) {
        $this->modifyDataPool = $modifyDataPool;
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
        $this->treeResolver = $treeResolver;
        $this->CategoryPermissionsFactory = $categoryPermissions;
        $this->segmentPermissions = $segmentPermissions;
        parent::__construct($treeResolver, $storeManager, $modifyDataPool);
    }

    public function getAllNodesData(): array
    {
        $elems = $this->getNodesData()['elems'] ?? [];
        foreach ($elems as $key => $elem) {
            if ($elem[ItemInterface::STATUS] == Status::MOBILE) {
                unset($elems[$key]);
            }
        }

        return $elems;
    }

    public function getNodesData(): array
    {
        if (!$this->nodesData) {
            $this->nodesData = $this->getNodeData($this->getMenuTree());
            usort(
                $this->nodesData['elems'],
                function (array $firstElement, array $secondElement) {
                    return $firstElement[Position::POSITION] - $secondElement[Position::POSITION];
                }
            );
        }

        return $this->nodesData;
    }

    private function getMenuTree(): ?Node
    {
        if ($this->menu === null) {
            $this->menu = $this->treeResolver->get(
                (int) $this->storeManager->getStore()->getId()
            );
        }

        return $this->menu;
    }

    private function getNodeData(Node $node): array
    {
        $data = [];
        if ($node->getChildren()->count()) {
            foreach ($node->getChildren() as $child) {
                if ($this->getIsSegmentEnabled()) {
                    $permission = $child->getDataByKey('permissions');
                    if (!empty($permission)) {
                        $customerGroupId = $permission[Constants::COLUMN_CUSTOMER_GROUP_ID] ?? null;
                        $groups = $this->getCustomerGroupsBySegmentIds();
                        $categoryId = (int)$child->getDataByKey('entity_id');
                        $isGranted = $this->getCategoryPermissions($categoryId, $groups);
                        if (!$isGranted) {
                            continue;
                        }
                    }
                }
                $data[] = $this->getNodeData($child);
            }
        }

        return $this->getCurrentNodeData($node, $data);
    }

    public function getHamburgerNodesData(): array
    {
        $nodes = [];
        foreach ($this->getAllNodesData() as $node) {
            if (!$node[GetItemData::IS_CATEGORY]) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    private function getCurrentNodeData(Node $node, array $elems = []): array
    {
        $data = [
            'elems' => $elems,
            '__disableTmpl' => true
        ];

        foreach ($this->modifyDataPool as $modifier) {
            $data = $modifier->execute($node, $data);
        }

        return $data;
    }


    /**
     * Check if segment access control is enabled
     *
     * @return bool
     */
    private function getIsSegmentEnabled(): bool
    {
        if ($this->isSegmentEnabled === null) {
            $this->isSegmentEnabled = $this->helperData->isEnabled();
        }
        return $this->isSegmentEnabled;
    }

    private function getCustomerGroupsBySegmentIds(){
        if($this->customerSession->isLoggedIn() && empty($this->customerGroupIds)){
            $customer = $this->customerSession->getCustomer();
            $this->customerGroupIds = $this->segmentPermissions->getCustomerGroupIds($customer->getGroupId(), $customer->getWebsiteId(), true);
        }
        return $this->customerGroupIds;
    }

    /**
     * Get all the permissions for the categories
     *
     * @param int $categoryId
     * @param array|null $groups
     * @return bool
     */
    private function getCategoryPermissions(int $categoryId, ?array $groups): bool
    {
        if (empty($groups)) {
            return false;
        }

        $collection = $this->CategoryPermissionsFactory->create()->getCollection();
        $collection->addFieldToFilter(Constants::COLUMN_CATEGORY_ID, ['eq' => $categoryId])
            ->addFieldToFilter(Constants::COLUMN_CUSTOMER_GROUP_ID, ['in' => $groups])
            ->addFieldToFilter(Constants::PERMISSION_GRANT_CATEGORY_VIEW, ['eq' => Constants::PERMISSION_USE_PARENT]);

        return $collection->getSize() > 0;
    }
}

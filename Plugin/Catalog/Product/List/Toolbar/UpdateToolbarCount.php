<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Plugin\Catalog\Product\List\Toolbar;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Update toolbar count for category list view
 * Optimized with better error handling and performance
 */
class UpdateToolbarCount
{
    /**
     * @var CategoryFactory
     */
    private CategoryFactory $categoryFactory;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    private StockConfigurationInterface $stockConfiguration;

    /**
     * @var AreProductsSalableInterface
     */
    private AreProductsSalableInterface $areProductsSalable;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LayerResolver
     */
    private LayerResolver $layerResolver;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Http
     */
    private Http $request;

    /**
     * @param CategoryFactory $categoryFactory
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StoreManagerInterface $storeManager
     * @param LayerResolver $layerResolver
     * @param LoggerInterface $logger
     * @param Http $request
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        AreProductsSalableInterface $areProductsSalable,
        StoreManagerInterface $storeManager,
        LayerResolver $layerResolver,
        LoggerInterface $logger,
        Http $request
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
        $this->layerResolver = $layerResolver;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Update toolbar count if store is in single source mode
     *
     * @param Toolbar $subject
     * @param int $result
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function afterGetTotalNum(Toolbar $subject, int $result): int
    {
        // Skip for search results (handled differently)
        if ($this->request->getFullActionName() === 'catalogsearch_result_index') {
            return $result;
        }

        // Only update if out of stock products are shown
        if (!$this->stockConfiguration->isShowOutOfStock()) {
            return $result;
        }

        try {
            $currentCategory = $this->layerResolver->get()->getCurrentCategory();
            if (!$currentCategory || !$currentCategory->getId()) {
                return $result;
            }

            $category = $this->categoryFactory->create()->load($currentCategory->getEntityId());
            if (!$category || !$category->getId()) {
                return $result;
            }

            // If filters are applied, use collection size directly
            if (count($this->request->getParams()) >= 2) {
                return $subject->getCollection()->getSize();
            }

            // Otherwise, check salable products
            $defaultScopeId = $this->storeManager->getWebsite()->getCode();
            $stockId = (int)$this->stockRegistry->getStock($defaultScopeId)->getStockId();
            
            $productCollection = $category->getProductCollection();
            if (!$productCollection || $productCollection->getSize() === 0) {
                return $result;
            }

            $skus = [];
            $items = $productCollection->getItems();
            
            foreach ($items as $item) {
                $sku = $item->getSku();
                if ($sku) {
                    $skus[] = $sku;
                }
            }

            if (empty($skus)) {
                return $result;
            }

            $salableProducts = $this->areProductsSalable->execute($skus, $stockId);
            
            if ($salableProducts) {
                return count($salableProducts);
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                'Error updating toolbar count: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        return $result;
    }
}


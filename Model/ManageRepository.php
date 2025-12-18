<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Model;

use BCMarketplace\SegmentAccessControl\Api\Data;
use BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface;
use BCMarketplace\SegmentAccessControl\Api\Data\ManageInterfaceFactory;
use BCMarketplace\SegmentAccessControl\Api\Data\ManageSearchResultsInterface;
use BCMarketplace\SegmentAccessControl\Api\ManageRepositoryInterface;
use BCMarketplace\SegmentAccessControl\Model\Api\SearchCriteria\ManageCollectionProcessor;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage as ResourceManage;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory as ManageCollectionFactory;
use BCMarketplace\SegmentAccessControl\Service\Cache\SegmentCacheService;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\App\Route\Config;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Segment manage repository
 */
class ManageRepository implements ManageRepositoryInterface
{
    /**
     * @var ResourceManage
     */
    protected $resource;

    /**
     * @var ManageFactory
     */
    protected $manageFactory;

    /**
     * @var ManageCollectionFactory
     */
    protected $manageCollectionFactory;

    /**
     * @var Data\ManageSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var ManageInterfaceFactory
     */
    protected $dataManageFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * @var Config
     */
    private $routeConfig;

    /**
     * @var SegmentCacheService
     */
    private SegmentCacheService $cacheService;

    /**
     * @param ResourceManage $resource
     * @param ManageFactory $manageFactory
     * @param ManageInterfaceFactory $dataManageFactory
     * @param ManageCollectionFactory $manageCollectionFactory
     * @param Data\ManageSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param SegmentCacheService $cacheService
     * @param CollectionProcessorInterface $collectionProcessor
     * @param HydratorInterface|null $hydrator
     * @param Config|null $routeConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceManage $resource,
        ManageFactory $manageFactory,
        ManageInterfaceFactory $dataManageFactory,
        ManageCollectionFactory $manageCollectionFactory,
        Data\ManageSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        SegmentCacheService $cacheService,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface $hydrator = null,
        ?Config $routeConfig = null
    ) {
        $this->resource = $resource;
        $this->manageFactory = $manageFactory;
        $this->manageCollectionFactory = $manageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataManageFactory = $dataManageFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->cacheService = $cacheService;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
        $this->hydrator = $hydrator ?: ObjectManager::getInstance()
            ->get(HydratorInterface::class);
        $this->routeConfig = $routeConfig ?? ObjectManager::getInstance()
                ->get(Config::class);
    }

    /**
     * Save Segment data
     *
     * @param ManageInterface|Manage $manage
     * @return Manage
     * @throws CouldNotSaveException
     */
    public function save(ManageInterface $manage)
    {
        try {
            $manageId = $manage->getId();
            if ($manageId && !($manage instanceof Manage && $manage->getOrigData())) {
                $manage = $this->hydrator->hydrate($this->getById($manageId), $this->hydrator->extract($manage));
            }
            if ($manage->getStoreId() === null) {
                $storeId = $this->storeManager->getStore()->getId();
                $manage->setStoreId($storeId);
            }
            $this->resource->save($manage);
            
            // Invalidate cache after save
            $this->cacheService->clean([
                Constants::CACHE_TAG_SEGMENTS,
                Constants::CACHE_TAG_SEGMENT_COLLECTION
            ]);
        } catch (LocalizedException $exception) {
            throw new CouldNotSaveException(
                __('Could not save the segment: %1', $exception->getMessage()),
                $exception
            );
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(
                __('Could not save the segment: %1', __('Something went wrong while saving the segment.')),
                $exception
            );
        }
        return $manage;
    }

    /**
     * Load segment data by given segment Identity
     *
     * @param string $manageId
     * @return Manage
     * @throws NoSuchEntityException
     */
    public function getById($manageId)
    {
        $manage = $this->manageFactory->create();
        $manage->load($manageId);
        if (!$manage->getId()) {
            throw new NoSuchEntityException(__('The segment manage with the "%1" ID doesn\'t exist.', $manageId));
        }

        return $manage;
    }

    /**
     * Load segment data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param SearchCriteriaInterface $criteria
     * @return ManageSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->manageCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete Segment
     *
     * @param ManageInterface $manage
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ManageInterface $manage)
    {
        try {
            $this->resource->delete($manage);
            
            // Invalidate cache after delete
            $this->cacheService->clean([
                Constants::CACHE_TAG_SEGMENTS,
                Constants::CACHE_TAG_SEGMENT_COLLECTION
            ]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the segment: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete Manage by given Manage Identity
     *
     * @param string $manageId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($manageId)
    {
        return $this->delete($this->getById($manageId));
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            // phpstan:ignore "Class BCMarketplace\SegmentAccessControl\Model\Api\SearchCriteria\ManageCollectionProcessor not found."
            $this->collectionProcessor = ObjectManager::getInstance()
                ->get(ManageCollectionProcessor::class);
        }
        return $this->collectionProcessor;
    }

    /**
     * Checks that manage identifier doesn't duplicate existed routes
     *
     * @param ManageInterface $manage
     * @return void
     * @throws CouldNotSaveException
     */
    private function validateRoutesDuplication($manage): void
    {
        if ($this->routeConfig->getRouteByFrontName($manage->getIdentifier(), 'frontend')) {
            throw new CouldNotSaveException(
                __('The value specified in the URL Key field would generate a URL that already exists.')
            );
        }
    }
}

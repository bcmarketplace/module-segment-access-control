<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/


namespace BCMarketplace\SegmentAccessControl\Model\ManageRepository;

use BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface;
use BCMarketplace\SegmentAccessControl\Api\ManageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\HydratorInterface;

/**
 * Validates and saves a segment
 */
class ValidationComposite implements ManageRepositoryInterface
{
    /**
     * @var ManageRepositoryInterface
     */
    private $repository;

    /**
     * @var array
     */
    private $validators;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * @param ManageRepositoryInterface $repository
     * @param ValidatorInterface[] $validators
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        ManageRepositoryInterface $repository,
        array $validators = [],
        ?HydratorInterface $hydrator = null
    ) {
        $this->repository = $repository;
        $this->validators = $validators;
        $this->hydrator = $hydrator ?? ObjectManager::getInstance()->get(HydratorInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function save(ManageInterface $manage)
    {
        if ($manage->getSegmentId()) {
            $manage = $this->hydrator->hydrate($this->getById($manage->getSegmentId()), $this->hydrator->extract($manage));
        }
        foreach ($this->validators as $validator) {
            $validator->validate($manage);
        }

        return $this->repository->save($manage);
    }

    /**
     * @inheritdoc
     */
    public function getById($manage)
    {
        return $this->repository->getById($manage);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->repository->getList($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function delete(ManageInterface $manage)
    {
        return $this->repository->delete($manage);
    }

    /**
     * @inheritdoc
     */
    public function deleteById($manage)
    {
        return $this->repository->deleteById($manage);
    }
}

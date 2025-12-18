<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Block\Adminhtml\Manage\Edit;

use Magento\Backend\Block\Widget\Context;
use BCMarketplace\SegmentAccessControl\Api\ManageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GenericButton
 */
class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ManageRepositoryInterface
     */
    protected $manageRepository;

    /**
     * @param Context $context
     * @param ManageRepositoryInterface $manageRepository
     */
    public function __construct(
        Context $context,
        ManageRepositoryInterface $manageRepository
    ) {
        $this->context = $context;
        $this->manageRepository = $manageRepository;
    }

    /**
     * Return Segment manage ID
     *
     * @return int|null
     */
    public function getSegmentId()
    {
        try {
            return $this->manageRepository->getById(
                $this->context->getRequest()->getParam('segment_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Controller\Adminhtml\Manage;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use BCMarketplace\SegmentAccessControl\Api\Data\ManageInterface;
use BCMarketplace\SegmentAccessControl\Api\ManageRepositoryInterface;
use BCMarketplace\SegmentAccessControl\Model\Manage;
use BCMarketplace\SegmentAccessControl\Model\ManageFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save segment manage action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'BCMarketplace_SegmentAccessControl::save';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var ManageFactory
     */
    private $manageFactory;

    /**
     * @var ManageRepositoryInterface
     */
    private $manageRepository;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param ManageFactory|null $manageFactory
     * @param ManageRepositoryInterface|null $manageRepository
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor,
        ManageFactory $manageFactory = null,
        ManageRepositoryInterface $manageRepository = null
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->manageFactory = $manageFactory ?: ObjectManager::getInstance()->get(ManageFactory::class);
        $this->manageRepository = $manageRepository ?: ObjectManager::getInstance()->get(ManageRepositoryInterface::class);
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var Manage $model */
            $model = $this->manageFactory->create();

            $id = $this->getRequest()->getParam('segment_id');
            if ($id) {
                try {
                    $model = $this->manageRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This segment no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $model->setIsActive($data['is_active']);
            $model->setName($data['name']);
            $model->setWebsiteId($data['website_id']);
            $model->setGroup($data['group']);

            try {
                $this->_eventManager->dispatch(
                    'segment_manage_prepare_save',
                    ['segment' => $model, 'request' => $this->getRequest()]
                );

                $this->manageRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the segment.'));
                return $this->processResultRedirect($model, $resultRedirect, $data);
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the segment.'));
            }

            $this->dataPersistor->set('segment_manage', $data);
            return $resultRedirect->setPath('*/*/edit', ['segment_id' => $this->getRequest()->getParam('segment_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Process result redirect
     *
     * @param SegmentInterface $model
     * @param Redirect $resultRedirect
     * @param array $data
     * @return Redirect
     * @throws LocalizedException
     */
    private function processResultRedirect($model, $resultRedirect, $data)
    {
        if ($this->getRequest()->getParam('back', false) === 'duplicate') {
            $newSegment = $this->manageFactory->create(['data' => $data]);
            $newSegment->setSegmentId(null);
            $newSegment->setIsActive(false);
            $this->manageRepository->save($newSegment);
            $this->messageManager->addSuccessMessage(__('You duplicated the segment.'));
            return $resultRedirect->setPath(
                '*/*/edit',
                [
                    'segment_id' => $newSegment->getSegmentId(),
                    '_current' => true,
                ]
            );
        }
        $this->dataPersistor->clear('segment_manage');
        if ($this->getRequest()->getParam('back')) {
            return $resultRedirect->setPath('*/*/edit', ['segment_id' => $model->getSegmentId(), '_current' => true]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}

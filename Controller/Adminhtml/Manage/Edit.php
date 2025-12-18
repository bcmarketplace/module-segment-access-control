<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Controller\Adminhtml\Manage;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use BCMarketplace\SegmentAccessControl\Model\ManageFactory;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage;

/**
 * Edit Segment manage action.
 */
class Edit  extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'BCMarketplace_SegmentAccessControl::save';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage
     */
    private $manageResource;

    /**
     * @var \BCMarketplace\SegmentAccessControl\Model\ManageFactory
     */
    private $manageFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param ManageFactory $manageFactory
     * @param Manage $manageResource
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        ManageFactory $manageFactory,
        Manage $manageResource
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->manageResource = $manageResource;
        $this->manageFactory = $manageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('segment_id');

        $model =  $this->manageFactory->create();

        // 2. Initial checking
        if ($id) {
            $this->manageResource->load($model, $id);
            if (!$model->getSegmentId()) {
                $this->messageManager->addErrorMessage(__('This segment no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Segments'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getSegmentId() ? $model->getName() : __('New Segment'));
        return $resultPage;
    }
}

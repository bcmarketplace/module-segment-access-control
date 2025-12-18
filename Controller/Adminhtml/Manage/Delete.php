<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Controller\Adminhtml\Manage;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Delete segment action.
 */
class Delete extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'BCMarketplace_SegmentAccessControl::segment_delete';

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('segment_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            $name = "";
            try {
                // init model and delete
                $model = $this->_objectManager->create(\BCMarketplace\SegmentAccessControl\Model\Manage::class);
                $model->load($id);

                $name = $model->getName();
                $model->delete();

                // display success message
                $this->messageManager->addSuccessMessage(__('The segment has been deleted.'));

                // go to grid
                $this->_eventManager->dispatch('adminhtml_segmentmanage_on_delete', [
                    'title' => $name,
                    'status' => 'success'
                ]);

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->_eventManager->dispatch(
                    'adminhtml_segmentmanage_on_delete',
                    ['name' => $name, 'status' => 'fail']
                );
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['segment_id' => $id]);
            }
        }

        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a segment to delete.'));

        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}

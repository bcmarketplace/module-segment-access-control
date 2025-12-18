<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Block\Adminhtml;

/**
 * Adminhtml segment content block
 */
class Manage extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_manage';
        $this->_blockGroup = 'BCMarketplace_SegmentAccessControl';
        $this->_headerText = __('Manage Segments');

        parent::_construct();

        if ($this->_isAllowedAction('BCMarketplace_SegmentAccessControl::save')) {
            $this->buttonList->update('add', 'label', __('Add New Segment'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}

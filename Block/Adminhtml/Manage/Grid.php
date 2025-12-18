<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/
namespace BCMarketplace\SegmentAccessControl\Block\Adminhtml\Manage;

use BCMarketplace\SegmentAccessControl\Model\Manage;
use BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory;

/**
 * Adminhtml segments grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \BCMarketplace\SegmentAccessControl\Model\Manage
     */
    protected $_segmentManage;

    /**
     * @param \BCMarketplace\SegmentAccessControl\Model\Manage $segmentManage
     * @param \BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\CollectionFactory $collectionFactory
     */
    public function __construct(
        Manage $segmentManage,
        CollectionFactory $collectionFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_segmentManage = $segmentManage;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->setId('segmentManageGrid');
        //$this->setDefaultSort('identifier');
        $this->setDefaultDir('ASC');
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        /* @var $collection \BCMarketplace\SegmentAccessControl\Model\ResourceModel\Manage\Collection */
        $collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => $this->_segmentManage->getAvailableStatuses()
            ]
        );
        $this->addColumn('name', ['header' => __('Name'), 'index' => 'name']);

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                [
                    'header' => __('Store View'),
                    'index' => 'store_id',
                    'type' => 'store',
                    'store_all' => true,
                    'store_view' => true,
                    'sortable' => false,
                    'filter_condition_callback' => [$this, '_filterStoreCondition']
                ]
            );
        }



        $this->addColumn(
            'creation_time',
            [
                'header' => __('Created'),
                'index' => 'creation_time',
                'type' => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        $this->addColumn(
            'update_time',
            [
                'header' => __('Modified'),
                'index' => 'update_time',
                'type' => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        $this->addColumn(
            'segment_actions',
            [
                'header' => __('Action'),
                'sortable' => false,
                'filter' => false,
                'renderer' => \BCMarketplace\SegmentAccessControl\Block\Adminhtml\Manage\Grid\Renderer\Action::class,
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * After load collection
     *
     * @return void
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    /**
     * Filter store condition
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @param \Magento\Framework\DataObject $column
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _filterStoreCondition($collection, \Magento\Framework\DataObject $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    /**
     * Row click url
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['segment_id' => $row->getId()]);
    }
}

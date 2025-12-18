<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Observer;

use BCMarketplace\SegmentAccessControl\Helper\Data as SegmentHelper;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;
use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\Helper\Data;
use Magento\Customer\Model\Session\Proxy as CustomerSession;

class ApplyPermissionsOnProductObserverPlugin extends \Magento\CatalogPermissions\Observer\ApplyPermissionsOnProduct
{

    private SegmentHelper $segmentHelper;

    private CustomerSession $customerSession;
    public function __construct(
        Data $catalogPermData,
        SegmentHelper $segmentHelper,
        CustomerSession $customerSession,
    ) {
        $this->segmentHelper = $segmentHelper;
        $this->customerSession = $customerSession;
        parent::__construct($catalogPermData);
    }

    /**
     * Apply category related permissions on product
     *
     * @param Product $product
     * @return $this
     */
    public function execute(Product $product): self
    {
        if (!$this->segmentHelper->isEnabled()) {
            return parent::execute($product);
        }

        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return parent::execute($product);
        }

        $segments = $customer->getData(Constants::ATTRIBUTE_SEGMENT_FILTER);
        
        if (!empty($segments)) {
            $product = $this->segmentHelper->setProductPermissionBySegment($product, $segments);
        }

        return parent::execute($product);
    }
}

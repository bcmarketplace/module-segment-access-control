<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for Segment search results.
 */
interface ManageSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Segment list.
     *
     * @return \BCMarketplace\SegmentAccessControl\Api\Data\SegmentInterface[]
     */
    public function getItems();

    /**
     * Set Segment list.
     *
     * @param \BCMarketplace\SegmentAccessControl\Api\Data\SegmentInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

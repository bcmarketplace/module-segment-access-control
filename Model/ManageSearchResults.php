<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Model;

use BCMarketplace\SegmentAccessControl\Api\Data\ManageSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Service Data Object with Segment search results.
 */
class ManageSearchResults extends SearchResults implements ManageSearchResultsInterface
{
}

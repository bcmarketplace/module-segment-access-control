<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Model\Config;

/**
 * Centralized constants for the module
 * This class eliminates hardcoded values throughout the codebase
 */
class Constants
{
    // Segment Status
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    // Customer Attribute Codes
    public const ATTRIBUTE_SEGMENT_FILTER = 'segment_filter';
    public const ATTRIBUTE_SEGMENT_APPROVAL = 'segment_approval';

    // Permission Field Names
    public const PERMISSION_GRANT_CATEGORY_VIEW = 'grant_catalog_category_view';
    public const PERMISSION_GRANT_PRODUCT_PRICE = 'grant_catalog_product_price';
    public const PERMISSION_GRANT_CHECKOUT_ITEMS = 'grant_checkout_items';

    // Database Table Names
    public const TABLE_SEGMENT_ENTITY = 'bcmarketplace_segment_entity';
    public const TABLE_COMPANY_ADVANCED_CUSTOMER = 'company_advanced_customer_entity';

    // Database Column Names
    public const COLUMN_SEGMENT_ID = 'segment_id';
    public const COLUMN_IS_ACTIVE = 'is_active';
    public const COLUMN_NAME = 'name';
    public const COLUMN_WEBSITE_ID = 'website_id';
    public const COLUMN_GROUP = 'group';
    public const COLUMN_CREATION_TIME = 'creation_time';
    public const COLUMN_UPDATE_TIME = 'update_time';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_CUSTOMER_GROUP_ID = 'customer_group_id';
    public const COLUMN_PRODUCT_ID = 'product_id';
    public const COLUMN_STORE_ID = 'store_id';

    // Configuration Paths
    public const XML_PATH_SEGMENT = 'segment/';
    public const XML_PATH_SEGMENT_GENERAL = 'segment/general/';
    public const CONFIG_ENABLED = 'enabled';
    public const CONFIG_DEFAULT_SEGMENT = 'default_segment';

    // Cache Tags
    public const CACHE_TAG_SEGMENT = 'segment_m';
    public const CACHE_TAG_SEGMENT_COLLECTION = 'segment_collection';
    public const CACHE_TAG_SEGMENTS = 'segments';

    // Cache Lifetime (in seconds)
    public const CACHE_LIFETIME = 3600; // 1 hour

    // Event Prefix
    public const EVENT_PREFIX = 'segment_manage';

    // Permission Values
    public const PERMISSION_ALLOW = 1;
    public const PERMISSION_DENY = 2;
    public const PERMISSION_USE_PARENT = -1;

    // String Delimiters
    public const DELIMITER_COMMA = ',';
    public const DELIMITER_PIPE = '|';

    // Default Values
    public const DEFAULT_IS_ACTIVE = 1;
    public const DEFAULT_POSITION = 500;

    // Attribute Configuration
    public const ATTRIBUTE_TYPE_VARCHAR = 'varchar';
    public const ATTRIBUTE_GROUP_GENERAL = 'General';
    public const ATTRIBUTE_INPUT_MULTISELECT = 'multiselect';
    public const ATTRIBUTE_FORM_ADMINHTML_CUSTOMER = 'adminhtml_customer';

    // Array Keys (for cache, collections, etc.)
    public const ARRAY_KEY_CUSTOMER_GROUP_ID = 'customer_group_id';
    public const ARRAY_KEY_PRODUCT_ID = 'product_id';
    public const ARRAY_KEY_CATEGORY_ID = 'category_id';
    public const ARRAY_KEY_SEGMENT_ID = 'segment_id';

    // Import/Export
    public const IMPORT_ENTITY_CODE = 'segmentimport';
    public const IMPORT_ERROR_NAME_REQUIRED = 'NameIsRequired';
    public const IMPORT_ERROR_SEGMENT_ID_REQUIRED = 'SegmentIdIsRequired';
    public const IMPORT_ERROR_ACTIVE_REQUIRED = 'ActiveIsRequired';
    public const IMPORT_ERROR_WEBSITE_ID_REQUIRED = 'WebsiteIdIsRequired';
    public const IMPORT_ERROR_GROUP_REQUIRED = 'GroupIsRequired';
}


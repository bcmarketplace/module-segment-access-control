# BCMarketplace Segment Access Control Module

## Overview

**BCMarketplace_SegmentAccessControl** is a Magento 2.4.7+ module that provides advanced, segment-based access control for catalog products and categories. It extends Magento's native catalog permissions system to enable fine-grained access management based on custom customer segments rather than just customer groups.

This module solves the challenge of managing complex B2B access control scenarios where customers need access to different catalog sections based on multiple organizational roles or segments, while maintaining compatibility with Magento's existing catalog permission infrastructure.

**Key Capabilities:**
- Control which categories and products customers can access based on their assigned segments
- Link organization groups directly to company roles for streamlined permission management
- Visual category tree makes it easy to see parent-child relationships when selecting categories
- Automatically filter search results based on customer segment permissions
- Support multiple segments per customer with aggregated permissions

## What Problem Does This Module Solve?

### The Challenge

In B2B e-commerce environments, organizations often need more sophisticated access control than what standard Magento customer groups provide. Common scenarios include:

1. **Multi-Segment Access**: A customer may belong to multiple segments (e.g., IT, Marketing, Operations), each with different catalog access rights
2. **Dynamic Permission Management**: Access requirements change frequently based on projects, roles, or organizational changes
3. **Granular Control**: Need to control access at a more granular level than customer groups allow
4. **Complex Approval Workflows**: Different segments may require different approval processes
5. **Search Filtering**: Search results must be filtered based on segment-based permissions

### The Solution

This module introduces a **Segment** entity that maps to customer groups but provides additional flexibility:

- **One-to-Many Relationship**: Customers can belong to multiple segments simultaneously
- **Segment-to-Customer Group Mapping**: Each segment maps to a customer group, leveraging existing catalog permission infrastructure without modifying core tables
- **Dynamic Permission Resolution**: The system dynamically resolves permissions by aggregating all customer groups associated with a customer's segments
- **OpenSearch Integration**: Search queries are automatically filtered based on segment permissions at the query level
- **Performance Optimization**: Intelligent caching layer minimizes database queries and improves response times

## Architecture

### High-Level Architecture

The module follows a layered architecture that integrates seamlessly with Magento's core catalog permissions:

```
┌─────────────────────────────────────────────────────────────┐
│                    Customer Layer                           │
│  ┌──────────────────┐  ┌──────────────────┐                 │
│  │ segment_filter   │  │ segment_approval │                 │
│  │ (multiselect)    │  │ (multiselect)    │                 │
│  │ Access Control   │  │ Approval Roles   │                 │
│  └──────────────────┘  └──────────────────┘                 │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Segment Entity Layer                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  bcmarketplace_segment_entity                        │   │
│  │  - segment_id (PK)                                   │   │
│  │  - name                                              │   │
│  │  - group (customer_group_id) ← Maps to CG            │   │
│  │  - website_id                                        │   │
│  │  - is_active                                         │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Permission Resolution Layer                    │
│  ┌──────────────────┐  ┌──────────────────┐                 │
│  │ Helper/Data      │  │ Permission/Index │                 │
│  │ - Segment lookup │  │ - Group mapping  │                 │
│  │ - Caching        │  │ - Permission     │                 │
│  │ - Batch ops      │  │   aggregation    │                 │
│  └──────────────────┘  └──────────────────┘                 │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Integration Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ Observers    │  │ Plugins      │  │ OpenSearch   │       │
│  │ - Category   │  │ - Customer   │  │ - Query      │       │
│  │ - Product    │  │ - Collection │  │   filtering  │       │
│  │ - Collection │  │ - Navigation │  │ - Results    │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Magento Core Catalog Permissions               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  catalogpermissions_category                         │   │
│  │  catalogpermissions_product                          │   │
│  │  (No modifications to core tables)                   │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Key Features

### 1. Segment Management
- **Admin Interface**: Full CRUD operations via admin panel (Customers → Segment Access Control)
- **Import/Export**: CSV import/export functionality for bulk segment management
- **Multi-Website Support**: Segments can be scoped to specific websites/stores
- **Active/Inactive Status**: Enable or disable segments without deletion
- **Customer Group Mapping**: Each segment maps to one customer group for permission inheritance

### 2. Multi-Segment Support
- **Multiple Assignments**: Customers can belong to multiple segments simultaneously
- **Permission Aggregation**: Permissions from all segments are aggregated (union operation)
- **Flexible Access**: Customer sees union of all accessible categories/products from all segments
- **Dynamic Resolution**: Permissions resolved dynamically on each request

### 3. Performance Optimization
- **Intelligent Caching**: 
  - Segment-to-group mappings cached
  - Category permission lookups cached
  - Cache keys include segment IDs for proper invalidation
- **Batch Operations**: 
  - Multiple segments loaded in single database query
  - Permission lookups batched for efficiency
- **Early Returns**: 
  - Skip processing when module disabled
  - Skip when customer has no segments assigned
  - Skip when customer not logged in
- **Efficient Queries**: 
  - Minimize database round trips
  - Use IN clauses for batch lookups
  - Proper indexing on segment_id and group columns

### 4. OpenSearch Integration
- **Automatic Filtering**: Search queries automatically filtered by segment permissions
- **Query-Level Control**: Filtering happens at OpenSearch query level, not post-processing
- **Category-Based**: Access control at category level for search results
- **Seamless Integration**: Works with Magento's OpenSearch adapter without modifications

### 5. Catalog Permission Extension
- **Non-Invasive**: Extends Magento's native catalog permissions without modifying core tables
- **Backward Compatible**: Works alongside standard customer group permissions
- **Infrastructure Reuse**: Leverages existing `catalogpermissions_category` and `catalogpermissions_product` tables
- **Graceful Degradation**: Falls back to standard permissions when module disabled

### 6. Organization Group Management
- **Company Role Integration**: Link organization groups directly to company roles for streamlined access control
- **Category Tree Selection**: Visual category tree shows parent-child relationships, making it easy to understand the catalog structure
- **Role Supervisor Toggle**: Designate roles as admin roles with a simple toggle switch
- **Status Control**: Enable or disable organization groups without deleting them
- **Automatic Data Management**: Organization group data is automatically saved when company roles are saved or deleted

## Installation

### Prerequisites

- **Magento Version**: 2.4.7 or higher
- **PHP Version**: 8.1, 8.2, or 8.3
- **OpenSearch**: Configured and running (Magento 2.4.7+ default search engine)
- **Required Modules**:
  - `Magento_Company` (for company customer attributes storage)
  - `Magento_CatalogPermissions` (core catalog permissions)
  - `Magento_OpenSearch` (search engine integration)
  - `Magento_OpenSearchCatalogPermissions` (OpenSearch permissions)

### Installation Steps

1. **Copy Module to Codebase**
   ```bash
   cp -r BCMarketplace/SegmentAccessControl app/code/BCMarketplace/SegmentAccessControl
   ```

2. **Enable Module**
   ```bash
   bin/magento module:enable BCMarketplace_SegmentAccessControl
   ```

3. **Run Setup and Compilation**
   ```bash
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy -f
   ```

4. **Clear Cache**
   ```bash
   bin/magento cache:flush
   ```

5. **Reindex Search**
   ```bash
   bin/magento indexer:reindex catalogsearch_fulltext
   ```

6. **Verify Installation**
   - Check admin menu: Customers → Segment Access Control should appear
   - Verify configuration: Stores → Configuration → Customers → Segment Management

## Configuration

Navigate to: **Stores → Configuration → Customers → Segment Management**

### Configuration Options

- **Enable Segment Access Control**: 
  - Enable/disable the module globally
  - When disabled, module functionality is bypassed and standard permissions apply
  - Default: Disabled

- **Default Segment**: 
  - Set default segment ID for new customers
  - Applied automatically when customer is created without explicit segment assignment
  - Can be left empty if no default is desired

## Usage Examples

### Creating a Segment

1. Navigate to **Customers → Segment Access Control** in admin
2. Click **Add New Segment** button
3. Fill in the form:
   - **Name**: "IT Segment" (or any descriptive name)
   - **Customer Group**: Select the customer group this segment maps to
   - **Website**: Select the website/store scope
   - **Active**: Set to "Yes" to enable the segment
4. Click **Save**
5. The segment is now available for assignment to customers

### Assigning Segments to Customer

1. Navigate to **Customers → All Customers** in admin
2. Edit the desired customer
3. Scroll to find the **Segment Filter** field (multiselect dropdown)
4. Select one or more segments from the list
5. Optionally, set **Segment Approval** if customer has approval roles
6. Click **Save Customer**
7. Customer now has access based on aggregated permissions from all assigned segments

### How Permissions Work - Step by Step

**Example Scenario**: Customer "John Doe" is assigned segments [1, 2, 3]

1. **Customer Login**: John logs into the storefront
2. **Segment Retrieval**: System retrieves John's `segment_filter` attribute: "1,2,3"
3. **Segment Loading**: System loads segments 1, 2, 3 from `bcmarketplace_segment_entity` table
   - Segment 1 → maps to Customer Group 5
   - Segment 2 → maps to Customer Group 6
   - Segment 3 → maps to Customer Group 7
4. **Group Aggregation**: System aggregates customer groups: [5, 6, 7] + John's current group
5. **Permission Query**: System queries catalog permissions for groups [5, 6, 7, current_group]
   - Finds categories accessible by these groups
   - Finds products accessible by these groups
6. **Permission Application**: System applies permissions:
   - Categories: Union of all accessible categories
   - Products: Union of all accessible products
   - Search: Filtered to only show accessible categories
7. **Display**: John sees only products and categories he has access to based on all his segments

### Setting Up Organization Groups for Company Roles

Organization groups allow you to control which categories company roles can access. This is perfect for B2B scenarios where different roles within a company need access to different product categories.

1. **Navigate to Company Role Management**
   - Go to your company's role management page (typically found in the company account section)
   - Click **Edit** on an existing role or create a new role

2. **Configure Organization Group Settings**
   - Scroll to the **Organization Group Settings** section
   - **Select Categories**: Choose which categories this role can access
     - The category list displays in a tree format showing parent-child relationships
     - Use Ctrl (Windows) or Cmd (Mac) to select multiple categories
     - The tree structure makes it easy to see which categories belong to which parent category
   - **Set Status**: Choose whether this organization group is Enabled or Disabled
   - **Role Supervisor**: Toggle this switch to mark the role as an admin role (Role Supervisor)

3. **Save the Role**
   - Click **Save** to store your organization group settings
   - The system automatically links the organization group data to the company role

4. **What Happens Next**
   - When users with this role log in, they'll only see products from the selected categories
   - If a role is marked as a Role Supervisor, it receives admin-level permissions
   - Disabled organization groups prevent access even if categories are selected

**Example**: If you create a "Purchasing Manager" role and select "Electronics > Computers" and "Electronics > Phones" categories, users with this role will only see products in those specific categories when browsing the store.

### Import/Export Segments

The module supports CSV import/export for bulk segment management:

1. **Export**: 
   - Navigate to **System → Data Transfer → Export**
   - Select "Segment Import" as entity type
   - Download CSV file

2. **Import**:
   - Navigate to **System → Data Transfer → Import**
   - Select "Segment Import" as entity type
   - Upload CSV file with segment data
   - Run import

**CSV Format**:
```csv
segment_id,name,is_active,website_id,group
1,IT Segment,1,1,5
2,Marketing Segment,1,1,6
3,Operations Segment,1,1,7
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Segments Not Appearing in Customer Edit Form

**Symptoms**: Segment Filter and Segment Approval fields not visible when editing customer

**Solutions**:
- Run `bin/magento setup:upgrade` to ensure attributes are created
- Clear cache: `bin/magento cache:flush`
- Verify module is enabled: `bin/magento module:status BCMarketplace_SegmentAccessControl`
- Check attribute exists: `SELECT * FROM eav_attribute WHERE attribute_code IN ('segment_filter', 'segment_approval')`

#### 2. Search Not Filtering Correctly

**Symptoms**: Search results show products customer shouldn't have access to

**Solutions**:
- Reindex search: `bin/magento indexer:reindex catalogsearch_fulltext`
- Verify OpenSearch is configured correctly
- Check module is enabled in configuration
- Verify customer has segments assigned
- Clear cache: `bin/magento cache:flush`
- Check OpenSearch logs for query structure

#### 3. Permissions Not Applying

**Symptoms**: Customer can see categories/products they shouldn't have access to

**Solutions**:
- Verify module is enabled in Stores → Configuration → Customers → Segment Management
- Check customer has segments assigned in customer edit form
- Verify segments are active (is_active = 1)
- Verify catalog permissions are configured for the customer groups mapped to segments
- Clear cache: `bin/magento cache:flush`
- Check permission index: `SELECT * FROM catalogpermissions_category WHERE customer_group_id IN (...)`

#### 4. Performance Issues

**Symptoms**: Slow page loads, high database query count

**Solutions**:
- Verify caching is enabled: Check cache configuration
- Clear cache and let it rebuild: `bin/magento cache:flush`
- Check database indexes exist on `bcmarketplace_segment_entity.group` and `bcmarketplace_segment_entity.website_id`
- Review slow query log for optimization opportunities
- Consider increasing cache lifetime if appropriate

#### 5. Import/Export Not Working

**Symptoms**: CSV import fails or export doesn't generate file

**Solutions**:
- Verify import entity is registered in `etc/import.xml`
- Check CSV format matches expected structure
- Verify file permissions for import directory
- Check Magento logs: `var/log/system.log` and `var/log/exception.log`
- Ensure required columns are present in CSV

#### 6. Organization Group Settings Not Appearing

**Symptoms**: Organization Group Settings section not visible when editing company roles

**Solutions**:
- Run `bin/magento setup:upgrade` to apply database schema changes
- Clear cache: `bin/magento cache:flush`
- Verify you're logged in as a company administrator
- Check that the Magento Company module is enabled
- Ensure you're on the correct company role edit page

#### 7. Categories Not Displaying in Tree Format

**Symptoms**: Categories appear as a flat list instead of a tree structure

**Solutions**:
- Clear browser cache and refresh the page
- Verify static content is deployed: `bin/magento setup:static-content:deploy -f`
- Check that categories have proper parent-child relationships in the catalog
- Clear Magento cache: `bin/magento cache:flush`

## Changelog

### Version 2.1.0 (Current)

#### New Features

- **Organization Group Management**: Link organization groups directly to company roles for better access control
- **Category Tree Display**: Visual tree structure shows parent-child category relationships, making it easier to understand the catalog hierarchy
- **Role Supervisor Feature**: Toggle to designate company roles as admin roles with enhanced permissions
- **Company Role Integration**: Seamless integration with Magento Company module - organization group settings appear directly in the company role edit form
- **Automatic Data Management**: Organization group data automatically saves when company roles are saved, and automatically deletes when roles are removed

#### Enhanced Features

- **Improved Category Selection**: Category dropdown now displays in a tree format with visual indicators (├──, └──) showing parent-child relationships
- **Better User Experience**: Clear visual hierarchy helps administrators understand which categories belong to which parent categories

### Version 2.0.0

#### Added Features

- **Segment Management**: Admin interface with CRUD operations, mass enable/disable, CSV import/export, multi-website support
- **Customer Attributes**: `segment_filter` and `segment_approval` multiselect attributes for segment assignment
- **Multi-Segment Support**: Customers can belong to multiple segments with aggregated permissions
- **Permission System**: Dynamic permission resolution with category and product-level access control
- **OpenSearch Integration**: Automatic search query filtering by segment-accessible categories
- **Performance Optimizations**: Intelligent caching layer, batch operations, efficient database queries
- **Observer & Plugin Architecture**: Category/product permission observers, OpenSearch plugins, navigation filtering
- **System Configuration**: Enable/disable toggle and default segment configuration

#### Technical Improvements

- Strict type declarations throughout
- Centralized constants management
- Comprehensive error handling
- Optimized database queries with batch operations
- Intelligent caching strategy with proper invalidation
- Magento 2.4.7+ and OpenSearch compatibility
- Modern PHP 8.1+ features and best practices
- Repository pattern and service layer architecture

## Author

**Raphael Baako**  
Senior Architect  
BCMarketplace  
Email: rbaako@baakoconsultingllc.com  
Website: https://baakoconsultingllc.com

## License

This project is open source and available under the [MIT License](LICENSE).

Copyright © 2024 BCMarketplace. All rights reserved.

## Support

For issues, questions, or contributions:

- **Documentation**: Review this README and inline code documentation
- **Magento Resources**: Check Magento OpenSearch and Catalog Permissions documentation
- **Contact**: Reach out to the module author via email
- **Code Review**: All code follows Magento 2.4.7+ coding standards

---

**Note**: This module is designed for Magento 2.4.7+ and requires OpenSearch as the search engine. For earlier Magento versions or Elasticsearch installations, modifications may be required.

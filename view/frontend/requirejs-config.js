var config =
    {
        map:
            {
                '*':
                    {
                        'Magento_Company/js/user-edit':'BCMarketplace_SegmentAccessControl/js/user-edit',
                        'segmentUsers': 'BCMarketplace_SegmentAccessControl/js/segment'
                    },
            },
        config: {
            mixins: {
                'Magento_Company/js/hierarchy-tree': {
                    'BCMarketplace_SegmentAccessControl/js/mixin-hierarchy-tree': true
                }
            }
        }
    };

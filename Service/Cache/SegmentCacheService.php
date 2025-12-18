<?php
declare(strict_types=1);

/**
 * Copyright © 2024 BCMarketplace. All rights reserved.
 * See COPYING.txt for license details.
 **/

namespace BCMarketplace\SegmentAccessControl\Service\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\StateInterface;
use BCMarketplace\SegmentAccessControl\Model\Config\Constants;

/**
 * Cache service for segment-related data
 * Improves performance by caching frequently accessed segment collections
 */
class SegmentCacheService
{
    /**
     * Cache type code unique among all cache types
     */
    public const TYPE_IDENTIFIER = 'segment_access_control';

    /**
     * Cache tag
     */
    public const CACHE_TAG = 'SEGMENT_ACCESS_CONTROL';

    /**
     * @var TagScope
     */
    private $cache;

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        $this->cache = new TagScope(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }

    /**
     * Load data from cache
     *
     * @param string $identifier
     * @return mixed|false
     */
    public function load(string $identifier)
    {
        return $this->cache->load($identifier);
    }

    /**
     * Save data to cache
     *
     * @param mixed $data
     * @param string $identifier
     * @param array $tags
     * @param int|null $lifeTime
     * @return bool
     */
    public function save($data, string $identifier, array $tags = [], ?int $lifeTime = null): bool
    {
        $tags = array_merge([self::CACHE_TAG, Constants::CACHE_TAG_SEGMENT], $tags);
        $lifeTime = $lifeTime ?? Constants::CACHE_LIFETIME;
        
        return $this->cache->save(
            serialize($data),
            $identifier,
            $tags,
            $lifeTime
        );
    }

    /**
     * Remove data from cache
     *
     * @param string $identifier
     * @return bool
     */
    public function remove(string $identifier): bool
    {
        return $this->cache->remove($identifier);
    }

    /**
     * Clean cache by tags
     *
     * @param array $tags
     * @return bool
     */
    public function clean(array $tags = []): bool
    {
        $tags = array_merge([self::CACHE_TAG, Constants::CACHE_TAG_SEGMENT], $tags);
        return $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
    }

    /**
     * Clean all segment-related cache
     *
     * @return bool
     */
    public function cleanAll(): bool
    {
        return $this->cache->clean(\Zend_Cache::CLEANING_MODE_ALL);
    }

    /**
     * Generate cache identifier
     *
     * @param string $prefix
     * @param array $params
     * @return string
     */
    public function generateIdentifier(string $prefix, array $params = []): string
    {
        $key = $prefix;
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        return $key;
    }
}


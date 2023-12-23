<?php

/*
 * This file is part of the twelvepics-com/php-calendar-builder project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Cache;

use LogicException;
use Psr\Cache\InvalidArgumentException;
use Redis;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class RedisCache
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-14)
 * @since 0.1.0 (2023-12-14) First version.
 */
class RedisCache
{
    private const PARAMETER_REDIS_DNS = 'redis.dns';

    private const OPTION_REDIS_CLASS = Redis::class;

    private const OPTION_TIMEOUT_SECONDS = 10;

    private const REDIS_NAMESPACE = '';

    private const REDIS_DEFAULT_LIFETIME = 0;

    final public const REDIS_ITEM_DEFAULT_LIFETIME = 30 * 86400;

    private RedisAdapter $redisAdapter;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    )
    {
        $this->setRedisAdapter();
    }

    /**
     * Sets the redis adapter.
     *
     * @return void
     */
    private function setRedisAdapter(): void
    {
        $redisDns = $this->parameterBag->get(self::PARAMETER_REDIS_DNS);

        if (!is_string($redisDns)) {
            throw new LogicException('Unexpected redis format retrieved.');
        }

        /* Options see: https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#available-options */
        $redisClient = RedisAdapter::createConnection($redisDns, [
            'class' => self::OPTION_REDIS_CLASS,
            'persistent' => 0,
            'persistent_id' => null,
            'timeout' => self::OPTION_TIMEOUT_SECONDS,
            'read_timeout' => 0,
            'retry_interval' => 0,
            'tcp_keepalive' => 0,
            'lazy' => null,
            'redis_cluster' => false,
            'redis_sentinel' => null,
            'dbindex' => 0,
            'failover' => 'none',
            'ssl' => null,
        ]);

        $this->redisAdapter = new RedisAdapter(
            $redisClient,
            self::REDIS_NAMESPACE,
            self::REDIS_DEFAULT_LIFETIME
        );
    }

    /**
     * Returns the Redis adapter.
     *
     * @return RedisAdapter
     */
    public function getRedisAdapter(): RedisAdapter
    {
        return $this->redisAdapter;
    }

    /**
     * Returns a cache key from given arguments.
     *
     * @return string
     */
    public function getCacheKey(): string
    {
        return sha1(implode('-', func_get_args()));
    }

    /**
     * Returns a mixed value from cache or call the given callable.
     *
     * @param string $key
     * @param callable $callable
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $key, callable $callable): mixed
    {
        return $this->getRedisAdapter()->get($key, $callable);
    }

    /**
     * Returns a string or null from cache or call the given callable.
     *
     * @param string $key
     * @param callable $callable
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function getStringOrNull(string $key, callable $callable): string|null
    {
        $value = $this->getRedisAdapter()->get($key, $callable);

        return match (true) {
            is_null($value), is_string($value) => $value,
            default => throw new LogicException(sprintf('Unexpected format of value "%s".', gettype($value))),
        };
    }
}

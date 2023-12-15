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

        $redisClient = RedisAdapter::createConnection($redisDns);

        $this->redisAdapter = new RedisAdapter($redisClient, '', 0);
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

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

namespace App\Calendar\Design\Base;

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract class DesignHelperBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-14)
 * @since 0.1.0 (2023-11-14) First version.
 */
abstract class DesignBase
{
    protected BaseImageBuilder $imageBuilder;

    protected KernelInterface $appKernel;

    protected Json $config;

    protected Json $configDefault;

    /**
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function __construct()
    {
        $this->config = new Json([]);
        $this->configDefault = new Json([]);
        $this->configureDefaultConfiguration();
    }

    /**
     * @return Json
     */
    public function getConfig(): Json
    {
        return $this->config;
    }

    /**
     * Do the main init for XXXDefault.php
     */
    abstract public function doInit(): void;

    /**
     * Do the main build for XXXDefault.php
     */
    abstract public function doBuild(): void;

    /**
     * @param BaseImageBuilder $imageBuilder
     * @return self
     */
    public function setImageBuilder(BaseImageBuilder $imageBuilder): self
    {
        $this->imageBuilder = $imageBuilder;

        return $this;
    }

    /**
     * @param KernelInterface $appKernel
     * @return self
     */
    public function setAppKernel(KernelInterface $appKernel): self
    {
        $this->appKernel = $appKernel;

        return $this;
    }

    /**
     * Sets the existing configuration.
     *
     * @param Json $config
     * @return self
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function setConfig(Json $config): self
    {
        $this->config = clone $this->configDefault;
        $this->config->addJson($config->getArray());

        return $this;
    }

    /**
     * Configures the default configuration for the current design.
     *
     * @return void
     * @throws TypeInvalidException
     */
    abstract protected function configureDefaultConfiguration(): void;

    /**
     * Adds a default to configuration.
     *
     * @param string|array<int, string> $path
     * @param int|string|array<int|string, mixed>|null $default
     * @return void
     * @throws TypeInvalidException
     */
    protected function addDefaultConfiguration(string|array $path, int|string|float|array|null $default): void
    {
        $this->configDefault->addValue($path, $default);
    }

    /**
     * Returns the configuration value from existing config or from default configuration.
     *
     * @param string|array<int, string> $keys
     * @return mixed
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getConfigurationValue(string|array $keys): mixed
    {
        if (!$this->config->hasKey($keys)) {
            if (is_string($keys)) {
                $keys = [$keys];
            }

            throw new LogicException(sprintf('Given key path "%s" not found in configuration', implode('.', $keys)));
        }

        return $this->config->getKey($keys);
    }

    /**
     * Returns the configuration value from existing config or from default configuration (as string).
     *
     * @param string|array<int, string> $keys
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getConfigurationValueString(string|array $keys): string
    {
        $value = $this->getConfigurationValue($keys);

        if (!is_int($value) && !is_string($value)) {
            if (is_string($keys)) {
                $keys = [$keys];
            }

            throw new LogicException(sprintf('Invalid value type "%s" for key path "%s" in configuration. "int" expected.', gettype($value), implode('.', $keys)));
        }

        return (string) $value;
    }

    /**
     * Returns the configuration value from existing config or from default configuration (as integer).
     *
     * @param string|array<int, string> $keys
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getConfigurationValueInteger(string|array $keys): int
    {
        $value = $this->getConfigurationValue($keys);

        if (!is_int($value)) {
            if (is_string($keys)) {
                $keys = [$keys];
            }

            throw new LogicException(sprintf('Invalid value type "%s" for key path "%s" in configuration. "int" expected.', gettype($value), implode('.', $keys)));
        }

        return $value;
    }

    /**
     * Returns the configuration value from existing config or from default configuration (as float).
     *
     * @param string|array<int, string> $keys
     * @return float
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getConfigurationValueFloat(string|array $keys): float
    {
        $value = $this->getConfigurationValue($keys);

        if (!is_float($value)) {
            if (is_string($keys)) {
                $keys = [$keys];
            }

            throw new LogicException(sprintf('Invalid value type "%s" for key path "%s" in configuration. "int" expected.', gettype($value), implode('.', $keys)));
        }

        return $value;
    }

    /**
     * Returns the configuration value from existing config or from default configuration (as array).
     *
     * @param string|array<int, string> $keys
     * @return array<int|string, mixed>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getConfigurationValueArray(string|array $keys): array
    {
        $value = $this->getConfigurationValue($keys);

        if (!is_array($value)) {
            if (is_string($keys)) {
                $keys = [$keys];
            }

            throw new LogicException(sprintf('Invalid value type "%s" for key path "%s" in configuration. "int" expected.', gettype($value), implode('.', $keys)));
        }

        return $value;
    }
}

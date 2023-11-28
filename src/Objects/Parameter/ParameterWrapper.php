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

namespace App\Objects\Parameter;

use App\Constants\Parameter\Argument;
use App\Constants\Parameter\Option;
use App\Objects\Input\Input;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ParameterWrapper
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-27)
 * @since 0.1.0 (2023-11-27) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ParameterWrapper
{
    private Json $config;

    private Input $input;

    /* Year of the page. */
    final public const DEFAULT_YEAR = 2024;
    private int $year = self::DEFAULT_YEAR;

    /* Month of the page. */
    final public const DEFAULT_MONTH = 1;
    private int $month = self::DEFAULT_MONTH;

    /**
     * @param Source $source
     * @param Target $target
     * @param KernelInterface $appKernel
     */
    public function __construct(
        private readonly Source $source,
        private readonly Target $target,
        protected readonly KernelInterface $appKernel
    )
    {
    }

    /**
     * Initializes the parameter wrapper.
     *
     * @param InputInterface $input
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function init(InputInterface $input): void
    {
        $this->unsetAll();

        $pathConfig = $input->getArgument(Argument::SOURCE);

        if (!is_string($pathConfig)) {
            throw new LogicException('Unable to get the path to the configuration file.');
        }

        /* Add the configuration file. */
        $this->addConfig(new File($pathConfig, $this->appKernel->getProjectDir()));

        $this->input = new Input($input, $this->getConfig());

        /* Set the year and month. */
        $this->setOptionFromParameter(Option::YEAR);
        $this->setOptionFromParameter(Option::MONTH);

        $this->source->setParameterWrapper($this);
        $this->target->setParameterWrapper($this);
    }

    /**
     * Returns the page number according to the given year and month.
     *
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPageNumber(): int
    {
        return $this->input->getPageNumber($this->year, $this->month);
    }

    /**
     * Returns if option exists within the parameter.
     *
     * @param string $name
     * @return bool
     */
    public function hasOptionFromParameter(string $name): bool
    {
        return $this->input->hasOptionFromParameter($name);
    }

    /**
     * Returns the option from the parameter.
     *
     * @param string $name
     * @return int|string|null
     */
    public function getOptionFromParameter(string $name): int|string|null
    {
        return $this->input->getOptionFromParameter($name);
    }

    /**
     * Returns if option exists within the configuration.
     *
     * @param string $name
     * @param int|null $year
     * @param int|null $month
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function hasOptionFromConfig(string $name, int $year = null, int $month = null): bool
    {
        return $this->input->hasOptionFromConfig($name, $year, $month);
    }

    /**
     * Returns the option from the configuration.
     *
     * @param string $name
     * @param int|null $year
     * @param int|null $month
     * @return int|string|array<int|string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromConfig(string $name, int $year = null, int $month = null): array|int|string|null
    {
        return $this->input->getOptionFromConfig($name, $year, $month);
    }

    /**
     * Returns the option from the configuration as array.
     *
     * @param string $name
     * @param int|null $year
     * @param int|null $month
     * @return array<int|string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromConfigAsArray(string $name, int $year = null, int $month = null): array|null
    {
        return $this->input->getOptionFromConfigAsArray($name, $year, $month);
    }

    /**
     * Unsets all.
     *
     * @return void
     */
    public function unsetAll(): void
    {
        $this->unsetConfig();
        $this->unsetYear();
        $this->unsetMonth();
    }

    /**
     * Unsets the config.
     *
     * @return void
     */
    public function unsetConfig(): void
    {
        unset($this->config);
    }

    /**
     * Unsets the year.
     *
     * @return void
     */
    public function unsetYear(): void
    {
        unset($this->year);
    }

    /**
     * Unsets the month.
     *
     * @return void
     */
    public function unsetMonth(): void
    {
        unset($this->month);
    }

    /**
     * Returns the source parameter.
     *
     * @return Source
     */
    public function getSource(): Source
    {
        return $this->source;
    }

    /**
     * Returns the target parameter.
     *
     * @return Target
     */
    public function getTarget(): Target
    {
        return $this->target;
    }

    /**
     * @return Json
     */
    public function getConfig(): Json
    {
        return $this->config;
    }

    /**
     * @param Json $config
     * @return self
     */
    public function setConfig(Json $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     * @return self
     */
    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @param int $month
     * @return self
     */
    public function setMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Sets the option to this class.
     *
     * @param string $name
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function setOptionFromParameter(string $name): void
    {
        $value = match (true) {
            $this->input->hasOptionFromParameter($name) => $this->input->getOptionFromParameter($name),
            $this->input->hasOptionFromConfig($name) => $this->input->getOptionFromConfig($name),
            default => $this->input->getOptionFromParameter($name),
        };

        if (is_null($value)) {
            return;
        }

        match ($name) {
            Option::YEAR => $this->setYear((int) $value),
            Option::MONTH => $this->setMonth((int) $value),

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Returns the identification folder.
     *
     * @return string
     */
    public function getIdentification(): string
    {
        return $this->getSource()->getIdentification();
    }

    /**
     * Returns the coordinate string.
     *
     * @return string
     * @throws CaseUnsupportedException
     */
    public function getCoordinateString(): string
    {
        $coordinateString = $this->getTarget()->getCoordinate();

        if ($coordinateString !== 'auto') {
            return $coordinateString;
        }

        $coordinate = $this->getSource()->getImageHolder()->getCoordinate();

        if (is_null($coordinate)) {
            throw new LogicException('Unable to get the coordinate of the image holder.');
        }

        return $coordinate->getStringDMS();
    }

    /**
     * Adds given config file to config.
     *
     * @param File $config
     * @return void
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function addConfig(File $config): void
    {
        /* Check if the config file exists. */
        try {
            $config->getPathReal();

            $parsedConfig = Yaml::parse($config->getContentAsText());

            if (!is_array($parsedConfig)) {
                throw new LogicException('Invalid configuration');
            }

            $this->setConfig(new Json($parsedConfig));
        } catch (FileNotFoundException) {
            throw new LogicException('Configuration file not found.');
        }
    }
}

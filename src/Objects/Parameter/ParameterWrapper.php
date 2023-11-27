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

    /* Year of the page. */
    final public const DEFAULT_YEAR = 2024;
    private int $year = self::DEFAULT_YEAR;

    /* Month of the page. */
    final public const DEFAULT_MONTH = 1;
    private int $month = self::DEFAULT_MONTH;

    /* Page number. */
    private int $pageNumber;

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

        /* Set the year and month. */
        $this->setOptionFromParameter($input, Option::YEAR);
        $this->setOptionFromParameter($input, Option::MONTH);

        $this->source->setParameterWrapper($this);
        $this->target->setParameterWrapper($this);
    }

    /**
     * Unsets all.
     *
     * @return void
     */
    public function unsetAll(): void
    {
        $this->unsetPageNumber();
        $this->unsetConfig();
        $this->unsetYear();
        $this->unsetMonth();
    }

    /**
     * Unsets the page number.
     *
     * @return void
     */
    public function unsetPageNumber(): void
    {
        unset($this->pageNumber);
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
     * Returns the config path.
     *
     * @param string $name
     * @return array<int, string>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getConfigPath(string $name): array
    {
        return match ($name) {

            /* Source options */
            Option::SOURCE => ['pages', (string) $this->getPageNumber(), 'source'],

            /* Target options */
            Option::YEAR => ['settings', 'defaults', 'year'],
            Option::MONTH => ['settings', 'defaults', 'month'],
            Option::TARGET => ['pages', (string) $this->getPageNumber(), 'target'],
            Option::PAGE_TITLE => ['pages', (string) $this->getPageNumber(), 'page-title'],
            Option::TITLE => ['pages', (string) $this->getPageNumber(), 'title'],
            Option::SUBTITLE => ['pages', (string) $this->getPageNumber(), 'subtitle'],
            Option::URL => ['pages', (string) $this->getPageNumber(), 'url'],
            Option::COORDINATE => ['pages', (string) $this->getPageNumber(), 'coordinate'],

            /* Output options */
            Option::OUTPUT_QUALITY => ['settings', 'output', 'quality'],
            Option::OUTPUT_FORMAT => ['settings', 'output', 'transparency'],

            /* Design options (default) */
            Option::DESIGN_ENGINE_DEFAULT => ['settings', 'defaults', 'design', 'engine'],
            Option::DESIGN_TYPE_DEFAULT => ['settings', 'defaults', 'design', 'type'],
            Option::DESIGN_CONFIG_DEFAULT => ['settings', 'defaults', 'design', 'config'],

            /* Design options (via page) */
            Option::DESIGN_ENGINE => ['pages', (string) $this->getPageNumber(), 'design', 'engine'],
            Option::DESIGN_TYPE => ['pages', (string) $this->getPageNumber(), 'design', 'type'],
            Option::DESIGN_CONFIG => ['pages', (string) $this->getPageNumber(), 'design', 'config'],

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Sets the option to this class.
     *
     * @param InputInterface $input
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
    private function setOptionFromParameter(InputInterface $input, string $name): void
    {
        $value = match (true) {
            $this->hasOptionFromParameter($input, $name) => $this->getOptionFromParameter($input, $name),
            $this->hasOptionFromConfig($name) => $this->getOptionFromConfig($name),
            default => $this->getOptionFromParameter($input, $name),
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
     * Returns if the option was given via parameter.
     *
     * @param InputInterface $input
     * @param string $name
     * @return bool
     */
    public function hasOptionFromParameter(InputInterface $input, string $name): bool
    {
        return $input->hasParameterOption(sprintf('--%s', $name));
    }

    /**
     * Tries to get option from parameter.
     *
     * @param InputInterface $input
     * @param string $name
     * @return int|string|null
     */
    public function getOptionFromParameter(InputInterface $input, string $name): int|string|null
    {
        $value = $input->getOption($name);

        if (is_null($value)) {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
    }

    /**
     * Tries to get Option from parameter as an array.
     *
     * @param string $name
     * @return array<int|string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromConfigAsArray(string $name): array|null
    {
        if (!$this->hasOptionFromConfig($name)) {
            return null;
        }

        $configPath = $this->getConfigPath($name);

        $value = $this->getConfig()->getKey($configPath);

        if (is_int($value) || is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
    }

    /**
     * Returns if config exists within the config.yml file.
     *
     * @param string $name
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function hasOptionFromConfig(string $name): bool
    {
        $configPath = $this->getConfigPath($name);

        return $this->getConfig()->hasKey($configPath);
    }

    /**
     * Tries to get Option from parameter.
     *
     * @param string $name
     * @return int|string|array<int|string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromConfig(string $name): int|string|array|null
    {
        if (!$this->hasOptionFromConfig($name)) {
            return null;
        }

        $configPath = $this->getConfigPath($name);

        $value = $this->getConfig()->getKey($configPath);

        if (!is_int($value) && !is_string($value) && !is_array($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getPageNumber(): int
    {
        if (isset($this->pageNumber)) {
            return $this->pageNumber;
        }

        if (!$this->getConfig()->hasKey('pages')) {
            throw new LogicException('Config.yml does not contain "pages" key.');
        }

        $pages = $this->getConfig()->getKeyArray('pages');

        $pageNumber = null;

        $index = -1;

        foreach ($pages as $page) {
            if (!is_array($page)) {
                throw new LogicException('Config.yml contains invalid format for a single page. Array expected.');
            }

            $index++;

            if (!array_key_exists('year', $page) || !array_key_exists('month', $page)) {
                continue;
            }

            if ((int) $page['year'] === $this->year && (int) $page['month'] === $this->month) {
                $pageNumber = $index;
                break;
            }
        }

        if (is_null($pageNumber)) {
            throw new LogicException(sprintf('Could not find page with year "%s" and month "%s". Please check your config.yml.', $this->year, $this->month));
        }

        $this->pageNumber = $pageNumber;

        return $pageNumber;
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

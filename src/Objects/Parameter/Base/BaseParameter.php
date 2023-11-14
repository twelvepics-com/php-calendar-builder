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

namespace App\Objects\Parameter\Base;

use App\Calendar\Design\Base\DesignBase;
use App\Calendar\Design\GdImage\GdImageBlank;
use App\Calendar\Design\GdImage\GdImageBlankJTAC;
use App\Calendar\Design\GdImage\GdImageDefault;
use App\Calendar\Design\GdImage\GdImageDefaultJTAC;
use App\Calendar\Design\ImageMagick\ImageMagickBlank;
use App\Calendar\Design\ImageMagick\ImageMagickBlankJTAC;
use App\Calendar\Design\ImageMagick\ImageMagickDefault;
use App\Calendar\Design\ImageMagick\ImageMagickDefaultJTCA;
use App\Constants\Design;
use App\Constants\Parameter\Argument;
use App\Constants\Parameter\Option;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
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
 * Class Target
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class BaseParameter
{
    /* Constants. */
    final public const CONFIG_NAME = 'config.yml';

    protected const URL_TWELVEPICS_LIST = 'https://c.twelvepics.com/l/%s';

    protected const URL_TWELVEPICS_DETAIL = 'https://c.twelvepics.com/d/%s/%d';

    /* Year of the page. */
    final public const DEFAULT_YEAR = 2024;
    private int $year = self::DEFAULT_YEAR;

    /* Month of the page. */
    final public const DEFAULT_MONTH = 1;
    private int $month = self::DEFAULT_MONTH;

    protected Json|null $config = null;

    private int $pageNumber;

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(protected readonly KernelInterface $appKernel)
    {
        $this->unsetPageNumber();
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
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    protected function setYear(int $year): void
    {
        $this->year = $year;
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
     */
    protected function setMonth(int $month): void
    {
        $this->month = $month;
    }

    /**
     * @return Json|null
     */
    public function getConfig(): ?Json
    {
        return $this->config;
    }

    /**
     * @param Json|null $config
     */
    public function setConfig(?Json $config): void
    {
        $this->config = $config;
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

        if (is_null($this->config)) {
            throw new LogicException('No config.yml was found.');
        }

        if (!$this->config->hasKey('pages')) {
            throw new LogicException('Config.yml does not contain "pages" key.');
        }

        $pages = $this->config->getKeyArray('pages');

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
    protected function getConfigPath(string $name): array
    {
        return match ($name) {
            Option::YEAR => ['settings', 'defaults', 'year'],
            Option::MONTH => ['settings', 'defaults', 'month'],

            Option::SOURCE => ['pages', (string) $this->getPageNumber(), 'source'],
            Option::TARGET => ['pages', (string) $this->getPageNumber(), 'target'],
            Option::PAGE_TITLE => ['pages', (string) $this->getPageNumber(), 'page-title'],
            Option::TITLE => ['pages', (string) $this->getPageNumber(), 'title'],
            Option::SUBTITLE => ['pages', (string) $this->getPageNumber(), 'subtitle'],
            Option::URL => ['pages', (string) $this->getPageNumber(), 'url'],
            Option::COORDINATE => ['pages', (string) $this->getPageNumber(), 'coordinate'],

            Option::QUALITY => ['settings', 'output', 'quality'],
            Option::TRANSPARENCY => ['settings', 'output', 'transparency'],

            Option::DESIGN_ENGINE_DEFAULT => ['settings', 'defaults', 'design', 'engine'],
            Option::DESIGN_TYPE_DEFAULT => ['settings', 'defaults', 'design', 'type'],
            Option::DESIGN_CONFIG_DEFAULT => ['settings', 'defaults', 'design', 'config'],
            Option::DESIGN_ENGINE => ['pages', (string) $this->getPageNumber(), 'design', 'engine'],
            Option::DESIGN_TYPE => ['pages', (string) $this->getPageNumber(), 'design', 'type'],
            Option::DESIGN_CONFIG => ['pages', (string) $this->getPageNumber(), 'design', 'config'],

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
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
    protected function hasOptionFromConfig(string $name): bool
    {
        if (is_null($this->config)) {
            return false;
        }

        $configPath = $this->getConfigPath($name);

        return $this->config->hasKey($configPath);
    }

    /**
     * Tries to get Option from parameter.
     *
     * @param string $name
     * @return int|string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromConfig(string $name): int|string|null
    {
        if (!$this->hasOptionFromConfig($name)) {
            return null;
        }
        if (is_null($this->config)) {
            return null;
        }

        $configPath = $this->getConfigPath($name);

        $value = $this->config->getKey($configPath);

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
        if (is_null($this->config)) {
            return null;
        }

        $configPath = $this->getConfigPath($name);

        $value = $this->config->getKey($configPath);

        if (is_int($value) || is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
    }

    /**
     * Returns if the option was given via parameter.
     *
     * @param InputInterface $input
     * @param string $name
     * @return bool
     */
    protected function hasOptionFromParameter(InputInterface $input, string $name): bool
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
     * Returns value from cli parameter or config file.
     *
     * @param InputInterface $input
     * @param string $name
     * @return int|string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getOptionFromParameterOrConfig(InputInterface $input, string $name): int|string|null
    {
        if ($this->hasOptionFromParameter($input, $name)) {
            return $this->getOptionFromParameter($input, $name);
        }

        return $this->getOptionFromConfig($name);
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
            $input->hasParameterOption(sprintf('--%s', $name)) => $this->getOptionFromParameter($input, $name),
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
     * @param File $config
     * @return void
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addConfig(File $config): void
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
            $this->setConfig(null);
            return;
        }
    }

    /**
     * Returns the source path from the image.
     *
     * @param InputInterface $input
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getSourcePath(InputInterface $input): string
    {
        $source = $input->getArgument(Argument::SOURCE);

        if (!is_string($source) && !is_int($source)) {
            throw new LogicException(sprintf('Invalid argument for source "%s".', Argument::SOURCE));
        }

        $sourcePath = (string) $source;

        $configGiven = is_dir($sourcePath) || basename($sourcePath) === self::CONFIG_NAME;

        if ($configGiven) {
            $pathConfig = match (true) {
                is_dir($sourcePath) => sprintf('%s/%s', $sourcePath, self::CONFIG_NAME),
                default => $sourcePath,
            };

            if (!is_dir($sourcePath)) {
                $sourcePath = dirname($sourcePath);
            }

            $this->addConfig(new File($pathConfig, $this->appKernel->getProjectDir()));

            if (is_null($this->config)) {
                throw new LogicException('The config.yml file was not found.');
            }

            /* Set calendar month and year (must be called first!). */
            $this->setOptionFromParameter($input, Option::YEAR);
            $this->setOptionFromParameter($input, Option::MONTH);

            $source = $this->getOptionFromConfig(Option::SOURCE);

            $sourcePath = sprintf('%s/%s', $sourcePath, $source);
        }

        return $sourcePath;
    }

    /**
     * Returns the target path from the image.
     *
     * @param InputInterface $input
     * @param int $year
     * @param int $month
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getTargetPath(InputInterface $input, int $year, int $month): string
    {
        $sourcePath = $this->getSourcePath($input);

        $sourceDirectory = pathinfo($sourcePath, PATHINFO_DIRNAME);

        if ($this->hasOptionFromConfig(Option::TARGET)) {
            return sprintf('%s/%s', $sourceDirectory, $this->getOptionFromConfig(Option::TARGET));
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

        return sprintf('%s/%s-%s.%s', $sourceDirectory, $year, $month, $extension);
    }

    /**
     * Returns the design according to the config.
     *
     * @return DesignBase
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getDesign(): DesignBase
    {
        $designEngine = match (true) {
            $this->hasOptionFromConfig(Option::DESIGN_ENGINE) => $this->getOptionFromConfig(Option::DESIGN_ENGINE),
            $this->hasOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT) => $this->getOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT),
            default => null,
        };

        $designType = match (true) {
            $this->hasOptionFromConfig(Option::DESIGN_TYPE) => $this->getOptionFromConfig(Option::DESIGN_TYPE),
            $this->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => $this->getOptionFromConfig(Option::DESIGN_TYPE_DEFAULT),
            default => null,
        };

        /* Use GdImageDefault as default design. */
        if (is_null($designEngine) || is_null($designType)) {
            return new GdImageDefault($this->appKernel, null);
        }

        $designConfigSource = match (true) {
            $this->hasOptionFromConfig(Option::DESIGN_TYPE) => Option::DESIGN_CONFIG,
            $this->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => Option::DESIGN_CONFIG_DEFAULT,
            default => null,
        };

        $designConfig = match (true) {
            is_null($designConfigSource) => throw new LogicException('Invalid design config.'),
            $this->hasOptionFromConfig($designConfigSource) => $this->getOptionFromConfigAsArray($designConfigSource),
            default => null,
        };
        $designConfigJson = match (true) {
            is_array($designConfig) => new Json($designConfig),
            default => null,
        };

        return match ($designEngine) {
            'gdimage' => match ($designType) {
                Design::BLANK => new GdImageBlank($this->appKernel, $designConfigJson),
                Design::BLANK_JTAC => new GdImageBlankJTAC($this->appKernel, $designConfigJson),
                Design::DEFAULT => new GdImageDefault($this->appKernel, $designConfigJson),
                Design::DEFAULT_JTAC => new GdImageDefaultJTAC($this->appKernel, $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            'imagick' => match ($designType) {
                Design::BLANK => new ImageMagickBlank($this->appKernel, $designConfigJson),
                Design::BLANK_JTAC => new ImageMagickBlankJTAC($this->appKernel, $designConfigJson),
                Design::DEFAULT => new ImageMagickDefault($this->appKernel, $designConfigJson),
                Design::DEFAULT_JTAC => new ImageMagickDefaultJTCA($this->appKernel, $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            default => throw new LogicException(sprintf('Unsupported design engine "%s" was given.', $designEngine)),
        };
    }
}

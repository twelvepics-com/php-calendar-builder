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

namespace App\Objects\Input;

use App\Constants\Parameter\Option;
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

/**
 * Class Input
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-28)
 * @since 0.1.0 (2023-11-28) First version.
 */
class Input
{
    private int $pageNumber;

    /**
     * @param InputInterface $input
     * @param Json|null $config
     */
    public function __construct(
        private readonly InputInterface $input,
        private readonly Json|null $config = null
    )
    {
    }

    /**
     * Returns the config path.
     *
     * @param string $name
     * @param int|null $year
     * @param int|null $month
     * @return array<int, string>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getConfigPath(string $name, int $year = null, int $month = null): array
    {
        return match ($name) {

            /* Source options */
            Option::SOURCE => ['pages', (string) $this->getPageNumber($year, $month), 'source'],

            /* Target options */
            Option::YEAR => ['settings', 'defaults', 'year'],
            Option::MONTH => ['settings', 'defaults', 'month'],
            Option::TARGET => ['pages', (string) $this->getPageNumber($year, $month), 'target'],
            Option::PAGE_TITLE => ['pages', (string) $this->getPageNumber($year, $month), 'page-title'],
            Option::TITLE => ['pages', (string) $this->getPageNumber($year, $month), 'title'],
            Option::SUBTITLE => ['pages', (string) $this->getPageNumber($year, $month), 'subtitle'],
            Option::LOGO => ['pages', (string) $this->getPageNumber($year, $month), 'logo'],
            Option::URL => ['pages', (string) $this->getPageNumber($year, $month), 'url'],
            Option::COORDINATE => ['pages', (string) $this->getPageNumber($year, $month), 'coordinate'],

            /* Output options */
            Option::OUTPUT_QUALITY => ['settings', 'output', 'quality'],
            Option::OUTPUT_FORMAT => ['settings', 'output', 'transparency'],

            /* Design options (default) */
            Option::DESIGN_ENGINE_DEFAULT => ['settings', 'defaults', 'design', 'engine'],
            Option::DESIGN_TYPE_DEFAULT => ['settings', 'defaults', 'design', 'type'],
            Option::DESIGN_CONFIG_DEFAULT => ['settings', 'defaults', 'design', 'config'],

            /* Design options (via page) */
            Option::DESIGN_ENGINE => ['pages', (string) $this->getPageNumber($year, $month), 'design', 'engine'],
            Option::DESIGN_TYPE => ['pages', (string) $this->getPageNumber($year, $month), 'design', 'type'],
            Option::DESIGN_CONFIG => ['pages', (string) $this->getPageNumber($year, $month), 'design', 'config'],

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Returns if the option was given via parameter.
     *
     * @param string $name
     * @return bool
     */
    public function hasOptionFromParameter(string $name): bool
    {
        return $this->input->hasParameterOption(sprintf('--%s', $name));
    }

    /**
     * Tries to get option from parameter.
     *
     * @param string $name
     * @return int|string|null
     */
    public function getOptionFromParameter(string $name): int|string|null
    {
        $value = $this->input->getOption($name);

        if (is_null($value)) {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
    }

    /**
     * Returns if config exists within the config.yml file.
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
        if (is_null($this->config)) {
            return false;
        }

        $configPath = $this->getConfigPath($name, $year, $month);

        return $this->config->hasKey($configPath);
    }

    /**
     * Tries to get Option from parameter as an array.
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
        if (!$this->hasOptionFromConfig($name, $year, $month)) {
            return null;
        }

        if (is_null($this->config)) {
            return null;
        }

        $configPath = $this->getConfigPath($name, $year, $month);

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
     * Tries to get Option from parameter.
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
    public function getOptionFromConfig(string $name, int $year = null, int $month = null): int|string|array|null
    {
        if (!$this->hasOptionFromConfig($name, $year, $month)) {
            return null;
        }

        if (is_null($this->config)) {
            return null;
        }

        $configPath = $this->getConfigPath($name, $year, $month);

        $value = $this->config->getKey($configPath);

        if (!is_int($value) && !is_string($value) && !is_array($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        return $value;
    }

    /**
     * Returns the page number according to the given year and month.
     *
     * @param int|null $year
     * @param int|null $month
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
    public function getPageNumber(int $year = null, int $month = null): int
    {
        if (isset($this->pageNumber)) {
            return $this->pageNumber;
        }

        if (is_null($year) && is_null($month)) {
            throw new LogicException('Both year and month must be given.');
        }

        if (is_null($this->config)) {
            throw new LogicException('Config must be set.');
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

            if ((int) $page['year'] === $year && (int) $page['month'] === $month) {
                $pageNumber = $index;
                break;
            }
        }

        if (is_null($pageNumber)) {
            throw new LogicException(sprintf('Could not find page with year "%s" and month "%s". Please check your config.yml.', $year, $month));
        }

        $this->pageNumber = $pageNumber;

        return $pageNumber;
    }
}

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
 * Class Target
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class BaseParameter
{
    /* Year of the page. */
    final public const DEFAULT_YEAR = 2024;
    private int $year = self::DEFAULT_YEAR;

    /* Month of the page. */
    final public const DEFAULT_MONTH = 1;
    private int $month = self::DEFAULT_MONTH;

    protected Json|null $config = null;

    private int $pageNumber;

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
    private function getPageNumber(): int
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
            Option::COORDINATE => ['pages', (string) $this->getPageNumber(), 'coordinate'],

            Option::QUALITY => ['settings', 'output', 'quality'],
            Option::TRANSPARENCY => ['settings', 'output', 'transparency'],

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
    protected function hasConfig(string $name): bool
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
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    public function getOptionFromConfig(string $name): int|string|null
    {
        if (!$this->hasConfig($name)) {
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
     * Tries to get option from parameter.
     *
     * @param InputInterface $input
     * @param string $name
     * @return int|string|null
     */
    protected function getOptionFromParameter(InputInterface $input, string $name): int|string|null
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
}

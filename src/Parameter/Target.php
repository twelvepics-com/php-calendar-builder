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

namespace App\Parameter;

use App\Constants\Parameter\Option;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use UnexpectedValueException;

/**
 * Class Target
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class Target
{
    /* Year of the page. */
    final public const DEFAULT_YEAR = 2024;
    private int $year = self::DEFAULT_YEAR;

    /* Month of the page. */
    final public const DEFAULT_MONTH = 1;
    private int $month = self::DEFAULT_MONTH;

    /* Quality from bad 0 to best 100. */
    final public const DEFAULT_QUALITY = 100;
    private int $quality = self::DEFAULT_QUALITY;

    /* Transparency from 0 (visible) to 100 (invisible). */
    final public const DEFAULT_TRANSPARENCY = 40;
    private int $transparency = self::DEFAULT_TRANSPARENCY;

    /* Title of the page. */
    final public const DEFAULT_PAGE_TITLE = 'Page Title';
    private string $pageTitle = self::DEFAULT_PAGE_TITLE;

    /* Title of the page. */
    final public const DEFAULT_TITLE = 'Title';
    private string $title = self::DEFAULT_TITLE;

    /* Subtitle of the page. */
    final public const DEFAULT_SUBTITLE = 'Subtitle';
    private string $subtitle = self::DEFAULT_SUBTITLE;

    /* Coordinate of the picture. */
    final public const DEFAULT_COORDINATE = 'Coordinate';
    private string $coordinate = self::DEFAULT_COORDINATE;

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
    private function setYear(int $year): void
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
    private function setMonth(int $month): void
    {
        $this->month = $month;
    }

    /**
     * @return int
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * @param int $quality
     */
    private function setQuality(int $quality): void
    {
        $this->quality = $quality;
    }

    /**
     * @return int
     */
    public function getTransparency(): int
    {
        return $this->transparency;
    }

    /**
     * @param int $transparency
     */
    public function setTransparency(int $transparency): void
    {
        $this->transparency = $transparency;
    }

    /**
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    /**
     * @param string $pageTitle
     */
    public function setPageTitle(string $pageTitle): void
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    private function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    /**
     * @param string $subtitle
     */
    private function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return string
     */
    public function getCoordinate(): string
    {
        if (!isset($this->coordinate)) {
            throw new UnexpectedValueException(sprintf('Call method readParameter before using this method "%s".', __METHOD__));
        }

        return $this->coordinate;
    }

    /**
     * @param string $coordinate
     * @throws CaseUnsupportedException
     */
    private function setCoordinate(string $coordinate): void
    {
        try {
            $this->coordinate = (new Coordinate($coordinate))->getStringDMS();
        } catch (ParserException) {
            $this->coordinate = $coordinate;
        }
    }

    /**
     * Sets the option to this class.
     *
     * @param InputInterface $input
     * @param string $name
     * @return void
     * @throws CaseUnsupportedException
     */
    private function setOption(InputInterface $input, string $name): void
    {
        $value = $input->getOption($name);

        if (is_null($value)) {
            return;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException('Unexpected value for option.');
        }

        match ($name) {
            Option::QUALITY => $this->setQuality((int) $value),

            Option::YEAR => $this->setYear((int) $value),
            Option::MONTH => $this->setMonth((int) $value),

            Option::PAGE_TITLE => $this->setPageTitle((string) $value),
            Option::TITLE => $this->setTitle((string) $value),
            Option::SUBTITLE => $this->setSubtitle((string) $value),
            Option::COORDINATE => $this->setCoordinate((string) $value),

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Reads and sets the parameter to this class.
     *
     * @param InputInterface $input
     * @return void
     * @throws CaseUnsupportedException
     */
    public function readParameter(InputInterface $input): void
    {
        /* Set calendar options. */
        $this->setOption($input, Option::QUALITY);

        /* Set calendar month and year. */
        $this->setOption($input, Option::YEAR);
        $this->setOption($input, Option::MONTH);

        /* Set calendar texts. */
        $this->setOption($input, Option::PAGE_TITLE);
        $this->setOption($input, Option::TITLE);
        $this->setOption($input, Option::SUBTITLE);
        $this->setOption($input, Option::COORDINATE);
    }
}

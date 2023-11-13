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

use App\Constants\Parameter\Option;
use App\Objects\Parameter\Base\BaseParameter;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
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
class Target extends BaseParameter
{
    private File $image;

    /* Quality from bad 0 to best 100. */
    final public const DEFAULT_QUALITY = 100;
    private int $quality = self::DEFAULT_QUALITY;

    /* Transparency from 0 (visible) to 100 (invisible). */
    final public const DEFAULT_TRANSPARENCY = 60;
    private int $transparency = self::DEFAULT_TRANSPARENCY;

    /* Title of the page. */
    final public const DEFAULT_PAGE_TITLE = 'Page Title';
    private string $pageTitle = self::DEFAULT_PAGE_TITLE;

    /* Title of the page. */
    final public const DEFAULT_TITLE = 'Title';
    private string|null $title = self::DEFAULT_TITLE;

    /* Subtitle of the page. */
    final public const DEFAULT_SUBTITLE = 'Subtitle';
    private string|null $subtitle = self::DEFAULT_SUBTITLE;

    /* Subtitle of the page. */
    final public const DEFAULT_URL = 'auto';
    private string $url = self::DEFAULT_SUBTITLE;

    /* Coordinate of the picture. */
    final public const DEFAULT_COORDINATE = 'Coordinate';
    private string $coordinate = self::DEFAULT_COORDINATE;

    /**
     * @return File
     */
    public function getImage(): File
    {
        return $this->image;
    }

    /**
     * @param File $image
     * @return self
     */
    public function setImage(File $image): self
    {
        $this->image = $image;

        return $this;
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
     * @return string|null
     */
    public function getTitle(): string|null
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    private function setTitle(string|null $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getSubtitle(): string|null
    {
        return $this->subtitle;
    }

    /**
     * @param string|null $subtitle
     */
    private function setSubtitle(string|null $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Returns the url of this page.
     *
     * @param string $identification
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getUrl(string $identification): string
    {
        if ($this->url === self::DEFAULT_URL) {
            return match (true) {
                $this->getMonth() === 0 => sprintf(self::URL_TWELVEPICS_LIST, $identification),
                default => sprintf(self::URL_TWELVEPICS_DETAIL, $identification, $this->getPageNumber()),
            };
        }

        return $this->url;
    }

    /**
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
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
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
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

            Option::PAGE_TITLE => $this->setPageTitle((string) $value),
            Option::TITLE => $this->setTitle((string) $value),
            Option::SUBTITLE => $this->setSubtitle((string) $value),
            Option::URL => $this->setUrl((string) $value),
            Option::COORDINATE => $this->setCoordinate((string) $value),

            Option::QUALITY => $this->setQuality((int) $value),
            Option::TRANSPARENCY => $this->setTransparency((int) $value),

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Reads and sets the parameter to this class.
     *
     * @param InputInterface $input
     * @param Json|null $config
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function readParameter(InputInterface $input, Json|null $config = null): void
    {
        $this->unsetPageNumber();

        $this->config = $config;

        /* Set calendar month and year (must be called first!). */
        $this->setOptionFromParameter($input, Option::YEAR);
        $this->setOptionFromParameter($input, Option::MONTH);

        /* Set calendar texts. */
        $this->setOptionFromParameter($input, Option::PAGE_TITLE);
        $this->setOptionFromParameter($input, Option::TITLE);
        $this->setOptionFromParameter($input, Option::SUBTITLE);
        $this->setOptionFromParameter($input, Option::URL);
        $this->setOptionFromParameter($input, Option::COORDINATE);

        /* Set calendar options. */
        $this->setOptionFromParameter($input, Option::QUALITY);
        $this->setOptionFromParameter($input, Option::TRANSPARENCY);

        /* Reset title and subtitle if the main calendar page is currently generated. */
        if ($this->getMonth() !== 0) {
            $this->setTitle(null);
            $this->setSubtitle(null);
        }

        /* Sets the target image. */
        $this->setImage(new File($this->getTargetPath($input, $this->getYear(), $this->getMonth()), $this->appKernel->getProjectDir()));
    }
}

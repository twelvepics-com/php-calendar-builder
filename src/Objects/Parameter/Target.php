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
use App\Constants\Service\Calendar\CalendarBuilderService;
use App\Objects\Parameter\Base\BaseParameter;
use Ixnode\PhpContainer\File;
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
    private File $path;

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



    /* Output quality from bad 0 to best 100. */
    final public const DEFAULT_OUTPUT_QUALITY = 100;
    private int $outputQuality = self::DEFAULT_OUTPUT_QUALITY;

    /* Output format. */
    final public const DEFAULT_OUTPUT_FORMAT = CalendarBuilderService::IMAGE_JPG;
    private string $outputFormat = self::DEFAULT_OUTPUT_FORMAT;

    /**
     * @return File
     */
    public function getPath(): File
    {
        return $this->path;
    }

    /**
     * @param File $path
     * @return self
     */
    public function setPath(File $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return int
     */
    public function getOutputQuality(): int
    {
        return $this->outputQuality;
    }

    /**
     * @param int $quality
     */
    private function setQuality(int $quality): void
    {
        $this->outputQuality = $quality;
    }

    /**
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    /**
     * @param string $outputFormat
     */
    public function setOutputFormat(string $outputFormat): void
    {
        $this->outputFormat = $outputFormat;
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
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getUrl(): string
    {
        $identification = $this->getParameterWrapper()->getIdentification();

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
    private function setOptionFromParameter(string $name): void
    {
        $value = match (true) {
            $this->getParameterWrapper()->hasOptionFromParameter($name) => $this->getParameterWrapper()->getOptionFromParameter($name),
            $this->getParameterWrapper()->hasOptionFromConfig($name) => $this->getParameterWrapper()->getOptionFromConfig($name),
            default => $this->getParameterWrapper()->getOptionFromParameter($name),
        };

        if (is_null($value)) {
            return;
        }

        if (is_array($value)) {
            throw new LogicException('Array is not supported.');
        }

        match ($name) {
            Option::PAGE_TITLE => $this->setPageTitle((string) $value),
            Option::TITLE => $this->setTitle((string) $value),
            Option::SUBTITLE => $this->setSubtitle((string) $value),
            Option::URL => $this->setUrl((string) $value),
            Option::COORDINATE => $this->setCoordinate((string) $value),

            Option::OUTPUT_QUALITY => $this->setQuality((int) $value),
            Option::OUTPUT_FORMAT => $this->setOutputFormat((string) $value),

            default => throw new LogicException(sprintf('Unsupported option "%s"', $name)),
        };
    }

    /**
     * Reads and sets the parameter to this class.
     *
     * @param InputInterface $input
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
    public function readParameter(InputInterface $input): void
    {
        /* Set calendar texts. */
        $this->setOptionFromParameter(Option::PAGE_TITLE);
        $this->setOptionFromParameter(Option::TITLE);
        $this->setOptionFromParameter(Option::SUBTITLE);
        $this->setOptionFromParameter(Option::URL);
        $this->setOptionFromParameter(Option::COORDINATE);

        /* Set calendar options. */
        $this->setOptionFromParameter(Option::OUTPUT_QUALITY);
        $this->setOptionFromParameter(Option::OUTPUT_FORMAT);

        /* Reset title and subtitle if the main calendar page is currently generated. */
        if ($this->getMonth() !== 0) {
            $this->setTitle(null);
            $this->setSubtitle(null);
        }

        /* Sets the target image. */
        $this->setPath(new File($this->getTargetPath($input), $this->projectDir));
    }
}

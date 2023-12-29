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

namespace App\Calendar\Config;

use App\Calendar\Structure\CalendarStructure;
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use App\Objects\Color\Color;
use App\Objects\Exif\ExifCoordinate;
use DateTimeImmutable;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
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
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 *
 * The class for calendar configuration
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-18)
 * @since 0.1.0 (2023-12-18) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Config extends Json
{
    final public const CONFIG_FILENAME = 'config.yml';

    final public const PATH_CALENDAR_ABSOLUTE = '%s/data/calendar/%s';

    final public const PATH_CALENDAR_RELATIVE = 'data/calendar/%s';

    final public const PATH_CONFIG_ABSOLUTE = '%s/data/calendar/%s/'.self::CONFIG_FILENAME;

    final public const PATH_CONFIG_RELATIVE = 'data/calendar/%s/'.self::CONFIG_FILENAME;

    final public const PATH_IMAGE_ABSOLUTE = '%s/data/calendar/%s/%s';

    final public const PATH_IMAGE_RELATIVE = 'data/calendar/%s/%s';

    final public const ENDPOINT_CALENDAR_IMAGE = '/v/%s/0.%s';

    final public const ENDPOINT_CALENDAR = '/v/%s.%s';

    final public const ENDPOINT_CALENDAR_RAW = '/v/%s';

    final public const ENDPOINT_IMAGE = '/v/%s/%s.%s';

    private const WITHOUT_YEAR = 2100;

    private string|null $error = null;

    /**
     * @param string $identifier
     * @param string $projectDir
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    public function __construct(private readonly string $identifier, private readonly string $projectDir)
    {
        $config = $this->getConfig();

        if ($config->hasKey('error')) {
            $this->error = $config->getKeyString('error');
        }

        parent::__construct($config->getArray());
    }

    /**
     * Returns true if an error occurred while loading the configuration.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Returns the name of the calendar. This is the title of the first page from the calendar.
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
    public function getCalendarName(): string
    {
        $path = ['name'];

        if (!$this->hasKey($path)) {
            return $this->identifier;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the date of the calendar. This is the title of the first page from the calendar.
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
    public function getCalendarDate(): string
    {
        $path = ['date'];

        if (!$this->hasKey($path)) {
            return (new DateTimeImmutable())->format('Y-m-d H:i');
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the title of the calendar. This is the title of the first page from the calendar.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getCalendarTitle(): string|null
    {
        $path = ['pages', '0', 'title'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->stripString($this->getKeyString($path));
    }

    /**
     * Returns the subtitle of the calendar. This is the subtitle of the first page from the calendar.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getCalendarSubtitle(): string|null
    {
        $path = ['pages', '0','subtitle'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->stripString($this->getKeyString($path));
    }

    /**
     * Returns the holidays of the calendar.
     *
     * @param int|null $year
     * @param int|null $month
     * @return array<string, array<int|string, mixed>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getHolidays(int $year = null, int $month = null): array
    {
        $path = ['holidays'];

        if (!$this->hasKey($path)) {
            return [];
        }

        $holidays = [];

        $dateYearMonth = match (true) {
            !is_null($year) && !is_null($month) => sprintf('%4d-%02d', $year, $month),
            default => null,
        };

        foreach ($this->getKeyArray($path) as $key => $holiday) {
            if (!is_null($dateYearMonth) && $dateYearMonth !== date('Y-m', (int) $key)) {
                continue;
            }

            $date = date('Y-m-d', (int) $key);

            if (is_string($holiday)) {
                $holiday = [
                    'name' => $holiday,
                ];
            }

            if (!is_array($holiday)) {
                throw new LogicException('Unable to get holiday.');
            }

            $jsonHoliday = (new Json($holiday))->setKeyMode(Json::KEY_MODE_UNDERLINE);

            if (!$jsonHoliday->hasKey('name_short')) {
                $jsonHoliday->addValue('name_short', $jsonHoliday->getKeyString('name'));
            }

            if (!$jsonHoliday->hasKey('date')) {
                $jsonHoliday->addValue('date', $date);
            }

            $holidays[$date] = $jsonHoliday->getArray();
        }

        return $holidays;
    }

    /**
     * Returns the holidays of the calendar.
     *
     * @return array<string, array<int, string>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getBirthdays(int $year, int $month): array
    {
        $path = ['birthdays'];

        if (!$this->hasKey($path)) {
            return [];
        }

        $data = [];

        $dateMonth = sprintf('%02d', $month);

        $birthdays = $this->getKeyArray($path);

        foreach ($birthdays as $key => $birthday) {
            if (is_array($birthday) && array_key_exists('date', $birthday) && array_key_exists('name', $birthday)) {
                $key = $birthday['date'];
                $birthday = $birthday['name'];
            }

            if ($dateMonth !== date('m', (int) $key)) {
                continue;
            }

            $dateYearMonthDay = sprintf('%d-', $year).date('m-d', (int) $key);

            if (!array_key_exists($dateYearMonthDay, $data)) {
                $data[$dateYearMonthDay] = [];
            }

            $dateYear = (int) date('Y', (int) $key);

            if (!is_string($birthday)) {
                throw new LogicException('Birthday is not a string.');
            }

            $name = match (true) {
                $dateYear === self::WITHOUT_YEAR => $this->getObfuscatedName($birthday),
                default => sprintf('%s (%d)', $this->getObfuscatedName($birthday), $year - $dateYear),
            };

            $data[$dateYearMonthDay][] = $name;
        }

        ksort($data);

        return $data;
    }

    /**
     * Returns an obfuscated name.
     *
     * @param string $name
     * @return string
     */
    private function getObfuscatedName(string $name): string
    {
        $name = str_replace('† ', '†', $name);

        $parts = explode(' ', $name);

        /* If the name consists of only one word, return it unchanged */
        if (count($parts) == 1) {
            return str_replace('†', '† ', $name);
        }

        $forename = array_shift($parts);

        foreach ($parts as &$part) {
            $part = $part[0].'.';
        }

        $obfuscatedName = implode(' ', [$forename, ...$parts]);

        return str_replace('†', '† ', $obfuscatedName);
    }

    /**
     * Returns the birthdays of the calendar from given pages.
     *
     * @param array<int, array<string|int, mixed>> $pages
     * @return array<string, array<int, string>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getBirthdaysFromPages(array $pages): array
    {
        $birthdays = [];

        foreach ($pages as $page) {
            $year = $this->getYearFromArray($page);
            $month = $this->getMonthFromArray($page);
            $birthdays = [...$birthdays, ...$this->getBirthdays($year, $month)];
        }

        return $birthdays;
    }

    /**
     * Returns the main calendar image for given identifier (calendar).
     *
     * @param string $format
     * @return string
     */
    public function getCalendarImageEndpoint(string $format = Image::FORMAT_JPG): string
    {
        return sprintf(self::ENDPOINT_CALENDAR_IMAGE, $this->identifier, $format);
    }

    /**
     * Returns the calendar endpoint for given format (calendar).
     *
     * @param string $format
     * @return string
     */
    public function getCalendarEndpoint(string $format = Format::HTML): string
    {
        return sprintf(self::ENDPOINT_CALENDAR, $this->identifier, $format);
    }

    /**
     * Returns the calendar raw endpoint (calendar).
     *
     * @return string
     */
    public function getCalendarEndpointRaw(): string
    {
        return sprintf(self::ENDPOINT_CALENDAR_RAW, $this->identifier);
    }

    /**
     * Returns the absolute path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarPathAbsolute(): string
    {
        return sprintf(self::PATH_CALENDAR_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarPathRelative(): string
    {
        return sprintf(self::PATH_CALENDAR_RELATIVE, $this->identifier);
    }

    /**
     * Returns the absolute config path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarConfigAbsolute(): string
    {
        return sprintf(self::PATH_CONFIG_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative config path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarConfigRelative(): string
    {
        return sprintf(self::PATH_CONFIG_RELATIVE, $this->identifier);
    }

    /**
     * Returns the public state of the calendar.
     *
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function isPublic(): bool
    {
        $path = ['settings', 'public'];

        if (!$this->hasKey($path)) {
            return false;
        }

        return $this->getKeyBoolean($path);
    }

    /**
     * Returns the identifier of the calendar.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Returns the pages configs.
     *
     * @return Json[]|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPages(): array|null
    {
        $path = ['pages'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->getKeyArrayJson($path);
    }

    /**
     * Returns the pages configs.
     *
     * @return Json[]|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPagesForApi(string $format = Image::FORMAT_JPG): array|null
    {
        $path = ['pages'];

        if (!$this->hasKey($path)) {
            return null;
        }

        $pages = [];

        foreach ($this->getKeyArrayJson($path) as $page) {
            $pages[] = new Json($this->transformPageForApi($page, $format));
        }

        return $pages;
    }

    /**
     * Returns the page config of given number.
     *
     * @param int $number
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPage(int $number): Json|null
    {
        $path = ['pages', (string) $number];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->getKeyJson($path);
    }

    /**
     * Returns the page config of given number. Convert the properties for api response before.
     *
     * @param int $number
     * @param string $format
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPageForApi(int $number, string $format = Image::FORMAT_JPG): Json|null
    {
        $path = ['pages', (string) $number];

        if (!$this->hasKey($path)) {
            return null;
        }

        $page = $this->getKeyJson($path);

        return $this->transformPageForApi($page, $format);
    }

    /**
     * Returns the image config of given number. Convert the properties for api response before.
     *
     * @param int $number
     * @param string $format
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    public function getImageArray(int $number, string $format = Image::FORMAT_JPG): Json|null
    {
        $page = $this->getPageForApi($number, $format);

        if (is_null($page)) {
            return null;
        }

        $image = $page->getArray();

        $imagePathAbsolute = $this->getImagePathAbsoluteFromSource($this->getSourceFromImageArray($image));
        $year = $this->getYearFromArray($image);
        $month = $this->getMonthFromArray($image);

        $colors = (new Color($imagePathAbsolute))->getMainColors();

        $image['coordinate'] = $this->getTranslatedCoordinate($imagePathAbsolute, $image);
        $image['coordinate_dms'] = $this->getCoordinateDms($image);
        $image['google_maps'] = $this->getGoogleMapsLink($image);

        $image = [
            ...$this->getTitleAndSubtitleFromFirstPage(),
            ...$image,
            'identifier' => $this->identifier,
            'colors' => $colors,
            'color' => $colors[0],
            'holidays' => $this->getHolidays($year, $month),
            'birthdays' => $this->getBirthdays($year, $month),
        ];

        if (array_key_exists('url', $image)) {
            unset($image['url']);
        }

        return new Json($image);
    }

    /**
     * Returns the image file for given number. If the source image does not exist, use the target image.
     *
     * @param int $number
     * @param string $imageType
     * @return File|string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getImageFile(int $number, string $imageType = CalendarStructure::IMAGE_TYPE_TARGET): File|string
    {
        $configKeyPath = ['pages', (string) $number, $imageType];

        if (!$this->hasKey($configKeyPath)) {
            return sprintf('Page with number "%d" does not exist', $number);
        }

        $target = $this->getKey($configKeyPath);

        if (is_array($target)) {
            $configKeyPath = ['pages', (string) $number, CalendarStructure::IMAGE_TYPE_TARGET];

            if (!$this->hasKey($configKeyPath)) {
                return sprintf('Page with number "%d" does not exist', $number);
            }

            $target = $this->getKey($configKeyPath);
        }

        if (!is_string($target)) {
            return 'Returned value is not a string.';
        }

        $imagePath = sprintf(CalendarBuilderService::PATH_IMAGE_RELATIVE, $this->identifier, $target);

        $file = new File($imagePath, $this->projectDir);

        if (!$file->exist()) {
            return sprintf('Image path "%s" does not exist.', $imagePath);
        }

        return $file;
    }

    /**
     * Transform the given page container for api response.
     *
     * @param Json $page
     * @param string $format
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function transformPageForApi(Json $page, string $format = Image::FORMAT_JPG): Json
    {
        $pageArray = $page->getArray();

        if (array_key_exists('page-title', $pageArray)) {
            $pageArray['page_title'] = $pageArray['page-title'];
            unset($pageArray['page-title']);
        }

        if (array_key_exists('design', $pageArray)) {
            unset($pageArray['design']);
        }

        if (array_key_exists('source', $pageArray) && is_array($pageArray['source'])) {
            unset($pageArray['source']);
        }

        foreach (['title', 'subtitle'] as $key) {
            if (array_key_exists($key, $pageArray)) {
                if (!is_string($pageArray[$key])) {
                    throw new LogicException(sprintf('String expected for key "%s".', $key));
                }

                $pageArray[$key] = $this->stripString($pageArray[$key]);
            }
        }

        $month = $this->getMonthFromArray($pageArray);

        $pageArray = [
            ...$pageArray,
            'path' => sprintf(self::ENDPOINT_IMAGE, $this->identifier, $month, $format),
        ];

        return new Json($pageArray);
    }

    /**
     * Returns the google maps link from given image.
     *
     * @param string $imagePath
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function getTranslatedCoordinate(string $imagePath, array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (is_string($coordinate) && $coordinate !== 'auto') {
            return $coordinate;
        }

        $coordinate = (new ExifCoordinate($imagePath))->getCoordinate();

        if (is_null($coordinate)) {
            return null;
        }

        return sprintf('%s, %s', $coordinate->getLatitude(), $coordinate->getLongitude());
    }

    /**
     * Returns coordinate dms string.
     *
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function getCoordinateDms(array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (!is_string($coordinate) || $coordinate === 'auto') {
            return null;
        }

        $coordinate = (new Coordinate($coordinate));

        return sprintf('%s, %s', $coordinate->getLatitudeDMS(), $coordinate->getLongitudeDMS());
    }

    /**
     * Returns the google maps link from given image.
     *
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function getGoogleMapsLink(array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (is_string($coordinate) && $coordinate !== 'auto') {
            return (new Coordinate($coordinate))->getLinkGoogle();
        }

        return null;
    }

    /**
     * Reads the configuration file.
     *
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getConfig(): Json
    {
        $pathCalendarAbsolute = sprintf(self::PATH_CALENDAR_ABSOLUTE, $this->projectDir, $this->identifier);

        if (!is_dir($pathCalendarAbsolute)) {
            return new Json(['error' => sprintf('Calendar path "%s" does not exist', $pathCalendarAbsolute)]);
        }

        $configFileRelative = new File(sprintf(self::PATH_CONFIG_RELATIVE, $this->identifier), $this->projectDir);

        if (!$configFileRelative->exist()) {
            return new Json(['error' => sprintf('Config path "%s" does not exist', $configFileRelative->getPath())]);
        }

        $configArray = Yaml::parse($configFileRelative->getContentAsText());

        if (!is_array($configArray)) {
            return new Json(['error' => sprintf('Config file "%s" is not an array', $configFileRelative->getPath())]);
        }

        return new Json($configArray);
    }

    /**
     * Strip the given string.
     *
     * @param string $string
     * @return string
     */
    private function stripString(string $string): string
    {
        $string = strip_tags($string);

        $string = preg_replace('~ +~', ' ', $string);

        if (!is_string($string)) {
            throw new LogicException('Unable to replace subtitle string.');
        }

        return $string;
    }

    /**
     * Returns the title and subtitle from the first page of the calendar.
     *
     * @return array{title?: string, subtitle?: string}
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getTitleAndSubtitleFromFirstPage(): array
    {
        $firstPage = $this->getPage(0);

        if (is_null($firstPage)) {
            return [];
        }

        return [
            'title' => $this->stripString($firstPage->getKeyString('title')),
            'subtitle' => $this->stripString($firstPage->getKeyString('subtitle')),
        ];
    }

    /**
     * Returns the source from given image. If the source is not a string, use target instead.
     *
     * @param array<int|string, mixed> $image
     * @return string
     */
    private function getSourceFromImageArray(array $image): string
    {
        $source = match (true) {
            array_key_exists('source', $image) && is_string($image['source']) => $image['source'],
            array_key_exists('target', $image) && is_string($image['target']) => $image['target'],
            default => null,
        };

        if (is_null($source)) {
            throw new LogicException('Unable to determine the source of the image.');
        }

        return $source;
    }

    /**
     * Returns the year from given image.
     *
     * @param array<int|string, mixed> $image
     * @return int
     */
    private function getYearFromArray(array $image): int
    {
        if (!array_key_exists('year', $image)) {
            throw new LogicException('Unable to determine the month of the image.');
        }

        $year = $image['year'];

        return match (true) {
            is_string($year) => (int) $year,
            is_int($year) => $year,
            default => throw new LogicException('The month of the image is not an integer.'),
        };
    }

    /**
     * Returns the month from given image.
     *
     * @param array<int|string, mixed> $image
     * @return int
     */
    private function getMonthFromArray(array $image): int
    {
        if (!array_key_exists('month', $image)) {
            throw new LogicException('Unable to determine the month of the image.');
        }

        $month = $image['month'];

        return match (true) {
            is_string($month) => (int) $month,
            is_int($month) => $month,
            default => throw new LogicException('The month of the image is not an integer.'),
        };
    }

    /**
     * Returns the absolute image path from the given source.
     *
     * @param string $source
     * @return string
     */
    private function getImagePathAbsoluteFromSource(string $source): string
    {
        return sprintf(self::PATH_IMAGE_ABSOLUTE, $this->projectDir, $this->identifier, $source);
    }
}

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

use App\Calendar\Config\Base\BaseConfig;
use App\Calendar\Structure\CalendarStructure;
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use App\Objects\Color\Color;
use DateTimeImmutable;
use Ixnode\PhpContainer\Base\BaseImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
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
 * Class CalendarConfig
 *
 * The class for calendar configuration
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-18)
 * @since 0.1.0 (2023-12-18) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CalendarConfig extends BaseConfig
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

    final public const REACT_VIEWER_HOST = 'https://calendar.twelvepics.com';

    final public const REACT_VIEWER_CALENDAR_OVERVIEW_RELATIVE = 'calendar.html?c=%s';

    final public const REACT_VIEWER_CALENDAR_PAGE_RELATIVE = 'page.html?c=%s&m=%s';

    final public const REACT_VIEWER_CALENDAR_OVERVIEW_ABSOLUTE = self::REACT_VIEWER_HOST.'/'.self::REACT_VIEWER_CALENDAR_OVERVIEW_RELATIVE;

    final public const REACT_VIEWER_CALENDAR_PAGE_ABSOLUTE = self::REACT_VIEWER_HOST.'/'.self::REACT_VIEWER_CALENDAR_PAGE_RELATIVE;

    private const WITHOUT_YEAR = 2100;

    private const YAML_CONFIG_INLINE = 4;

    private const YAML_CONFIG_IDENT = 4;

    private const PAGE_MIN = 0;

    private const PAGE_MAX = 12;

    final public const PAGE_AUTO = 'a';

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
     * @throws FunctionReplaceException
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $projectDir
    )
    {
        $config = $this->getConfig();

        if ($config->hasKey('error')) {
            $this->error = $config->getKeyString('error');
        }

        parent::__construct($config->getArray());
    }

    /**
     * Returns true if an error occurred while loading the configuration.
     */
    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    /**
     * Returns an error if an error occurred while loading the configuration.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Returns the public state of the calendar.
     *
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
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
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Returns the name of the calendar. This is the title of the first page from the calendar.
     *
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function getName(): string
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
     * @throws FunctionReplaceException
     */
    public function getDate(): string
    {
        $path = ['date'];

        if (!$this->hasKey($path)) {
            return (new DateTimeImmutable())->format('Y-m-d H:i');
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the default year of the calendar.
     *
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function getYear(): int
    {
        $path = ['settings', 'defaults', 'year'];

        if (!$this->hasKey($path)) {
            return (int) date('Y');
        }

        return $this->getKeyInteger($path);
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
     * @throws FunctionReplaceException
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
     * @throws FunctionReplaceException
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
     * Returns the country code.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getHolidayCountryCode(): string|null
    {
        $path = ['settings', 'holiday', 'country-code'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the state code.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getHolidayStateCode(): string|null
    {
        $path = ['settings', 'holiday', 'state-code'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->getKeyString($path);
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
            /* Normalize date to timestamp. */
            $key = $this->parseDateOrTimestamp($key);

            if (!is_null($dateYearMonth) && $dateYearMonth !== date('Y-m', $key)) {
                continue;
            }

            $date = date('Y-m-d', $key);

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
     * Returns the holidays of the calendar for all pages.
     *
     * @return array<string, array<int|string, mixed>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function getHolidaysAll(): array
    {
        $pages = $this->getPages();

        if (is_null($pages)) {
            return [];
        }

        $holidays = [];

        foreach ($pages as $page) {
            $year = $page->getKeyInteger('year');
            $month = $page->getKeyInteger('month');

            $holidays = [...$holidays, ...$this->getHolidays($year, $month)];
        }

        return $holidays;
    }

    /**
     * Returns the holidays of the calendar.
     *
     * @return array<string, array<int, array<int|string, mixed>>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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

        foreach ($birthdays as $date => $birthday) {
            if (is_array($birthday) && array_key_exists('date', $birthday)) {
                $date = $birthday['date'];
            }

            /* Normalize date to timestamp. */
            $date = $this->parseDateOrTimestamp($date);

            if ($dateMonth !== date('m', $date)) {
                continue;
            }

            $dateYearMonthDay = sprintf('%d-', $year).date('m-d', $date);

            if (!array_key_exists($dateYearMonthDay, $data)) {
                $data[$dateYearMonthDay] = [];
            }

            $dateYear = (int) date('Y', $date);

            if (is_string($birthday)) {
                $birthday = [
                    'name' => $birthday,
                ];
            }

            if (!is_array($birthday)) {
                throw new LogicException('Unable to get birthday.');
            }

            $jsonBirthday = (new Json($birthday))->setKeyMode(Json::KEY_MODE_UNDERLINE);

            if (!$jsonBirthday->hasKey('name_short')) {
                $jsonBirthday->addValue('name_short', $jsonBirthday->getKeyString('name'));
            }

            $jsonBirthday->addValue('date', $dateYearMonthDay);


            $jsonBirthday->addValue('name', match (true) {
                $dateYear === self::WITHOUT_YEAR => $this->getObfuscatedName($jsonBirthday->getKeyString('name')),
                default => sprintf('%s (%d)', $this->getObfuscatedName($jsonBirthday->getKeyString('name')), $year - $dateYear),
            });

            $jsonBirthday->addValue('name_short', match (true) {
                $dateYear === self::WITHOUT_YEAR => $this->getObfuscatedName($jsonBirthday->getKeyString('name_short')),
                default => sprintf('%s (%d)', $this->getObfuscatedName($jsonBirthday->getKeyString('name_short')), $year - $dateYear),
            });

            $data[$dateYearMonthDay][] = $jsonBirthday->getArray();
        }

        ksort($data);

        return $data;
    }

    /**
     * Returns the birthdays of the calendar from given pages.
     *
     * @param array<int, array<string|int, mixed>> $pages
     * @return array<string, array<int, array<int|string, mixed>>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
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
     * Returns the birthdays of the calendar for all pages.
     *
     * @return array<string, array<int, array<int|string, mixed>>>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function getBirthdaysAll(): array
    {
        $pages = $this->getPages();

        if (is_null($pages)) {
            return [];
        }

        $birthdays = [];

        foreach ($pages as $page) {
            $year = $page->getKeyInteger('year');
            $month = $page->getKeyInteger('month');

            $birthdays = [...$birthdays, ...$this->getBirthdays($year, $month)];
        }

        return $birthdays;
    }

    /**
     * Returns the main calendar image for given identifier (calendar).
     */
    public function getCalendarImageEndpoint(string $format = BaseImage::FORMAT_JPG): string
    {
        return sprintf(self::ENDPOINT_CALENDAR_IMAGE, $this->identifier, $format);
    }

    /**
     * Returns the calendar endpoint for given format (calendar).
     */
    public function getCalendarEndpoint(string $format = Format::HTML): string
    {
        return sprintf(self::ENDPOINT_CALENDAR, $this->identifier, $format);
    }

    /**
     * Returns the calendar raw endpoint (calendar).
     */
    public function getCalendarEndpointRaw(): string
    {
        return sprintf(self::ENDPOINT_CALENDAR_RAW, $this->identifier);
    }

    /**
     * Returns the absolute path to the calendar directory.
     */
    public function getCalendarPathAbsolute(): string
    {
        return sprintf(self::PATH_CALENDAR_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative path to the calendar directory.
     */
    public function getCalendarPathRelative(): string
    {
        return sprintf(self::PATH_CALENDAR_RELATIVE, $this->identifier);
    }

    /**
     * Returns the absolute config path to the calendar directory.
     */
    public function getConfigPathAbsolute(): string
    {
        return sprintf(self::PATH_CONFIG_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative config path to the calendar directory.
     */
    public function getConfigPathRelative(): string
    {
        return sprintf(self::PATH_CONFIG_RELATIVE, $this->identifier);
    }

    /**
     * Returns the absolute react viewer calendar overview url.
     */
    public function getReactViewerCalendarOverviewAbsolute(): string
    {
        return sprintf(self::REACT_VIEWER_CALENDAR_OVERVIEW_ABSOLUTE, $this->getIdentifier());
    }

    /**
     * Returns the absolute react viewer calendar overview url.
     */
    public function getReactViewerCalendarOverviewQrCode(): string
    {
        return $this->getReactViewerCalendarOverviewAbsolute();
    }

    /**
     * Returns the absolute react viewer calendar overview url.
     */
    public function getReactViewerCalendarPageAbsolute(string|int $page): string
    {
        if (!$this->isPageValid($page)) {
            throw new LogicException(sprintf('Page %s is not valid.', $page));
        }

        return sprintf(self::REACT_VIEWER_CALENDAR_PAGE_ABSOLUTE, $this->getIdentifier(), $page);
    }

    /**
     * Returns the absolute react viewer calendar overview url.
     */
    public function getReactViewerCalendarPageQrCode(string|int $page): string
    {
        return $this->getReactViewerCalendarPageAbsolute($page);
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
     * @throws FunctionReplaceException
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
     * @throws FunctionReplaceException
     */
    public function getPagesForApi(string $format = BaseImage::FORMAT_JPG): array|null
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
     * @throws FunctionReplaceException
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
     * @throws FunctionReplaceException
     */
    public function getPageForApi(int $number, string $format = BaseImage::FORMAT_JPG): Json|null
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
     * @throws FunctionReplaceException
     */
    public function getImageArray(int $number, string $format = BaseImage::FORMAT_JPG): Json|null
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
        $image['coordinate_decimal'] = $this->getCoordinateDecimal($image);
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
     * @throws FunctionReplaceException
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
     * Backups the current config file.
     */
    public function backupConfigFile(): bool
    {
        $configPath = $this->getConfigPathAbsolute();

        /* Get the backup path */
        $backupFile = $configPath.'.~'.date('Y-m-d_H-i-s');

        /* Make backup of given file. */
        return copy($configPath, $backupFile);
    }

    /**
     * Writes the current config to config file.
     *
     * @param bool $backupConfigFile
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function writeConfigFile(bool $backupConfigFile = true): bool
    {
        /* Make backup of given file. */
        if ($backupConfigFile) {
            $success = $this->backupConfigFile();

            /* Unable to back up config file. */
            if (!$success) {
                return false;
            }
        }

        /* Builds the YAML data. */
        $yamlData = Yaml::dump($this->getArray(), self::YAML_CONFIG_INLINE, self::YAML_CONFIG_IDENT);

        /* Write new content to YAML path. */
        $state = file_put_contents($this->getConfigPathAbsolute(), $yamlData);

        /* Unable to write YAML file. */
        if ($state === false) {
            return false;
        }

        return true;
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
     * Parses the given date string and returns the timestamp.
     *
     * @param string|int $value
     * @return int
     */
    private function parseDateOrTimestamp(string|int $value): int
    {
        $value = trim((string) $value);

        /* Timestamp directly given. */
        if (is_numeric($value) && (int) $value == $value) {
            return (int) $value;
        }

        /* Convert Y-m-d to timestamp. */
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            [$year, $month, $day] = explode('-', $value);

            if (checkdate((int) $month, (int) $day, (int) $year)) {
                $timestamp = mktime(12, 0, 0, (int) $month, (int) $day, (int) $year);

                if ($timestamp === false) {
                    throw new LogicException('Unable to get timestamp.');
                }

                return $timestamp;
            }
        }

        throw new LogicException('Invalid date or timestamp.');
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
     * @throws FunctionReplaceException
     */
    private function transformPageForApi(Json $page, string $format = BaseImage::FORMAT_JPG): Json
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
                if (!is_string($pageArray[$key]) && !is_int($pageArray[$key])) {
                    throw new LogicException(sprintf('String expected for key "%s".', $key));
                }

                $pageArray[$key] = $this->stripString((string) $pageArray[$key]);
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
     * Reads the configuration file.
     *
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
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
     * Returns the title and subtitle from the first page of the calendar.
     *
     * @return array{title?: string, subtitle?: string}
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
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
    protected function getImagePathAbsoluteFromSource(string $source): string
    {
        return sprintf(self::PATH_IMAGE_ABSOLUTE, $this->projectDir, $this->identifier, $source);
    }

    /**
     * Returns the relative image path from the given source.
     *
     * @param string $source
     * @return string
     */
    protected function getImagePathRelativeFromSource(string $source): string
    {
        return sprintf(self::PATH_IMAGE_RELATIVE, $this->identifier, $source);
    }

    /**
     * Checks if the given page is valid. Valid cases:
     *
     * - 0 - 12
     * - '0' - '12'
     * - 'a'
     */
    private function isPageValid(int|string $page): bool
    {
        /* 'a' (auto) is allowed */
        if ($page === self::PAGE_AUTO) {
            return true;
        }

        /* Only numerical values allowed. */
        if (!ctype_digit((string) $page)) {
            return false;
        }

        $page = (int) $page;

        return $page >= self::PAGE_MIN && $page <= self::PAGE_MAX;
    }
}

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

namespace App\Calendar\Structure;

use App\Cache\RedisCache;
use App\Calendar\Config\Config;
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
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
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class CalendarStructure
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-07)
 * @since 0.1.0 (2023-12-07) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CalendarStructure
{
    final public const IMAGE_TYPE_TARGET = 'target';

    final public const IMAGE_TYPE_SOURCE = 'source';

    private const CALENDAR_DIRECTORY = '%s/data/calendar';

    private readonly string $calendarDirectory;

    /**
     * @param KernelInterface $appKernel
     * @param RedisCache $redisCache
     */
    public function __construct(
        protected KernelInterface $appKernel,
        protected readonly RedisCache $redisCache
    )
    {
        $this->calendarDirectory = sprintf(self::CALENDAR_DIRECTORY, $this->appKernel->getProjectDir());
    }

    /**
     * Returns the config for given identifier.
     *
     * @param string $identifier
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getConfig(string $identifier): Json
    {
        $pathCalendarAbsolute = sprintf(CalendarBuilderService::PATH_CALENDAR_ABSOLUTE, $this->appKernel->getProjectDir(), $identifier);

        if (!is_dir($pathCalendarAbsolute)) {
            return new Json(['error' => sprintf('Calendar path "%s" does not exist', $pathCalendarAbsolute)]);
        }

        $configFileRelative = new File(sprintf(CalendarBuilderService::PATH_CONFIG_RELATIVE, $identifier), $this->appKernel->getProjectDir());

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
     * Returns all calendar paths, id's and names.
     *
     * @return array<int, array{
     *     identifier: string,
     *     url: string,
     *     name: string,
     *     title: string|null,
     *     subtitle: string|null,
     *     image: string,
     *     public: bool,
     *     url_json?: string,
     *     url_raw?: string,
     *     path?: string,
     *     config?: string
     * }>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getCalendars(string $format = Format::HTML, bool $withPaths = false): array
    {
        $calendars = [];

        foreach ($this->getIdentifiers() as $identifier) {
            $config = new Config($identifier, $this->appKernel->getProjectDir());

            if ($config->hasError()) {
                throw new LogicException((string) $config->getError());
            }

            $calendar = [
                'identifier' => $config->getIdentifier(),
                'url' => $config->getCalendarEndpoint($format),
                'name' => $config->getCalendarName(),
                'title' => $config->getCalendarTitle(),
                'subtitle' => $config->getCalendarSubtitle(),
                'image' => $config->getCalendarImageEndpoint(),
                'date' => $config->getCalendarDate(),
                'public' => $config->isPublic(),
            ];

            if ($format === Format::HTML) {
                $calendar['url_json'] = $config->getCalendarEndpoint(Format::JSON);
                $calendar['url_raw'] = $config->getCalendarEndpointRaw();
            }

            /* Add config paths if needed. */
            if ($withPaths) {
                $calendar['path'] = $config->getCalendarPathRelative();
                $calendar['config'] = $config->getCalendarConfigRelative();
            }

            $calendars[] = $calendar;
        }

        usort($calendars, fn($item1, $item2) => $item2['date'] <=> $item1['date']);

        return $calendars;
    }

    /**
     * Returns the calendar from given identifier.
     *
     * @param string $identifier
     * @param string $format
     * @return array<string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    public function getCalendar(string $identifier, string $format = Image::FORMAT_JPG): array|null
    {
        $config = new Config($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return null;
        }

        $pages = $this->getPages($identifier, $format);

        if (is_null($pages)) {
            return null;
        }

        return [
            'identifier' => $identifier,
            'image' => $config->getCalendarImageEndpoint(),
            'title' => $config->getCalendarTitle(),
            'subtitle' => $config->getCalendarSubtitle(),
            'public' => $config->isPublic(),
            'pages' => $pages,
            'holidays' => $config->getHolidays(),
            'birthdays' => $config->getBirthdaysFromPages($pages),
        ];
    }

    /**
     * Returns the pages from given identifier (calendar).
     *
     * @param string $identifier
     * @param string $format
     * @return array<int, array<string|int, mixed>>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPages(string $identifier, string $format = Image::FORMAT_JPG): array|null
    {
        $config = new Config($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return null;
        }

        $pages = $config->getPagesForApi($format);

        if (is_null($pages)) {
            return null;
        }

        return array_map(fn(Json $page): array => $page->getArray(), $pages);
    }

    /**
     * Returns the image from given identifier and number.
     *
     * @param string $identifier
     * @param int $number
     * @param string $format
     * @return array<string|int, mixed>|null
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
    public function getImage(string $identifier, int $number, string $format = Image::FORMAT_JPG): array|null
    {
        $config = new Config($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return null;
        }

        $image = $config->getImageArray($number, $format);

        if (is_null($image)) {
            return null;
        }

        return $image->getArray();
    }

    /**
     * Returns the image path.
     *
     * Description:
     * ------------
     * Return a Response object if an error occurred, otherwise returns the image path.
     *
     * @param string $identifier
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
    public function getImageFile(
        string $identifier,
        int $number,
        string $imageType = CalendarStructure::IMAGE_TYPE_TARGET
    ): File|string
    {
        $config = new Config($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return (string) $config->getError();
        }

        $imageFile = $config->getImageFile($number, $imageType);

        /* String means an error occurred. */
        if (is_string($imageFile)) {
            return $imageFile;
        }

        return $imageFile;
    }

    /**
     * Returns the image string from redis cache.
     *
     * @param File $file
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function getImageStringFromCache(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = Image::FORMAT_JPG
    ): string|null
    {
        /* Write or read the cached image string. */
        return $this->redisCache->getStringOrNull(
            $this->redisCache->getCacheKey($file->getPath(), $width, $quality, $format),
            $this->getImageStringCallable($file, $width, $quality, $format)
        );
    }

    /**
     * Returns the image string callable for the cache.
     *
     * @param File $file
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return callable
     */
    private function getImageStringCallable(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = Image::FORMAT_JPG
    ): callable
    {
        return function (ItemInterface $item) use ($file, $width, $format, $quality): string|null {
            $item->expiresAfter(RedisCache::REDIS_ITEM_DEFAULT_LIFETIME);

            $image = new Image($file);

            if (!$image->isImage()) {
                return null;
            }

            return $image->getImageString($width, $format, $quality);
        };
    }

    /**
     * Returns all available identifiers.
     *
     * @return array<int, string>
     */
    private function getIdentifiers(): array
    {
        if (!is_dir($this->calendarDirectory)) {
            return [];
        }

        $scanned = scandir($this->calendarDirectory);

        if ($scanned === false) {
            return [];
        }

        return array_filter($scanned, fn($element) => is_dir(sprintf('%s/%s', $this->calendarDirectory, $element)) && !in_array($element, ['.', '..']));
    }
}

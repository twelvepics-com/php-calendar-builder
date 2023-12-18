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
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use App\Objects\Color\Color;
use App\Objects\Exif\ExifCoordinate;
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

    private const PROJECT_DIRECTORY = '%s/data/calendar/%s';

    private const CONFIG_FILE = '%s/data/calendar/%s/config.yml';

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
     * @return array<int, array{identifier: string, path: string, config: string, url: string, name: string}>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    public function getCalendars(string $format = Format::HTML): array
    {
        $calendars = [];

        if (!is_dir($this->calendarDirectory)) {
            return $calendars;
        }

        $scanned = scandir($this->calendarDirectory);

        if ($scanned === false) {
            return $calendars;
        }

        $identifiers = array_filter($scanned, fn($element) => is_dir(sprintf('%s/%s', $this->calendarDirectory, $element)) && !in_array($element, ['.', '..']));

        foreach ($identifiers as $identifier) {
            $configPath = sprintf(self::CONFIG_FILE, $this->appKernel->getProjectDir(), $identifier);

            $config = new File($configPath);

            if (!$config->exist()) {
                throw new LogicException(sprintf('Config file "%s" does not exist.', $configPath));
            }

            $parsedConfig = Yaml::parse($config->getContentAsText());

            if (!is_array($parsedConfig)) {
                throw new LogicException(sprintf('Config file "%s" is not a valid YAML file.', $configPath));
            }

            $json = new Json($parsedConfig);

            $calendars[] = [
                'identifier' => $identifier,
                'path' => sprintf(self::PROJECT_DIRECTORY, $this->appKernel->getProjectDir(), $identifier),
                'config' => sprintf(self::CONFIG_FILE, $this->appKernel->getProjectDir(), $identifier),
                'url' => sprintf('/v/%s/all.%s', $identifier, $format),
                'name' => $json->hasKey('title') ? $json->getKeyString('title') : $identifier,
                'title_image' => $this->getTitleImage($identifier),
                'title' => $this->getTitle($json),
                'subtitle' => $this->getSubtitle($json),
                'public' => $json->hasKey(['settings', 'public']) && $json->getKeyBoolean(['settings', 'public']),
            ];
        }

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
     */
    public function getCalendar(string $identifier, string $format = Image::FORMAT_JPG): array|null
    {
        $config = $this->getConfig($identifier);

        if ($config->hasKey('error')) {
            return null;
        }

        $pages = $this->getPages($identifier, $format);

        if (is_null($pages)) {
            return null;
        }

        return [
            'identifier' => $identifier,
            'title_image' => $this->getTitleImage($identifier),
            'title' => $this->getTitle($identifier),
            'subtitle' => $this->getSubtitle($identifier),
            'public' => $config->hasKey(['settings', 'public']) && $config->getKeyBoolean(['settings', 'public']),
            'pages' => $pages,
        ];
    }

    /**
     * Returns the pages from given identifier (calendar).
     *
     * @param string $identifier
     * @param string $format
     * @return array<int, array<string, mixed>>|null
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
        $config = $this->getConfig($identifier);

        if ($config->hasKey('error')) {
            return null;
        }

        $configKeyPath = ['pages'];

        if (!$config->hasKey($configKeyPath)) {
            return null;
        }

        $pages = $config->getKeyArray($configKeyPath);

        $images = [];
        foreach ($pages as $number => $page) {
            if (!is_int($number)) {
                continue;
            }

            if (!is_array($page)) {
                continue;
            }

            $images[] = $this->getImageArray($identifier, $number, $page, $format);
        }

        return $images;
    }

    /**
     * Returns the google maps link from given image.
     *
     * @param string $imagePath
     * @param array<string, mixed> $image
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
     * @param array<string, mixed> $image
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
     * @param array<string, mixed> $image
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
     * Returns the image from given identifier and number.
     *
     * @param string $identifier
     * @param int $number
     * @param string $format
     * @return array<string, mixed>|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getImage(string $identifier, int $number, string $format = Image::FORMAT_JPG): array|null
    {
        $config = $this->getConfig($identifier);

        if ($config->hasKey('error')) {
            return null;
        }

        $configKeyPath = ['pages'];

        if (!$config->hasKey($configKeyPath)) {
            return null;
        }

        $pages = $config->getKeyArray($configKeyPath);

        if (!array_key_exists($number, $pages)) {
            return null;
        }

        $page = $pages[$number];

        if (!is_array($page)) {
            return null;
        }

        $image = $this->getImageArray($identifier, $number, $page, $format);

        $source = match (true) {
            array_key_exists('source', $image) && is_string($image['source']) => $image['source'],
            array_key_exists('target', $image) && is_string($image['target']) => $image['target'],
            default => null,
        };

        if (is_null($source)) {
            throw new LogicException('Unable to determine the source of the image.');
        }

        $imagePath = sprintf('%s/%s/%s', $this->calendarDirectory, $identifier, $source);

        $colors = (new Color($imagePath))->getMainColors();

        $image['month'] = $number;
        $image['identifier'] = $identifier;
        $image['colors'] = $colors;
        $image['color'] = $colors[0];
        $image['coordinate'] = $this->getTranslatedCoordinate($imagePath, $image);
        $image['coordinate_dms'] = $this->getCoordinateDms($image);
        $image['google_maps'] = $this->getGoogleMapsLink($image);

        $firstPage = $config->getKeyJson([...$configKeyPath, '0']);

        if ($firstPage->hasKey('title')) {
            $image['title'] = $this->stripString($firstPage->getKeyString('title'));
        }

        if ($firstPage->hasKey('subtitle')) {
            $image['subtitle'] = $this->stripString($firstPage->getKeyString('subtitle'));
        }

        if (array_key_exists('url', $image)) {
            unset($image['url']);
        }

        return $image;
    }

    /**
     * Returns the image from given path.
     *
     * @param string $identifier
     * @param int $number
     * @param array<string, mixed> $page
     * @param string $format
     * @return array<string, mixed>
     */
    protected function getImageArray(
        string $identifier,
        int $number,
        array $page,
        string $format = Image::FORMAT_JPG
    ): array
    {
        $path = sprintf('/v/%s/%d.%s', $identifier, $number, $format);

        $image = [
            'path' => $path,
            ...$page
        ];

        if (array_key_exists('page-title', $image)) {
            $image['page_title'] = $image['page-title'];
            unset($image['page-title']);
        }

        if (array_key_exists('design', $image)) {
            unset($image['design']);
        }

        /* Strip some fields */
        foreach (['title', 'subtitle'] as $key) {
            if (array_key_exists($key, $image)) {
                if (!is_string($image[$key])) {
                    throw new LogicException(sprintf('String expected for key "%s".', $key));
                }

                $image[$key] = $this->stripString($image[$key]);
            }
        }

        return $image;
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
        $config = $this->getConfig($identifier);

        if ($config->hasKey('error')) {
            return $config->getKeyString('error');
        }

        $configKeyPath = ['pages', (string) $number, $imageType];

        if (!$config->hasKey($configKeyPath)) {
            return sprintf('Page with number "%d" does not exist', $number);
        }

        $target = $config->getKey($configKeyPath);

        if (is_array($target)) {
            $configKeyPath = ['pages', (string) $number, CalendarStructure::IMAGE_TYPE_TARGET];

            if (!$config->hasKey($configKeyPath)) {
                return sprintf('Page with number "%d" does not exist', $number);
            }

            $target = $config->getKey($configKeyPath);
        }

        if (!is_string($target)) {
            return 'Returned value is not a string.';
        }

        $imagePath = sprintf(CalendarBuilderService::PATH_IMAGE_RELATIVE, $identifier, $target);

        $file = new File($imagePath, $this->appKernel->getProjectDir());

        if (!$file->exist()) {
            return sprintf('Image path "%s" does not exist.', $imagePath);
        }

        return $file;
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
            $item->expiresAfter(86400);

            $image = new Image($file);

            if (!$image->isImage()) {
                return null;
            }

            return $image->getImageString($width, $format, $quality);
        };
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
     * Returns the title image for given identifier (calendar).
     *
     * @param string $identifier
     * @return string
     */
    public function getTitleImage(string $identifier): string
    {
        return sprintf('/v/%s/%d.%s', $identifier, 0, Image::FORMAT_JPG);
    }

    /**
     * Returns the title of the calendar.
     *
     * @param Json|string $configOrIdentifier
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getTitle(Json|string $configOrIdentifier): string|null
    {
        $config = $this->getConfigFromIdentifierOrConfig($configOrIdentifier);

        $path = ['pages', '0', 'title'];

        if (!$config->hasKey($path)) {
            return null;
        }

        return $this->stripString($config->getKeyString($path));
    }

    /**
     * Returns the subtitle of the calendar.
     *
     * @param Json|string $configOrIdentifier
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getSubtitle(Json|string $configOrIdentifier): string|null
    {
        $config = $this->getConfigFromIdentifierOrConfig($configOrIdentifier);

        $path = ['pages', '0','subtitle'];

        if (!$config->hasKey($path)) {
            return null;
        }

        return $this->stripString($config->getKeyString($path));
    }

    /**
     * Returns the config from given identifier or config.
     *
     * @param Json|string $configOrIdentifier
     * @return Json
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getConfigFromIdentifierOrConfig(Json|string $configOrIdentifier): Json
    {
        if ($configOrIdentifier instanceof Json) {
            return $configOrIdentifier;
        }

        $config = $this->getConfig($configOrIdentifier);

        if (!$config->hasKey('error')) {
            return $config;
        }

        throw new LogicException(sprintf('Unable to get config from given identifier %s.', $configOrIdentifier));
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

        $string = preg_replace('~[ ]+~', ' ', $string);

        if (!is_string($string)) {
            throw new LogicException('Unable to replace subtitle string.');
        }

        return $string;
    }
}

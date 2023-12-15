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
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
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
 */
class CalendarStructure
{
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
            ];
        }

        return $calendars;
    }

    /**
     * Returns the images from given identifier.
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
    public function getImages(string $identifier, string $format = Image::FORMAT_JPG): array|null
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
        int $number
    ): File|string
    {
        $config = $this->getConfig($identifier);

        if ($config->hasKey('error')) {
            return $config->getKeyString('error');
        }

        $configKeyPath = ['pages', (string) $number, 'target'];

        if (!$config->hasKey($configKeyPath)) {
            return sprintf('Page with number "%d" does not exist', $number);
        }

        $target = $config->getKeyString($configKeyPath);

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
            $item->expiresAfter(3600);

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
}

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
use App\Calendar\Config\PhotoConfig;
use App\Constants\Format;
use App\Constants\Service\Photo\PhotoBuilderService;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class PhotoStructure
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-28)
 * @since 0.1.0 (2024-11-28) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PhotoStructure
{
    final public const IMAGE_TYPE_TARGET = 'target';

    private const PHOTO_DIRECTORY = '%s/data/photo';

    protected readonly RedisCache|null $redisCache;

    private readonly string $calendarDirectory;

    /**
     * @param KernelInterface $appKernel
     * @param ParameterBagInterface $parameterBag
     * @param bool $disableCache
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        protected readonly KernelInterface $appKernel,
        private readonly ParameterBagInterface $parameterBag,
        bool $disableCache = false,
    )
    {
        $this->redisCache = $disableCache ? null : new RedisCache(parameterBag: $this->parameterBag);

        $this->calendarDirectory = sprintf(self::PHOTO_DIRECTORY, $this->appKernel->getProjectDir());
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
     * @throws FunctionReplaceException
     */
    public function getConfig(string $identifier): Json
    {
        $pathPhotoSetAbsolute = sprintf(PhotoBuilderService::PATH_PHOTO_ABSOLUTE, $this->appKernel->getProjectDir(), $identifier);

        if (!is_dir($pathPhotoSetAbsolute)) {
            return new Json(['error' => sprintf('Photo path "%s" does not exist', $pathPhotoSetAbsolute)]);
        }

        $configFileRelative = new File(sprintf(PhotoBuilderService::PATH_CONFIG_RELATIVE, $identifier), $this->appKernel->getProjectDir());

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
     * Returns all photo sets paths, id's and names.
     *
     * @param string $format
     * @param bool $withPaths
     * @param bool $onlyPublic
     * @return array<int, array{
     *     identifier: string,
     *     url: string,
     *     name: string,
     *     title: string|null,
     *     subtitle: string|null,
     *     date: string,
     *     public: bool
     * }>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getPhotoSets(
        string $format = Format::HTML,
        bool $withPaths = false,
        bool $onlyPublic = false
    ): array
    {
        $photoSets = [];

        foreach ($this->getIdentifiers() as $identifier) {
            $photoConfig = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

            if ($photoConfig->hasError()) {
                throw new LogicException((string) $photoConfig->getError());
            }

            if ($onlyPublic && !$photoConfig->isPublic()) {
                continue;
            }

            $photoSet = [
                'identifier' => $photoConfig->getIdentifier(),
                'url' => $photoConfig->getPhotoSetEndpoint($format),
                'name' => $photoConfig->getPhotoSetName(),
                'title' => $photoConfig->getPhotoSetTitle(),
                'subtitle' => $photoConfig->getPhotoSetSubtitle(),
                'date' => $photoConfig->getPhotoSetDate(),
                'public' => $photoConfig->isPublic(),
            ];

            if ($format === Format::HTML) {
                $photoSet['url_json'] = $photoConfig->getPhotoSetEndpoint(Format::JSON);
                $photoSet['url_raw'] = $photoConfig->getPhotoSetEndpointRaw();
            }

            /* Add config paths if needed. */
            if ($withPaths) {
                $photoSet['path'] = $photoConfig->getPhotoSetPathRelative();
                $photoSet['config'] = $photoConfig->getPhotoSetConfigRelative();
            }

            $photoSets[] = $photoSet;
        }

        usort($photoSets, fn($item1, $item2) => $item2['date'] <=> $item1['date']);

        return $photoSets;
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
    public function getPhotoSet(string $identifier, string $format = Image::FORMAT_JPG): array|null
    {
        $photoConfig = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        if ($photoConfig->hasError()) {
            return null;
        }

        $photos = $this->getPhotos($identifier, $format);

        if (is_null($photos)) {
            return null;
        }

        return [
            'identifier' => $photoConfig->getIdentifier(),
            'photo_set' => $photoConfig->getPhotoSetEndpoint(),
            'title' => $photoConfig->getPhotoSetTitle(),
            'subtitle' => $photoConfig->getPhotoSetSubtitle(),
            'public' => $photoConfig->isPublic(),
            'photos' => $photos,
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
     * @throws FunctionReplaceException
     */
    public function getPhotos(string $identifier, string $format = Image::FORMAT_JPG): array|null
    {
        $config = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return null;
        }

        $photos = $config->getPhotosForApi($format);

        if (is_null($photos)) {
            return null;
        }

        return array_map(fn(Json $photo): array => $photo->getArray(), $photos);
    }

    /**
     * Returns the image from given identifier and number.
     *
     * @param string $identifier
     * @param string $name
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
     * @throws FunctionReplaceException
     */
    public function getImage(string $identifier, string $name, string $format = Image::FORMAT_JPG): array|null
    {
        $config = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        if ($config->hasError()) {
            return null;
        }

        $image = $config->getImageArray(
            name: $name,
            identifier: $identifier,
            format: $format
        );

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
     * @param string $name
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
    public function getImageFile(
        string $identifier,
        string $name,
        string $imageType = PhotoStructure::IMAGE_TYPE_TARGET
    ): File|string
    {
        $photoConfig = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        if ($photoConfig->hasError()) {
            return (string) $photoConfig->getError();
        }

        $imageFile = $photoConfig->getImageFile($name, $imageType);

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
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws InvalidArgumentException
     */
    public function getImageStringFromCache(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = Image::FORMAT_JPG
    ): string|null
    {
        /* Disabled cache. */
        if (is_null($this->redisCache)) {
            $image = new Image($file, ignoreOrientation: true);

            return $image->getImageString($width, $format, $quality);
        }

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

            $image = new Image($file, ignoreOrientation: true);

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

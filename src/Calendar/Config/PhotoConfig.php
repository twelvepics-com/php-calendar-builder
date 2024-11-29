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
use App\Constants\Service\Photo\PhotoBuilderService;
use App\Objects\Color\Color;
use DateTimeImmutable;
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
use Symfony\Component\Yaml\Yaml;

/**
 * Class PhotoConfig
 *
 * The class for photo configuration
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-28)
 * @since 0.1.0 (2024-11-28) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PhotoConfig extends BaseConfig
{
    final public const CONFIG_FILENAME = 'config.yml';

    final public const PATH_PHOTO_ABSOLUTE = '%s/data/photo/%s';

    final public const PATH_PHOTO_SET_RELATIVE = 'data/photo/%s';

    final public const PATH_CONFIG_ABSOLUTE = '%s/data/photo/%s/'.self::CONFIG_FILENAME;

    final public const PATH_CONFIG_RELATIVE = 'data/photo/%s/'.self::CONFIG_FILENAME;

    final public const PATH_IMAGE_ABSOLUTE = '%s/data/photo/%s/%s';

    final public const ENDPOINT_PHOTO_SET = '/pv/%s.%s';

    final public const ENDPOINT_PHOTO_SET_RAW = '/pv/%s';

    final public const ENDPOINT_PHOTO = '/pv/%s/%s';

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
     * Returns the name of the photo set.
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
    public function getPhotoSetName(): string
    {
        $path = ['name'];

        if (!$this->hasKey($path)) {
            return $this->identifier;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the date of the photo set.
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
    public function getPhotoSetDate(): string
    {
        $path = ['date'];

        if (!$this->hasKey($path)) {
            return (new DateTimeImmutable())->format('Y-m-d H:i');
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the title of the photo set.
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
    public function getPhotoSetTitle(): string|null
    {
        $path = ['title'];

        if (!$this->hasKey($path)) {
            return $this->identifier;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the subtitle of the photo set.
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
    public function getPhotoSetSubtitle(): string|null
    {
        $path = ['subtitle'];

        if (!$this->hasKey($path)) {
            return $this->identifier;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the photo set endpoint for given format.
     *
     * @param string $format
     * @return string
     */
    public function getPhotoSetEndpoint(string $format = Format::HTML): string
    {
        return sprintf(self::ENDPOINT_PHOTO_SET, $this->identifier, $format);
    }

    /**
     * Returns the photo set raw endpoint.
     *
     * @return string
     */
    public function getPhotoSetEndpointRaw(): string
    {
        return sprintf(self::ENDPOINT_PHOTO_SET_RAW, $this->identifier);
    }

    /**
     * Returns the absolute path to the photo set directory.
     *
     * @return string
     */
    public function getPhotoSetPathAbsolute(): string
    {
        return sprintf(self::PATH_PHOTO_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative path to the photo set directory.
     *
     * @return string
     */
    public function getPhotoSetPathRelative(): string
    {
        return sprintf(self::PATH_PHOTO_SET_RELATIVE, $this->identifier);
    }

    /**
     * Returns the absolute config path to the photo set directory.
     *
     * @return string
     */
    public function getPhotoSetConfigAbsolute(): string
    {
        return sprintf(self::PATH_CONFIG_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative config path to the photo set directory.
     *
     * @return string
     */
    public function getPhotoSetConfigRelative(): string
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
     * @throws FunctionReplaceException
     */
    public function getPhotosForApi(string $format = Image::FORMAT_JPG): array|null
    {
        $path = ['photos'];

        if (!$this->hasKey($path)) {
            return null;
        }

        $photos = [];

        foreach ($this->getKeyArrayJson($path) as $photo) {
            $photos[] = new Json($this->transformPhotoForApi($photo, $format));
        }

        return $photos;
    }

    /**
     * Returns the page config of given number. Convert the properties for api response before.
     *
     * @param string $name
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
    public function getPhotoForApi(string $name, string $format = Image::FORMAT_JPG): Json|null
    {
        $path = ['photos', $name];

        if (!$this->hasKey($path)) {
            return null;
        }

        $photo = $this->getKeyJson($path);

        return $this->transformPhotoForApi($photo, $format);
    }

    /**
     * Returns the image config of given number. Convert the properties for api response before.
     *
     * @param string $name
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
    public function getImageArray(string $name, string $format = Image::FORMAT_JPG): Json|null
    {
        $photo = $this->getPhotoForApi($name, $format);

        if (is_null($photo)) {
            return null;
        }

        $image = $photo->getArray();

        $imagePathAbsolute = $this->getImagePathAbsoluteFromSource($this->getSourceFromImageArray($image));

        $colors = (new Color($imagePathAbsolute))->getMainColors();

        $image['coordinate'] = $this->getTranslatedCoordinate($imagePathAbsolute, $image);
        $image['coordinate_dms'] = $this->getCoordinateDms($image);
        $image['coordinate_decimal'] = $this->getCoordinateDecimal($image);
        $image['google_maps'] = $this->getGoogleMapsLink($image);
        $image['year'] = $this->getYearFromArray($image);
        $image['month'] = $this->getMonthFromArray($image);
        $image['day'] = $this->getDayFromArray($image);

        $image = [
            ...$image,
            'identifier' => $this->identifier,
            'colors' => $colors,
            'color' => $colors[0],
        ];

        if (array_key_exists('url', $image)) {
            unset($image['url']);
        }

        return new Json($image);
    }

    /**
     * Returns the image file for given name.
     *
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
    public function getImageFile(string $name, string $imageType = CalendarStructure::IMAGE_TYPE_TARGET): File|string
    {
        $configKeyPath = ['photos', $name, $imageType];

        if (!$this->hasKey($configKeyPath)) {
            return sprintf('Photo with name "%s" does not exist', $name);
        }

        $target = $this->getKey($configKeyPath);

        if (!is_string($target)) {
            return 'Returned value is not a string.';
        }

        $imagePath = sprintf(PhotoBuilderService::PATH_IMAGE_RELATIVE, $this->identifier, $target);

        $file = new File($imagePath, $this->projectDir);

        if (!$file->exist()) {
            return sprintf('Image path "%s" does not exist.', $imagePath);
        }

        return $file;
    }

    /**
     * Transform the given photo container for api response.
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function transformPhotoForApi(Json $page, string $format = Image::FORMAT_JPG): Json
    {
        $photoArray = $page->getArray();

        if (array_key_exists('photo-title', $photoArray)) {
            $photoArray['photo_title'] = $photoArray['photo-title'];
            unset($photoArray['photo-title']);
        }

        if (array_key_exists('design', $photoArray)) {
            unset($photoArray['design']);
        }

        if (array_key_exists('source', $photoArray) && is_array($photoArray['source'])) {
            unset($photoArray['source']);
        }

        foreach (['title', 'subtitle'] as $key) {
            if (array_key_exists($key, $photoArray)) {
                if (!is_string($photoArray[$key]) && !is_int($photoArray[$key])) {
                    throw new LogicException(sprintf('String expected for key "%s".', $key));
                }

                $photoArray[$key] = $this->stripString((string) $photoArray[$key]);
            }
        }

        $source = $this->getSourceFromArray($photoArray);

        $photoArray = [
            ...$photoArray,
            'path' => sprintf(self::ENDPOINT_PHOTO, $this->identifier, $source),
        ];

        return new Json($photoArray);
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
        $pathPhotoAbsolute = sprintf(self::PATH_PHOTO_ABSOLUTE, $this->projectDir, $this->identifier);

        if (!is_dir($pathPhotoAbsolute)) {
            return new Json(['error' => sprintf('Photo path "%s" does not exist', $pathPhotoAbsolute)]);
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
     * Returns the month from given photo.
     *
     * @param array<int|string, mixed> $photo
     * @return int
     */
    private function getMonthFromArray(array $photo): int
    {
        if (!array_key_exists('month', $photo)) {
            throw new LogicException('Unable to determine the month of the photo.');
        }

        $month = $photo['month'];

        return match (true) {
            is_string($month) => (int) $month,
            is_int($month) => $month,
            default => throw new LogicException('The month of the photo is not an integer.'),
        };
    }

    /**
     * Returns the day from given photo.
     *
     * @param array<int|string, mixed> $image
     * @return int
     */
    private function getDayFromArray(array $image): int
    {
        if (!array_key_exists('day', $image)) {
            throw new LogicException('Unable to determine the day of the photo.');
        }

        $day = $image['day'];

        return match (true) {
            is_string($day) => (int) $day,
            is_int($day) => $day,
            default => throw new LogicException('The day of the photo is not an integer.'),
        };
    }

    /**
     * Returns the source from given photo.
     *
     * @param array<int|string, mixed> $photo
     * @return string
     */
    private function getSourceFromArray(array $photo): string
    {
        if (!array_key_exists('source', $photo)) {
            throw new LogicException('Unable to determine the source of the photo.');
        }

        $source = $photo['source'];

        return match (true) {
            is_string($source) => $source,
            default => throw new LogicException('The source of the photo is not a string.'),
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

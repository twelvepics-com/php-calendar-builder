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

namespace App\Objects\Image;

use App\Objects\Exif\ExifCoordinate;
use App\Objects\Parameter\Source;
use Exception;
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
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ImageHolder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 */
class ImageHolder
{
    private const PATH_CALENDAR = 'data/calendar';

    private const ALLOWED_IMAGES_MIME_TYPES = ['image/jpeg', 'image/png'];

    private string $imageString;

    private string $mimeType;

    private int $sizeByte;

    private int $width;

    private int $height;

    private Coordinate|null $coordinate = null;

    private string|null $path = null;

    /**
     * @param KernelInterface $appKernel
     * @param string $identifier
     * @param string|Json $imageConfig
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function __construct(protected readonly KernelInterface $appKernel, protected string $identifier, protected string|Json $imageConfig)
    {
        $this->init();
    }

    /**
     * Returns whether the path is a valid image.
     *
     * @param string $path
     * @return bool
     */
    private function isImage(string $path): bool
    {
        $imageInformation = getimagesize($path);

        if ($imageInformation === false) {
            return false;
        }

        if (!in_array($imageInformation['mime'], self::ALLOWED_IMAGES_MIME_TYPES)) {
            return false;
        }

        return true;
    }

    /**
     * Sets the image string.
     *
     * @param string $pathRelative
     * @return void
     */
    private function setImageString(string $pathRelative): void
    {
        $pathAbsolute = sprintf('%s/%s', $this->appKernel->getProjectDir(), $pathRelative);

        if (!file_exists($pathAbsolute)) {
            throw new LogicException(sprintf('Image "%s" not found.', $pathAbsolute));
        }

        if (!$this->isImage($pathAbsolute)) {
            throw new LogicException(sprintf('Image "%s" is not an image.', $pathAbsolute));
        }

        $imageString = file_get_contents($pathAbsolute);

        if ($imageString === false) {
            throw new LogicException(sprintf('Could not read image "%s".', $pathAbsolute));
        }

        $this->path = $pathRelative;
        $this->imageString = $imageString;
    }

    /**
     * Sets the image format.
     *
     * @return void
     */
    private function setImageFormat(): void
    {
        $imageInformation = getimagesizefromstring($this->imageString);

        if ($imageInformation === false) {
            throw new LogicException(sprintf('Could not read image "%s".', $this->imageString));
        }

        $this->mimeType = match ($imageInformation['mime']) {
            'image/png' => 'image/png',
            'image/jpeg' => 'image/jpeg',
            default => throw new LogicException(sprintf('Unsupported image format "%s".', $imageInformation['mime'])),
        };
    }

    /**
     * Sets the image width and height.
     *
     * @return void
     */
    private function setImageDimensions(): void
    {
        $imageInformation = getimagesizefromstring($this->imageString);

        if ($imageInformation === false) {
            throw new LogicException(sprintf('Could not read image "%s".', $this->imageString));
        }

        $this->width = $imageInformation[0];
        $this->height = $imageInformation[1];
    }

    /**
     * Sets the image size.
     *
     * @return void
     */
    private function setImageSize(): void
    {
        $this->sizeByte = strlen($this->imageString);
    }

    /**
     * Sets the image coordinate.
     *
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function setImageCoordinate(): void
    {
        if (is_null($this->path)) {
            return;
        }

        $this->coordinate = (new ExifCoordinate($this->path))->getCoordinate();
    }

    /**
     * Initializes the image holder (according to the image path).
     *
     * @param string $imageConfig
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    private function initPath(string $imageConfig): void
    {
        $this->setImageString(sprintf('%s/%s/%s', self::PATH_CALENDAR, $this->identifier, $imageConfig));
        $this->setImageCoordinate();
    }

    /**
     * Initializes the image holder (according to the image Json object).
     *
     * @param Json $imageConfig
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     * @throws Exception
     */
    private function initJson(Json $imageConfig): void
    {
        $width = $imageConfig->getKeyInteger(['config', 'width']);
        $height = $imageConfig->getKeyInteger(['config', 'height']);
        $format = $imageConfig->getKeyString(['config', 'format']);

        $imageBuilder = (new Source($this->appKernel))->getImageBuilder($imageConfig);

        $imageBuilder->initWithoutCalendarBuilderService($width, $height, $format);

        $imageContainer = $imageBuilder->build();

        $this->imageString = $imageContainer->getTarget()->getImageString();

        $imageBuilder->setImageStringSource($this->imageString);
    }

    /**
     * Initializes the image holder.
     *
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function init(): void
    {
        match (true) {
            /* Build image from the path. */
            is_string($this->imageConfig) => $this->initPath($this->imageConfig),

            /* Build image from config. */
            $this->imageConfig instanceof Json => $this->initJson($this->imageConfig),
        };

        $this->setImageFormat();
        $this->setImageSize();
        $this->setImageDimensions();
    }

    /**
     * @return string
     */
    public function getImageString(): string
    {
        return $this->imageString;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return Coordinate|null
     */
    public function getCoordinate(): ?Coordinate
    {
        return $this->coordinate;
    }

    /**
     * @param Coordinate|null $coordinate
     * @return $this
     */
    public function setCoordinate(?Coordinate $coordinate): ImageHolder
    {
        $this->coordinate = $coordinate;
        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return int
     */
    public function getSizeByte(): int
    {
        return $this->sizeByte;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}

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

use App\Calendar\Design\DesignText;
use App\Calendar\ImageBuilder\ImageBuilderFactory;
use App\Objects\Exif\ExifCoordinate;
use App\Objects\Parameter\ParameterWrapper;
use Exception;
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

    private File|null $path = null;

    /**
     * @param KernelInterface $appKernel
     * @param string $identifier
     * @param string|Json $imageConfig
     * @param ParameterWrapper $parameterWrapper
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
    public function __construct(protected readonly KernelInterface $appKernel, protected string $identifier, protected string|Json $imageConfig, protected ParameterWrapper $parameterWrapper)
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

        $this->setPath(new File($pathRelative, $this->appKernel->getProjectDir()));

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
        $path = $this->getPath();

        if (is_null($path)) {
            return;
        }

        $this->setCoordinate((new ExifCoordinate($path->getPath()))->getCoordinate());
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
    private function initViaConfig(Json $imageConfig): void
    {
        $width = $imageConfig->getKeyInteger(['config', 'width']);
        $height = $imageConfig->getKeyInteger(['config', 'height']);
        $format = $imageConfig->getKeyString(['config', 'format']);

        $imageBuilder = (new ImageBuilderFactory($this->appKernel->getProjectDir(), $this->parameterWrapper))->getImageBuilder($imageConfig);

        $design = $imageBuilder->getDesign();

        /* Check if the design is a text design. */
        if (!$design instanceof DesignText) {
            throw new LogicException(sprintf('Only text design (DesignText) is supported for source image. %s given. Check and add other designs if needed.', $design::class));
        }

        $imageBuilder->initWithoutCalendarBuilderService($width, $height, $format);

        /* Builds the image via target. */
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
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws ParserException
     * @throws TypeInvalidException
     */
    private function init(): void
    {
        match (true) {
            /* Build image from the path. */
            is_string($this->imageConfig) => $this->initPath($this->imageConfig),

            /* Build image from config. */
            $this->imageConfig instanceof Json => $this->initViaConfig($this->imageConfig),
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

    /**
     * Gets the path to the image.
     *
     * @return File|null
     */
    public function getPath(): ?File
    {
        return $this->path;
    }

    /**
     * Sets the path to the image.
     *
     * @param File|null $path
     * @return $this
     */
    public function setPath(?File $path): self
    {
        $this->path = $path;

        return $this;
    }
}

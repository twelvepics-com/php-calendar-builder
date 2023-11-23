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

use App\Objects\Parameter\Source;
use Exception;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpSizeByte\SizeByte;
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
     * @param string $pathAbsolute
     * @return void
     */
    private function setImageString(string $pathAbsolute): void
    {
        $imageString = file_get_contents($pathAbsolute);

        if ($imageString === false) {
            throw new LogicException(sprintf('Could not read image "%s".', $pathAbsolute));
        }

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
     * Initializes the image holder (according to the image path).
     *
     * @param string $imageConfig
     * @return void
     */
    private function initPath(string $imageConfig): void
    {
        $pathRelative = sprintf('%s/%s/%s', self::PATH_CALENDAR, $this->identifier, $imageConfig);

        $pathAbsolute = sprintf('%s/%s', $this->appKernel->getProjectDir(), $pathRelative);

        if (!file_exists($pathAbsolute)) {
            throw new LogicException(sprintf('Image "%s" not found.', $pathAbsolute));
        }

        if (!$this->isImage($pathAbsolute)) {
            throw new LogicException(sprintf('Image "%s" is not an image.', $pathAbsolute));
        }

        $this->setImageString($pathAbsolute);
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
            is_string($this->imageConfig) => $this->initPath($this->imageConfig),
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
     * @return string
     * @throws Exception
     */
    public function getSizeHuman(): string
    {
        return (new SizeByte($this->getSizeByte()))->getHumanReadable();
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}

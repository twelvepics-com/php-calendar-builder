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

namespace App\Calendar\Design\GdImage\Base;

use App\Calendar\Design\Base\DesignBase;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\Image;
use Exception;
use GdImage;
use JetBrains\PhpStorm\ArrayShape;
use LogicException;

/**
 * Abstract class GdImageBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class GdImageBase extends DesignBase
{
    /**
     * Returns image properties from given image.
     *
     * @inheritdoc
     */
    protected function getImageProperties(string $pathAbsolute): Image
    {
        /* Check created image */
        if (!file_exists($pathAbsolute)) {
            throw new Exception(sprintf('Missing file "%s" (%s:%d).', $pathAbsolute, __FILE__, __LINE__));
        }

        /* Get image properties */
        $image = getimagesize($pathAbsolute);

        /* Check image properties */
        if ($image === false) {
            throw new Exception(sprintf('Unable to get file information from "%s" (%s:%d).', $pathAbsolute, __FILE__, __LINE__));
        }

        /* Get file size */
        $sizeByte = filesize($pathAbsolute);

        /* Check image properties */
        if ($sizeByte === false) {
            throw new Exception(sprintf('Unable to get file size from "%s" (%s:%d).', $pathAbsolute, __FILE__, __LINE__));
        }

        /* Return the image properties */
        return (new Image($this->appKernel))
            ->setPathAbsolute($pathAbsolute)
            ->setWidth((int)$image[0])
            ->setHeight((int)$image[1])
            ->setMimeType((string)$image['mime'])
            ->setSizeByte($sizeByte);
    }

    /**
     * Returns the dimension of given text, font size and angle.
     *
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    #[ArrayShape(['width' => "int", 'height' => "int"])]
    protected function getDimension(string $text, int $fontSize, int $angle = 0): array
    {
        $boundingBox = imageftbbox($fontSize, $angle, $this->pathFont, $text);

        if ($boundingBox === false) {
            throw new Exception(sprintf('Unable to get bounding box (%s:%d', __FILE__, __LINE__));
        }

        [$leftBottomX, $leftBottomY, $rightBottomX, $rightBottomY, $rightTopX, $rightTopY, $leftTopX, $leftTopY] = $boundingBox;

        return [
            'width' => $rightBottomX - $leftBottomX,
            'height' => $leftBottomY - $rightTopY,
        ];
    }

    /**
     * Creates an empty image.
     *
     * @inheritdoc
     */
    protected function createImage(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new Exception(sprintf('Unable to create image (%s:%d)', __FILE__, __LINE__));
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Creates image from given filename.
     *
     * @inheritdoc
     */
    protected function createImageFromImage(string $filename, string $type = null): GdImage
    {
        if (!file_exists($filename)) {
            throw new Exception(sprintf('Unable to find image "%s" (%s:%d)', $filename, __FILE__, __LINE__));
        }

        if (is_null($type)) {
            $type = pathinfo($filename, PATHINFO_EXTENSION);
        }

        $image = match ($type) {
            CalendarBuilderServiceConstants::IMAGE_JPG, CalendarBuilderServiceConstants::IMAGE_JPEG => imagecreatefromjpeg($filename),
            CalendarBuilderServiceConstants::IMAGE_PNG => imagecreatefrompng($filename),
            default => throw new Exception(sprintf('Unknown given image type "%s" (%s:%d)', $type, __FILE__, __LINE__)),
        };

        if ($image === false) {
            throw new Exception(sprintf('Unable to create image (%s:%d)', __FILE__, __LINE__));
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Create color from given red, green, blue and alpha value.
     *
     * @inheritdoc
     */
    protected function createColor(int $red, int $green, int $blue, ?int $alpha = null): int
    {
        $color = match(true) {
            $alpha === null => imagecolorallocate($this->getImageTarget(), $red, $green, $blue),
            default => imagecolorallocatealpha($this->getImageTarget(), $red, $green, $blue, $alpha),
        };

        if ($color === false) {
            throw new Exception(sprintf('Unable to create color (%s:%d)', __FILE__, __LINE__));
        }

        return $color;
    }

    /**
     * Creates color from given config.
     *
     * @inheritdoc
     */
    protected function createColorFromConfig(string $key): int
    {
        $color = null;

        if (!is_null($this->config) && $this->config->hasKey($key)) {
            $color = $this->config->getKeyArray($key);
        }

        if (is_null($color)) {
            $color = self::DEFAULT_COLOR;
        }

        if (count($color) < self::EXPECTED_COLOR_VALUES) {
            $color = self::DEFAULT_COLOR;
        }

        $red = $color[0];
        $green = $color[1];
        $blue = $color[2];

        if (!is_int($red) || !is_int($green) || !is_int($blue)) {
            throw new LogicException('Invalid color value given.');
        }

        return $this->createColor($red, $green, $blue);
    }

    /**
     * Add text.
     *
     * @inheritdoc
     */
    #[ArrayShape(['width' => "int", 'height' => "int"])]
    protected function addText(string $text, int $fontSize, int $color = null, int $paddingTop = 0, int $align = CalendarBuilderServiceConstants::ALIGN_LEFT, int $valign = CalendarBuilderServiceConstants::VALIGN_BOTTOM, int $angle = 0): array
    {
        if ($color === null) {
            $color = $this->colors['white'];
        }

        $dimension = $this->getDimension($text, $fontSize, $angle);

        $positionX = match ($align) {
            CalendarBuilderServiceConstants::ALIGN_CENTER => $this->positionX - intval(round($dimension['width'] / 2)),
            CalendarBuilderServiceConstants::ALIGN_RIGHT => $this->positionX - $dimension['width'],
            default => $this->positionX,
        };

        $positionY = match ($valign) {
            CalendarBuilderServiceConstants::VALIGN_TOP => $this->positionY + $fontSize,
            default => $this->positionY,
        };

        imagettftext($this->getImageTarget(), $fontSize, $angle, $positionX, $positionY + $paddingTop, $color, $this->pathFont, $text);

        return [
            'width' => $dimension['width'],
            'height' => $fontSize,
        ];
    }

    /**
     * Add image.
     *
     * @inheritdoc
     */
    protected function addImage(): void
    {
        imagecopyresampled($this->getImageTarget(), $this->getImageSource(), 0, 0, 0, 0, $this->widthTarget, $this->heightTarget, $this->widthSource, $this->heightSource);
    }

    /**
     * Writes target image.
     *
     * @inheritdoc
     */
    protected function writeImage(): void
    {
        $extension = pathinfo($this->pathTargetAbsolute, PATHINFO_EXTENSION);

        if (!is_string($extension)) {
            throw new LogicException('Unable to get extension of file.');
        }

        match ($extension) {
            CalendarBuilderServiceConstants::IMAGE_JPG, CalendarBuilderServiceConstants::IMAGE_JPEG => imagejpeg($this->getImageTarget(), $this->pathTargetAbsolute, $this->calendarBuilderService->getParameterTarget()->getQuality()),
            CalendarBuilderServiceConstants::IMAGE_PNG => imagepng($this->getImageTarget(), $this->pathTargetAbsolute),
            default => throw new LogicException(sprintf('Unsupported given image extension "%s"', $extension)),
        };
    }

    /**
     * Destroys all images.
     *
     * @inheritdoc
     */
    protected function destroyImages(): void
    {
        /* Destroy image */
        imagedestroy($this->getImageTarget());
        imagedestroy($this->getImageSource());
    }

    /**
     * Returns the target image object.
     *
     * @return GdImage
     */
    protected function getImageTarget(): GdImage
    {
        if (!$this->imageTarget instanceof GdImage) {
            throw new LogicException('$this->imageTarget must be an instance of GdImage');
        }

        return $this->imageTarget;
    }

    /**
     * Returns the source image object.
     *
     * @return GdImage
     */
    protected function getImageSource(): GdImage
    {
        if (!$this->imageSource instanceof GdImage) {
            throw new LogicException('$this->imageSource must be an instance of GdImage');
        }

        return $this->imageSource;
    }
}

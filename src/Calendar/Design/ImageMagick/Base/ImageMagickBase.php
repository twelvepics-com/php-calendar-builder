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

namespace App\Calendar\Design\ImageMagick\Base;

use App\Calendar\Design\Base\DesignBase;
use App\Constants\Color;
use App\Objects\Image\Image;
use Exception;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use JetBrains\PhpStorm\ArrayShape;
use LogicException;

/**
 * Abstract class ImageMagickBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
abstract class ImageMagickBase extends DesignBase
{
    private const GD_IMAGE_TO_IMAGICK_CORRECTION = 1 + 1/3;

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
        $image = new Imagick($pathAbsolute);

        /* Return the image properties */
        return (new Image($this->appKernel))
            ->setPathAbsolute($pathAbsolute)
            ->setWidth($image->getImageWidth())
            ->setHeight($image->getImageHeight())
            ->setMimeType($image->getImageMimeType())
            ->setSizeByte($image->getImageLength());
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
        /* Create a new Imagick object. */
        $imagick = new Imagick();

        /* Create the font layer. */
        $draw = new ImagickDraw();
        $draw->setFont($this->pathFont);
        $draw->setFontSize($fontSize * self::GD_IMAGE_TO_IMAGICK_CORRECTION);

        /* Gets the font metrics. */
        $fontMetrics = $imagick->queryFontMetrics($draw, $text);

        $width = (int) round($fontMetrics['textWidth']);
        $height = (int) round($fontMetrics['textHeight'] / 1.9);

        /* Rotate the font layer on a temporary image. */
        $imagick->newImage($width, $height, new ImagickPixel(Color::TRANSPARENT));
        $imagick->annotateImage($draw, 0, 0, 0, $text);
        $imagick->rotateImage(new ImagickPixel(Color::TRANSPARENT), $angle);

        return [
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight(),
        ];
    }

    /**
     * Creates an empty image.
     *
     * @inheritdoc
     */
    protected function createImage(int $width, int $height): Imagick
    {
        $image = new Imagick();

        $image->newImage($width, $height, new ImagickPixel('rgba(0, 255, 0, 1)'));
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

        return $image;
    }

    /**
     * Creates image from given filename.
     *
     * @inheritdoc
     * @SupressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function createImageFromImage(string $filename, string $type = null): Imagick
    {
        if (!file_exists($filename)) {
            throw new Exception(sprintf('Unable to find image "%s" (%s:%d)', $filename, __FILE__, __LINE__));
        }

        $image = new Imagick($filename);
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

        return $image;
    }

    /**
     * Create color from given red, green, blue and alpha value.
     *
     * @param int|null $alpha 100 - 100% visible, 0 - 0% visible
     *
     * @inheritdoc
     */
    protected function createColor(string $keyColor, int $red, int $green, int $blue, ?int $alpha = null): void
    {
        $this->colors[$keyColor] = match (!is_null($alpha)) {
            true => sprintf('rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha / 100),
            false => sprintf('rgb(%d, %d, %d)', $red, $green, $blue),
        };
    }

    /**
     * Returns the color from the given key.
     *
     * @param string $keyColor
     * @return string
     */
    protected function getColor(string $keyColor): string
    {
        if (!array_key_exists($keyColor, $this->colors)) {
            throw new LogicException(sprintf('Color "%s" is not defined.', $keyColor));
        }

        $color = $this->colors[$keyColor];

        if (!is_string($color)) {
            throw new LogicException(sprintf('Color "%s" is not an integer.', $keyColor));
        }

        return $color;
    }

    /**
     * Returns the angle depending on the given value.
     *
     * @inheritdoc
     */
    protected function getAngle(int $angle): int
    {
        return 360 - $angle;
    }

    /**
     * Add raw text.
     *
     * @param string $text
     * @param int $fontSize
     * @param string $keyColor
     * @param int $positionX
     * @param int $positionY
     * @param int $angle
     * @return void
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    protected function addTextRaw(
        string $text,
        int $fontSize,
        string $keyColor,
        int $positionX,
        int $positionY,
        int $angle = 0
    ): void
    {
        /* Create an ImagickDraw object. */
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->getColor($keyColor)));
        $draw->setFont($this->pathFont);
        $draw->setFontSize($fontSize * self::GD_IMAGE_TO_IMAGICK_CORRECTION);

        /* Add text to image. */
        $this->getImageTarget()->annotateImage($draw, $positionX, $positionY, $angle, $text);
    }

    /**
     * Draws a line.
     *
     * @inheritdoc
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws ImagickException
     */
    protected function drawLine(int $x1, int $y1, int $x2, int $y2, string $keyColor): void
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($this->getColor($keyColor)));
        $draw->line($x1, $y1, $x2, $y2);

        $this->getImageTarget()->drawImage($draw);
    }

    /**
     * Add image.
     *
     * @inheritdoc
     * @throws ImagickException
     */
    protected function addImage(): void
    {
        $source = clone $this->getImageSource();

        $source->scaleImage($this->getImageTarget()->getImageWidth(), $this->getImageTarget()->getImageHeight());

        $this->getImageTarget()->compositeImage(
            $source,
            Imagick::COMPOSITE_OVER,
            0,
            0
        );
    }

    /**
     * Writes target image.
     *
     * @inheritdoc
     * @throws ImagickException
     */
    protected function writeImage(): void
    {
        $this->getImageTarget()->writeImage($this->pathTargetAbsolute);
    }

    /**
     * Destroys all images.
     *
     * @inheritdoc
     */
    protected function destroyImages(): void
    {
        /* Destroy image */
        $this->getImageTarget()->destroy();
        $this->getImageSource()->destroy();
    }

    /**
     * Returns the target image object.
     *
     * @return Imagick
     */
    protected function getImageTarget(): Imagick
    {
        if (!$this->imageTarget instanceof Imagick) {
            throw new LogicException('$this->imageTarget must be an instance of Imagick');
        }

        return $this->imageTarget;
    }

    /**
     * Returns the source image object.
     *
     * @return Imagick
     */
    protected function getImageSource(): Imagick
    {
        if (!$this->imageSource instanceof Imagick) {
            throw new LogicException('$this->imageSource must be an instance of Imagick');
        }

        return $this->imageSource;
    }
}

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

namespace App\Calendar\ImageBuilder;

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Constants\Color;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
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
 * Class ImageMagickBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
class ImageMagickImageBuilder extends BaseImageBuilder
{
    private const GD_IMAGE_TO_IMAGICK_CORRECTION = 1 + 1/3;

    /**
     * Returns image properties from given image.
     *
     * @inheritdoc
     */
    protected function getImagePropertiesFromPath(string|null $pathAbsolute): Image
    {
        if (is_null($pathAbsolute)) {
            throw new LogicException('Invalid image path given.');
        }

        /* Check created image */
        if (!file_exists($pathAbsolute)) {
            throw new Exception(sprintf('Missing file "%s" (%s:%d).', $pathAbsolute, __FILE__, __LINE__));
        }

        /* Get image properties */
        print 'start'.PHP_EOL;
        $image = new Imagick($pathAbsolute);
        print 'finish'.PHP_EOL;

        /* Return the image properties */
        $image2 = new Image($this->appKernel);
        $image2->setPathAbsolute($pathAbsolute);
        print '$pathAbsolute'.PHP_EOL;
        $image2->setWidth($image->getImageWidth());
        print 'getImageWidth'.PHP_EOL;
        $image2->setHeight($image->getImageHeight());
        print 'getImageHeight'.PHP_EOL;
        $image2->setMimeType($image->getImageMimeType());
        print 'getImageMimeType'.PHP_EOL;
        $image2->setSizeByte($image->getImageLength());
        print 'getImageLength'.PHP_EOL;
        $image2->setImageString($image->getImagesBlob());
        print 'getImagesBlob'.PHP_EOL;

        return $image2;

//        return (new Image($this->appKernel))
//            ->setPathAbsolute($pathAbsolute)
//            ->setWidth($image->getImageWidth())
//            ->setHeight($image->getImageHeight())
//            ->setMimeType($image->getImageMimeType())
//            ->setSizeByte($image->getImageLength())
//            ->setImageString($image->getImagesBlob());
    }

    /**
     * Returns image properties from given image.
     *
     * @inheritdoc
     */
    protected function getImagePropertiesFromImageString(string $imageString, string|null $pathAbsolute = null): Image
    {
        $imageInformation = getimagesizefromstring($imageString);

        if ($imageInformation === false) {
            throw new LogicException(sprintf('Could not read image "%s".', $this->imageString));
        }

        $width = $imageInformation[0];
        $height = $imageInformation[1];
        $mimeType = $imageInformation['mime'];

        /* Return the image properties */
        return (new Image($this->appKernel))
            ->setPathAbsolute($pathAbsolute)
            ->setWidth($width)
            ->setHeight($height)
            ->setMimeType($mimeType)
            ->setSizeByte(strlen($imageString))
            ->setImageString($imageString)
        ;
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
     */
    protected function createImageFromImage(string|null $filename, string $format = null): Imagick
    {
        if (is_null($format)) {
            throw new LogicException('Invalid image format given.');
        }

        if (!is_null($filename) && !file_exists($filename)) {
            throw new Exception(sprintf('Unable to find image "%s" (%s:%d)', $filename, __FILE__, __LINE__));
        }

        $image = new Imagick($filename);

        if (is_null($filename)) {
            $image->newImage($this->widthTarget, $this->heightTarget, new ImagickPixel('rgba(0, 255, 0, 1)'), $format);
        }

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
    public function createColor(string $keyColor, int $red, int $green, int $blue, ?int $alpha = null): void
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
    public function getColor(string $keyColor): string
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
    public function getAngle(int $angle): int
    {
        return 360 - $angle;
    }

    /**
     * Add raw text.
     *
     * @inheritdoc
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws Exception
     */
    public function addTextRaw(
        string $text,
        int $fontSize,
        string $keyColor,
        int $positionX,
        int $positionY,
        int $angle = 0,
        int $align = CalendarBuilderServiceConstants::ALIGN_LEFT,
        int $valign = CalendarBuilderServiceConstants::VALIGN_BOTTOM
    ): void
    {
        $text = str_replace('<br>', PHP_EOL, $text);

        $dimension = $this->getDimension($text, $fontSize, $angle);

        $positionXAlignment = match ($align) {
            CalendarBuilderServiceConstants::ALIGN_CENTER => Imagick::ALIGN_CENTER,
            CalendarBuilderServiceConstants::ALIGN_RIGHT => Imagick::ALIGN_RIGHT,
            default => Imagick::ALIGN_LEFT,
        };

        $positionY = match ($valign) {
            CalendarBuilderServiceConstants::VALIGN_TOP => $positionY + $dimension['height'],
            CalendarBuilderServiceConstants::VALIGN_MIDDLE => $positionY - intval(round($dimension['height'] / 2)),
            default => $positionY,
        };

        $positionX = max($positionX, 0);
        $positionY = max($positionY, 0);

        /* Create an ImagickDraw object. */
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->getColor($keyColor)));
        $draw->setFont($this->pathFont);
        $draw->setFontSize($fontSize * self::GD_IMAGE_TO_IMAGICK_CORRECTION);
        $draw->setTextAlignment($positionXAlignment);
        $draw->setTextAntialias(true);

        /* Add text to image. */
        $this->getImageTarget()->annotateImage($draw, $positionX, $positionY, $angle, $text);
    }

    /**
     * Gets corrected value.
     *
     * @param float $value
     * @return float
     */
    public function getCorrectedValue(float $value): float
    {
        return $value * self::GD_IMAGE_TO_IMAGICK_CORRECTION;
    }

    /**
     * Draws a line.
     *
     * @inheritdoc
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws ImagickException
     */
    public function drawLine(int $xPosition1, int $yPosition1, int $xPosition2, int $yPosition2, string $keyColor): void
    {
        $draw = new ImagickDraw();
        $draw->setStrokeColor(new ImagickPixel($this->getColor($keyColor)));
        $draw->line($xPosition1, $yPosition1, $xPosition2, $yPosition2);

        $this->getImageTarget()->drawImage($draw);
    }

    /**
     * Add bottom calendar box.
     *
     * @inheritdoc
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws ImagickException
     */
    public function addRectangle(int $xPosition, int $yPosition, int $width, int $height, string $keyColor): void
    {
        /* Add fullscreen rectangle to image. */
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->getColor($keyColor)));
        $draw->rectangle($xPosition, $yPosition, $width, $height);
        $this->getImageTarget()->drawImage($draw);
    }

    /**
     * Add image.
     *
     * @inheritdoc
     * @throws ImagickException
     */
    public function addImage(int $xPosition, int $yPosition, int $width, int $height): void
    {
        $source = clone $this->getImageSource();

        $source->scaleImage($width, $height);

        $this->getImageTarget()->compositeImage(
            $source,
            Imagick::COMPOSITE_OVER,
            $xPosition,
            $yPosition
        );
    }

    /**
     * Add image from blob.
     *
     * @inheritdoc
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws Exception
     */
    public function addImageBlob(string $blob, int $xPosition, int $yPosition, int $width, int $height, array $backgroundColor): void
    {
        /* Set background color */
        $backgroundColor = sprintf('rgb(%d, %d, %d)', $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);

        /* Create Imagick from blob */
        $imageQrCode = new Imagick();
        $result = $imageQrCode->readImageBlob($blob);

        /* Check creating image. */
        if ($result === false) {
            throw new Exception('An error occurred while creating Imagick from blob');
        }

        $transparentColor = new ImagickPixel($backgroundColor);

        $imageQrCode->transparentPaintImage($transparentColor, 0, 0, false);
        $imageQrCode->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);

        $this->getImageTarget()->compositeImage(
            $imageQrCode,
            Imagick::COMPOSITE_DEFAULT,
            $xPosition,
            $yPosition
        );
        $this->getImageTarget()->setImageBackgroundColor($backgroundColor);
        $this->getImageTarget()->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);

        /* Destroy image. */
        $imageQrCode->destroy();
    }

    /**
     * Gets the target image as string.
     *
     * @inheritdoc
     * @throws ImagickException
     */
    public function getImageString(): string
    {
        $image = $this->getImageTarget();

        switch ($this->format) {
            case CalendarBuilderServiceConstants::IMAGE_JPG:
            case CalendarBuilderServiceConstants::IMAGE_JPEG:
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($this->calendarBuilderService->getParameterTarget()->getOutputQuality());
                $image->setImageFormat('JPEG');
                $image->setFormat('JPEG');
                break;

            case CalendarBuilderServiceConstants::IMAGE_PNG:
                $image->setImageFormat('PNG');
                $image->setFormat('PNG');
                break;

            default:
                throw new LogicException(sprintf('Unsupported given image extension "%s"', $this->format));
        }

        return $image->getImagesBlob();
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
    public function getImageTarget(): Imagick
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
    public function getImageSource(): Imagick
    {
        if (!$this->imageSource instanceof Imagick) {
            throw new LogicException('$this->imageSource must be an instance of Imagick');
        }

        return $this->imageSource;
    }
}

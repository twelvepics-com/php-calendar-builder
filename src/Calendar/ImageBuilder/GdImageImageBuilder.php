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
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\Image;
use Exception;
use GdImage;
use JetBrains\PhpStorm\ArrayShape;
use LogicException;

/**
 * Class GdImageBuilder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class GdImageImageBuilder extends BaseImageBuilder
{
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
     * Returns image properties from given image.
     *
     * @inheritdoc
     */
    protected function getImagePropertiesFromImageString(string $imageString, string|null $pathAbsolute = null): Image
    {
        throw new LogicException('Not implemented yet.');
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
            'width' => (int) abs($rightTopX - $leftBottomX),
            'height' => (int) abs($leftBottomY - $rightTopY),
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
    protected function createImageFromImage(string|null $filename, string $format = null): GdImage
    {
        if (!is_null($filename) && !file_exists($filename)) {
            throw new Exception(sprintf('Unable to find image "%s" (%s:%d)', $filename, __FILE__, __LINE__));
        }

        if (is_null($format)) {
            $format = pathinfo($filename, PATHINFO_EXTENSION);
        }

        $image = match ($format) {
            CalendarBuilderServiceConstants::IMAGE_JPG, CalendarBuilderServiceConstants::IMAGE_JPEG => imagecreatefromjpeg($filename),
            CalendarBuilderServiceConstants::IMAGE_PNG => imagecreatefrompng($filename),
            default => throw new Exception(sprintf('Unknown given image type "%s" (%s:%d)', $format, __FILE__, __LINE__)),
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
     * @param int|null $alpha 100 - 100% visible, 0 - 0% visible
     *
     * @inheritdoc
     */
    public function createColor(string $keyColor, int $red, int $green, int $blue, ?int $alpha = null): void
    {
        $color = match(true) {
            $alpha === null => imagecolorallocate($this->getImageTarget(), $red, $green, $blue),
            default => imagecolorallocatealpha($this->getImageTarget(), $red, $green, $blue, 100 - $alpha),
        };

        if ($color === false) {
            throw new Exception(sprintf('Unable to create color (%s:%d)', __FILE__, __LINE__));
        }

        $this->colors[$keyColor] = $color;
    }

    /**
     * Returns the color from the given key.
     *
     * @param string $keyColor
     * @return int
     */
    public function getColor(string $keyColor): int
    {
        if (!array_key_exists($keyColor, $this->colors)) {
            throw new LogicException(sprintf('Color "%s" is not defined.', $keyColor));
        }

        $color = $this->colors[$keyColor];

        if (!is_int($color)) {
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
        return $angle;
    }

    /**
     * Add raw text.
     *
     * @inheritdoc
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

        $positionX = match ($align) {
            CalendarBuilderServiceConstants::ALIGN_CENTER => $positionX - intval(round($dimension['width'] / 2)),
            CalendarBuilderServiceConstants::ALIGN_RIGHT => $positionX - $dimension['width'],
            default => $positionX,
        };

        $positionY = match ($valign) {
            CalendarBuilderServiceConstants::VALIGN_TOP => $positionY + $fontSize,
            default => $positionY,
        };

        $positionX = max($positionX, 0);
        $positionY = max($positionY, 0);

        imagettftext($this->getImageTarget(), $fontSize, $angle, $positionX, $positionY, $this->getColor($keyColor), $this->pathFont, $text);
    }

    /**
     * Gets corrected value.
     *
     * @param float $value
     * @return float
     */
    public function getCorrectedValue(float $value): float
    {
        return $value;
    }

    /**
     * Draws a line.
     *
     * @inheritdoc
     */
    public function drawLine(int $xPosition1, int $yPosition1, int $xPosition2, int $yPosition2, string $keyColor): void
    {
        imageline($this->getImageTarget(), $xPosition1, $yPosition1, $xPosition2, $yPosition2, $this->getColor($keyColor));
    }

    /**
     * Add bottom calendar box.
     *
     * @inheritdoc
     */
    public function addRectangle(int $xPosition, int $yPosition, int $width, int $height, string $keyColor): void
    {
        /* Add calendar area (rectangle) */
        imagefilledrectangle($this->getImageTarget(), $xPosition, $yPosition, $width, $height, $this->getColor($keyColor));
    }

    /**
     * Add image.
     *
     * @param int $xPosition
     * @param int $yPosition
     * @param int $width
     * @param int $height
     * @inheritdoc
     */
    public function addImage(int $xPosition, int $yPosition, int $width, int $height): void
    {
        imagecopyresampled(
            $this->getImageTarget(),
            $this->getImageSource(),
            $xPosition, $yPosition,
            0, 0,
            $width, $height,
            $this->widthSource, $this->heightSource
        );
    }

    /**
     * Add image from blob.
     *
     * @inheritdoc
     * @throws Exception
     */
    public function addImageBlob(string $blob, int $xPosition, int $yPosition, int $width, int $height, array $backgroundColor): void
    {
        /* Create GDImage from blob */
        $imageQrCode = imagecreatefromstring($blob);

        /* Check creating image. */
        if ($imageQrCode === false) {
            throw new Exception(sprintf('An error occurred while creating GDImage from blob (%s:%d)', __FILE__, __LINE__));
        }

        /* Get height from $imageQrCode */
        $widthQrCode  = imagesx($imageQrCode);
        $heightQrCode = imagesy($imageQrCode);

        /* Create transparent color */
        $transparentColor = imagecolorexact($imageQrCode, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);

        /* Set background color to transparent */
        imagecolortransparent($imageQrCode, $transparentColor);

        /* Add a dynamically generated qr image to the main image */
        imagecopyresized(
            $this->getImageTarget(),
            $imageQrCode,
            $xPosition,
            $yPosition,
            0,
            0,
            $width,
            $height,
            $widthQrCode,
            $heightQrCode
        );

        /* Destroy image. */
        imagedestroy($imageQrCode);
    }

    /**
     * Gets the target image as string.
     *
     * @inheritdoc
     */
    public function getImageString(): string
    {
        $extension = pathinfo($this->pathTargetAbsolute, PATHINFO_EXTENSION);

        if (!is_string($extension)) {
            throw new LogicException('Unable to get extension of file.');
        }

        ob_start();
        match ($extension) {
            CalendarBuilderServiceConstants::IMAGE_JPG, CalendarBuilderServiceConstants::IMAGE_JPEG => imagejpeg($this->getImageTarget(), null, $this->calendarBuilderService->getParameterTarget()->getOutputQuality()),
            CalendarBuilderServiceConstants::IMAGE_PNG => imagepng($this->getImageTarget()),
            default => throw new LogicException(sprintf('Unsupported given image extension "%s"', $extension)),
        };
        $imageString = ob_get_clean();

        if ($imageString === false) {
            throw new LogicException('Unable to create image content.');
        }

        return $imageString;
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
    public function getImageTarget(): GdImage
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
    public function getImageSource(): GdImage
    {
        if (!$this->imageSource instanceof GdImage) {
            throw new LogicException('$this->imageSource must be an instance of GdImage');
        }

        return $this->imageSource;
    }
}

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

use App\Constants\Parameter\Option;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\Image;
use App\Objects\Image\ImageContainer;
use App\Service\CalendarBuilderService;
use Exception;
use GdImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract class DesignBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class DesignBase
{
    /**
     * Constants.
     */
    protected const ASPECT_RATIO = 3 / 2;


    /**
     * Calculated values (by zoom).
     */
    protected float $zoom = 1.0;



    /**
     * Class cached values.
     */
    protected string $pathSourceAbsolute;

    protected string $pathTargetAbsolute;


    /**
     * Internal properties.
     */
    protected CalendarBuilderService $calendarBuilderService;

    protected GdImage $imageTarget;

    protected GdImage $imageSource;

    protected int $width;

    protected int $height;

    protected int $widthSource;

    protected int $heightSource;

    protected int $positionX;

    protected int $positionY;



    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(protected KernelInterface $appKernel)
    {
    }

    /**
     * Init function.
     *
     * @param CalendarBuilderService $calendarBuilderService
     * @param int $qrCodeVersion
     * @param bool $useCalendarImagePath
     * @param bool $deleteTargetImages
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
     public function init(
        CalendarBuilderService $calendarBuilderService,
        int $qrCodeVersion = CalendarBuilderServiceConstants::DEFAULT_QR_CODE_VERSION,
        bool $useCalendarImagePath = false,
        bool $deleteTargetImages = false
     ): void
     {
         $this->calendarBuilderService = $calendarBuilderService;

         /* sizes */
         $this->height = CalendarBuilderServiceConstants::ZOOM_HEIGHT_100;
         $this->width = intval(floor($this->height * self::ASPECT_RATIO));

         /* Calculate zoom */
         $this->zoom = $this->height / CalendarBuilderServiceConstants::ZOOM_HEIGHT_100;
     }

    /**
     * Builds the given source image to a calendar page.
     *
     * @return ImageContainer
     * @throws Exception
     */
    abstract public function build(): ImageContainer;

    /**
     * Init x and y.
     *
     * @param int $positionX
     * @param int $positionY
     */
    protected function initXY(int $positionX = 0, int $positionY = 0): void
    {
        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }

    /**
     * Set x position.
     *
     * @param int $positionX
     */
    protected function setPositionX(int $positionX): void
    {
        $this->positionX = $positionX;
    }

    /**
     * Set y position.
     *
     * @param int $positionY
     */
    protected function setPositionY(int $positionY): void
    {
        $this->positionY = $positionY;
    }

    /**
     * Add x position.
     *
     * @param int $positionX
     */
    protected function addX(int $positionX): void
    {
        $this->positionX += $positionX;
    }

    /**
     * Add y position.
     *
     * @param int $positionY
     */
    protected function addY(int $positionY): void
    {
        $this->positionY += $positionY;
    }

    /**
     * Returns the size depending on the zoom.
     *
     * @param int $size
     * @return int
     */
    protected function getSize(int $size): int
    {
        return intval(round($size * $this->zoom));
    }

    /**
     * Returns image properties from given image.
     *
     * @param string $pathAbsolute
     * @return Image
     * @throws Exception
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
            ->setWidth((int) $image[0])
            ->setHeight((int) $image[1])
            ->setMimeType((string) $image['mime'])
            ->setSizeByte($sizeByte)
            ;
    }

    /**
     * Returns the target path from given source.
     *
     * @param File $sourceImage
     * @return string
     * @throws FileNotFoundException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getTargetPathFromSource(File $sourceImage): string
    {
        $target = $this->calendarBuilderService->getParameterTarget();
        $source = $this->calendarBuilderService->getParameterSource();

        $extension = pathinfo($sourceImage->getPathReal(), PATHINFO_EXTENSION);

        $targetPath = $source->getOptionFromConfig(Option::TARGET);

        if (is_null($targetPath)) {
            $targetPath = sprintf('%s-%s.%s', $target->getYear(), $target->getMonth(), $extension);
        }

        return sprintf('%s/%s', $sourceImage->getDirectoryPath(), $targetPath);
    }

    /**
     * Creates an empty image.
     *
     * @param int $width
     * @param int $height
     * @return GdImage
     * @throws Exception
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
     * @param string $filename
     * @param string|null $type
     * @return GdImage
     * @throws Exception
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
            CalendarBuilderServiceConstants::IMAGE_JPG => imagecreatefromjpeg($filename),
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
     * Creates the GdImage instances.
     * @throws Exception
     */
    protected function createImages(): void
    {
        $this->imageTarget = $this->createImage($this->width, $this->height);
        $this->imageSource = $this->createImageFromImage($this->pathSourceAbsolute);
    }

    /**
     * Writes target image.
     */
    protected function writeImage(): void
    {
        /* Write image */
        imagejpeg($this->imageTarget, $this->pathTargetAbsolute, $this->calendarBuilderService->getParameterTarget()->getQuality());
    }

    /**
     * Destroys all images.
     */
    protected function destroy(): void
    {
        /* Destroy image */
        imagedestroy($this->imageTarget);
        imagedestroy($this->imageSource);
    }
}

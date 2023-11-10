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

use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\Image;
use App\Objects\Image\ImageContainer;
use App\Service\CalendarBuilderService;
use Exception;
use GdImage;
use Ixnode\PhpContainer\File;
use LogicException;
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
    protected float $zoomTarget = 1.0;


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

    protected int $widthTarget;

    protected int $heightTarget;

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
     * Additional init tasks.
     */
    abstract public function doInit(): void;

    /**
     * Additional build tasks.
     *
     * @throws Exception
     */
    abstract public function doBuild(): void;

    /**
     * Init function.
     *
     * @param CalendarBuilderService $calendarBuilderService
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function init(
        CalendarBuilderService $calendarBuilderService
    ): void
    {
        /* Sets calendar builder service. */
        $this->calendarBuilderService = $calendarBuilderService;

        /* Sets image paths */
        $this->setSourcePath();
        $this->setTargetPath();

        /* Calculate all dimensions. */
        $this->setSourceDimensions();
        $this->setTargetDimensions();
        $this->setTargetZoom();

        /* Do some customs initializations. */
        $this->doInit();
    }

    /**
     * Builds the given source image to a calendar page.
     *
     * @return ImageContainer
     * @throws Exception
     */
    public function build(): ImageContainer
    {
        /* Creates the source and target GDImages */
        $this->createImages();

        /* Do the custom-builds */
        $this->doBuild();

        /* Write image */
        $this->writeImage();

        /* Destroy image */
        $this->destroy();

        /* Returns the properties of the created and source image */
        return (new ImageContainer())
            ->setSource($this->getImageProperties($this->pathSourceAbsolute))
            ->setTarget($this->getImageProperties($this->pathTargetAbsolute))
        ;
    }

    /**
     * Returns the source image.
     *
     * @return File
     */
    protected function getSourceImage(): File
    {
        return $this->calendarBuilderService->getParameterSource()->getImage();
    }

    /**
     * Returns the target image.
     *
     * @return File
     */
    protected function getTargetImage(): File
    {
        return $this->calendarBuilderService->getParameterTarget()->getImage();
    }

    /**
     * Sets the source image path.
     *
     * @return void
     */
    protected function setSourcePath(): void
    {
        /* Check if the source image exists. */
        if (!$this->getSourceImage()->exist()) {
            throw new LogicException(sprintf('Given source image was not found: "%s"', $this->getSourceImage()->getPath()));
        }

        /* Sets the source and target image paths. */
        $this->pathSourceAbsolute = $this->getSourceImage()->getPath();
    }

    /**
     * Sets the target image path.
     *
     * @return void
     */
    protected function setTargetPath(): void
    {
        $this->pathTargetAbsolute = $this->getTargetImage()->getPath();
    }

    /**
     * Sets the source dimensions.
     *
     * @return void
     */
    protected function setSourceDimensions(): void
    {
        $propertySources = getimagesize($this->pathSourceAbsolute);

        if ($propertySources === false) {
            throw new LogicException(sprintf('Unable to get image size (%s:%d)', __FILE__, __LINE__));
        }

        $this->widthSource = $propertySources[0];
        $this->heightSource = $propertySources[1];
    }

    /**
     * Sets the target dimensions.
     *
     * @return void
     */
    protected function setTargetDimensions(): void
    {
        $this->heightTarget = CalendarBuilderServiceConstants::TARGET_HEIGHT;
        $this->widthTarget = intval(floor($this->heightTarget * self::ASPECT_RATIO));
    }

    /**
     * Sets the zoom of the current image.
     *
     * @return void
     */
    protected function setTargetZoom(): void
    {
        $this->zoomTarget = $this->heightTarget / CalendarBuilderServiceConstants::TARGET_HEIGHT;
    }

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
        return intval(round($size * $this->zoomTarget));
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
            ->setWidth((int)$image[0])
            ->setHeight((int)$image[1])
            ->setMimeType((string)$image['mime'])
            ->setSizeByte($sizeByte);
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
        $this->imageTarget = $this->createImage($this->widthTarget, $this->heightTarget);
        $this->imageSource = $this->createImageFromImage($this->pathSourceAbsolute);
    }

    /**
     * Add image
     */
    protected function addImage(): void
    {
        imagecopyresampled($this->imageTarget, $this->imageSource, 0, 0, 0, 0, $this->widthTarget, $this->heightTarget, $this->widthSource, $this->heightSource);
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

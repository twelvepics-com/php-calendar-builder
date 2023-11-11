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
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
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
    private const FONT = 'OpenSansCondensed-Light.ttf';

    protected const ASPECT_RATIO = 3 / 2;

    private const DEFAULT_COLOR = [47, 141, 171];

    private const EXPECTED_COLOR_VALUES = 3;


    /**
     * Calculated values (by zoom).
     */
    protected float $zoomTarget = 1.0;


    /**
     * Class cached values.
     */
    protected string $pathSourceAbsolute;

    protected string $pathTargetAbsolute;

    protected string $pathFont;

    /** @var int[] $colors */
    protected array $colors;


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
     * @param Json|null $config
     */
    public function __construct(protected KernelInterface $appKernel, protected Json|null $config = null)
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

        /* Font path */
        $this->setFontPath();

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
     * Sets the default font path.
     *
     * @return void
     */
    protected function setFontPath(): void
    {
        $pathData = sprintf('%s/data', $this->appKernel->getProjectDir());
        $this->pathFont = sprintf('%s/font/%s', $pathData, self::FONT);
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
     * Returns the dimension of given text, font size and angle.
     *
     * @param string $text
     * @param int $fontSize
     * @param int $angle
     * @return array{width: int, height: int}
     * @throws Exception
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
     * Creates the GdImage instances.
     * @throws Exception
     */
    protected function createImages(): void
    {
        $this->imageTarget = $this->createImage($this->widthTarget, $this->heightTarget);
        $this->imageSource = $this->createImageFromImage($this->pathSourceAbsolute);
    }

    /**
     * Create color from given red, green, blue and alpha value.
     *
     * @param GdImage $image
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int|null $alpha
     * @return int
     * @throws Exception
     */
    protected function createColor(GdImage $image, int $red, int $green, int $blue, ?int $alpha = null): int
    {
        $color = match(true) {
            $alpha === null => imagecolorallocate($image, $red, $green, $blue),
            default => imagecolorallocatealpha($image, $red, $green, $blue, $alpha),
        };

        if ($color === false) {
            throw new Exception(sprintf('Unable to create color (%s:%d)', __FILE__, __LINE__));
        }

        return $color;
    }

    /**
     * Creates color from given config.
     *
     * @param GdImage $image
     * @param string $key
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     * @throws Exception
     */
    protected function createColorFromConfig(GdImage $image, string $key): int
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

        return $this->createColor($image, $red, $green, $blue);
    }

    /**
     * Add text.
     *
     * @param string $text
     * @param int $fontSize
     * @param ?int $color
     * @param int $paddingTop
     * @param int $align
     * @param int $valign
     * @param int $angle
     * @return array{width: int, height: int}
     * @throws Exception
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

        imagettftext($this->imageTarget, $fontSize, $angle, $positionX, $positionY + $paddingTop, $color, $this->pathFont, $text);

        return [
            'width' => $dimension['width'],
            'height' => $fontSize,
        ];
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
        $extension = pathinfo($this->pathTargetAbsolute, PATHINFO_EXTENSION);

        if (!is_string($extension)) {
            throw new LogicException('Unable to get extension of file.');
        }

        match ($extension) {
            CalendarBuilderServiceConstants::IMAGE_JPG, CalendarBuilderServiceConstants::IMAGE_JPEG => imagejpeg($this->imageTarget, $this->pathTargetAbsolute, $this->calendarBuilderService->getParameterTarget()->getQuality()),
            CalendarBuilderServiceConstants::IMAGE_PNG => imagepng($this->imageTarget, $this->pathTargetAbsolute),
            default => throw new LogicException(sprintf('Unsupported given image extension "%s"', $extension)),
        };
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

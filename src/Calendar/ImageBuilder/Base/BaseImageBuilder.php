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

namespace App\Calendar\ImageBuilder\Base;

use App\Calendar\Design\Base\DesignBase;
use App\Calendar\ImageBuilder\GdImageImageBuilder;
use App\Calendar\ImageBuilder\ImageMagickImageBuilder;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\Image;
use App\Objects\Image\ImageContainer;
use App\Service\CalendarBuilderService;
use Exception;
use GdImage;
use Imagick;
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
 * Abstract class BaseBuilder
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class BaseImageBuilder
{
    /**
     * Constants.
     */
    protected const FONT = 'OpenSansCondensed-Light.ttf';

    protected const ASPECT_RATIO = 3 / 2;

    protected const DEFAULT_COLOR = [47, 141, 171];

    protected const EXPECTED_COLOR_VALUES = 3;


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

    /** @var int[]|string[] $colors */
    protected array $colors = [];


    /**
     * Internal properties.
     */
    protected CalendarBuilderService $calendarBuilderService;

    protected GdImage|Imagick $imageTarget;

    protected GdImage|Imagick $imageSource;

    protected int $widthTarget;

    protected int $heightTarget;

    protected int $widthSource;

    protected int $heightSource;

    protected int $positionX;

    protected int $positionY;


    /**
     * @param KernelInterface $appKernel
     * @param DesignBase $design
     * @param Json|null $config
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function __construct(protected KernelInterface $appKernel, protected DesignBase $design, protected Json|null $config = null)
    {
        $design->setImageBuilder($this);
        $design->setAppKernel($appKernel);

        if (!is_null($config)) {
            $design->setConfig($config);
        }
    }

    /**
     * @return DesignBase
     */
    public function getDesign(): DesignBase
    {
        return $this->design;
    }

    /**
     * Additional init tasks.
     */
    protected function doInit(): void
    {
        $this->design->doInit();
    }

    /**
     * Additional build tasks.
     *
     * @throws Exception
     */
    protected function doBuild(): void
    {
        $this->design->doBuild();
    }

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
        $this->destroyImages();

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
        $correct = match (true) {
            $this instanceof ImageMagickImageBuilder => 1, # + 1/3,
            $this instanceof GdImageImageBuilder => 1.,
            default => throw new LogicException('Class must be either ImageMagickBase or GdImageBase'),
        };

        $this->zoomTarget = $this->heightTarget / CalendarBuilderServiceConstants::TARGET_HEIGHT * $correct;
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
    public function initXY(int $positionX = 0, int $positionY = 0): void
    {
        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }

    /**
     * @return int
     */
    public function getPositionX(): int
    {
        return $this->positionX;
    }

    /**
     * @return int
     */
    public function getPositionY(): int
    {
        return $this->positionY;
    }

    /**
     * Set x position.
     *
     * @param int $positionX
     */
    public function setPositionX(int $positionX): void
    {
        $this->positionX = $positionX;
    }

    /**
     * Set y position.
     *
     * @param int $positionY
     */
    public function setPositionY(int $positionY): void
    {
        $this->positionY = $positionY;
    }

    /**
     * Add x position.
     *
     * @param int $positionX
     */
    public function addX(int $positionX): void
    {
        $this->positionX += $positionX;
    }

    /**
     * Add y position.
     *
     * @param int $positionY
     */
    public function addY(int $positionY): void
    {
        $this->positionY += $positionY;
    }

    /**
     * Remove x position.
     *
     * @param int $positionX
     */
    public function removeX(int $positionX): void
    {
        $this->positionX -= $positionX;
    }

    /**
     * Remove y position.
     *
     * @param int $positionY
     */
    public function removeY(int $positionY): void
    {
        $this->positionY -= $positionY;
    }

    /**
     * Returns the size depending on the zoom.
     *
     * @param int $size
     * @return int
     */
    public function getSize(int $size): int
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
    abstract protected function getImageProperties(string $pathAbsolute): Image;

    /**
     * Returns the dimension of given text, font size and angle.
     *
     * @param string $text
     * @param int $fontSize
     * @param int $angle
     * @return array{width: int, height: int}
     * @throws Exception
     */
    #[ArrayShape(['width' => "int", 'height' => "int"])]
    abstract protected function getDimension(string $text, int $fontSize, int $angle = 0): array;

    /**
     * Creates an empty image.
     *
     * @param int $width
     * @param int $height
     * @return GdImage|Imagick
     * @throws Exception
     */
    abstract protected function createImage(int $width, int $height): GdImage|Imagick;

    /**
     * Creates image from given filename.
     *
     * @param string $filename
     * @param string|null $type
     * @return GdImage|Imagick
     * @throws Exception
     */
    abstract protected function createImageFromImage(string $filename, string $type = null): GdImage|Imagick;

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
     * @param string $keyColor
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int|null $alpha
     * @return void
     * @throws Exception
     */
    abstract public function createColor(string $keyColor, int $red, int $green, int $blue, ?int $alpha = null): void;

    /**
     * Resets the color array.
     *
     * @return void
     */
    public function resetColors(): void
    {
        $this->colors = [];
    }

    /**
     * Returns the color from the given key.
     *
     * @param string $keyColor
     * @return int|string
     */
    abstract public function getColor(string $keyColor): int|string;

    /**
     * Creates color from given config.
     *
     * @param string $keyColor
     * @param string $keyConfig
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws Exception
     */
    public function createColorFromConfig(string $keyColor, string $keyConfig): void
    {
        $color = null;

        if (!is_null($this->config) && $this->config->hasKey($keyConfig)) {
            $color = $this->config->getKeyArray($keyConfig);
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

        $this->createColor($keyColor, $red, $green, $blue);
    }

    /**
     * Returns the angle depending on the given value.
     *
     * @param int $angle
     * @return int
     */
    abstract public function getAngle(int $angle): int;

    /**
     * Add raw text.
     *
     * @param string $text
     * @param int $fontSize
     * @param string $keyColor
     * @param int $positionX
     * @param int $positionY
     * @param int $angle
     * @param int $align
     * @param int $valign
     * @return void
     */
    abstract public function addTextRaw(
        string $text,
        int $fontSize,
        string $keyColor,
        int $positionX,
        int $positionY,
        int $angle = 0,
        int $align = CalendarBuilderServiceConstants::ALIGN_LEFT,
        int $valign = CalendarBuilderServiceConstants::VALIGN_BOTTOM
    ): void;

    /**
     * Gets corrected value.
     *
     * @param float $value
     * @return float
     */
    abstract public function getCorrectedValue(float $value): float;

    /**
     * Add text.
     *
     * @param string $text
     * @param int $fontSize
     * @param ?string $keyColor
     * @param int $paddingTop
     * @param int $align
     * @param int $valign
     * @param int $angle
     * @return array{width: int, height: int}
     * @throws Exception
     */
    #[ArrayShape(['width' => "int", 'height' => "int"])]
    public function addText(
        string $text,
        int $fontSize,
        string $keyColor = null,
        int $paddingTop = 0,
        int $align = CalendarBuilderServiceConstants::ALIGN_LEFT,
        int $valign = CalendarBuilderServiceConstants::VALIGN_BOTTOM,
        int $angle = 0
    ): array
    {
        if ($keyColor === null) {
            $keyColor = 'white';
        }

        $splitText = explode('<br>', $text);

        $text = str_replace('<br>', PHP_EOL, $text);

        $this->addTextRaw(
            $text,
            $fontSize,
            $keyColor,
            $this->positionX,
            $this->positionY + $paddingTop,
            $angle,
            $align,
            $valign
        );

        $dimension = $this->getDimension($text, $fontSize, $angle);

        return [
            'width' => $dimension['width'],
            'height' => count($splitText) > 1 ? intval(round($this->getCorrectedValue($dimension['height']))) : $fontSize,
        ];
    }

    /**
     * Draws a line.
     *
     * @param int $xPosition1
     * @param int $yPosition1
     * @param int $xPosition2
     * @param int $yPosition2
     * @param string $keyColor
     * @return void
     */
    abstract public function drawLine(int $xPosition1, int $yPosition1, int $xPosition2, int $yPosition2, string $keyColor): void;

    /**
     * Add bottom calendar box.
     */
    abstract public function addRectangle(int $xPosition, int $yPosition, int $width, int $height, string $keyColor): void;

    /**
     * Add image.
     *
     * @param int $xPosition
     * @param int $yPosition
     * @param int $width
     * @param int $height
     * @return void
     */
    abstract public function addImage(int $xPosition, int $yPosition, int $width, int $height): void;

    /**
     * Add image from blob.
     *
     * @param string $blob
     * @param int $xPosition
     * @param int $yPosition
     * @param int $width
     * @param int $height
     * @param int[] $backgroundColor
     * @return void
     */
    abstract public function addImageBlob(string $blob, int $xPosition, int $yPosition, int $width, int $height, array $backgroundColor): void;

    /**
     * Writes target image.
     *
     * @return void
     */
    abstract protected function writeImage(): void;

    /**
     * Destroys all images.
     *
     * @return void
     */
    abstract protected function destroyImages(): void;

    /**
     * @return CalendarBuilderService
     */
    public function getCalendarBuilderService(): CalendarBuilderService
    {
        return $this->calendarBuilderService;
    }

    /**
     * @return GdImage|Imagick
     */
    abstract public function getImageTarget(): Imagick|GdImage;

    /**
     * @return GdImage|Imagick
     */
    abstract public function getImageSource(): Imagick|GdImage;

    /**
     * @return int
     */
    public function getWidthTarget(): int
    {
        return $this->widthTarget;
    }

    /**
     * @return int
     */
    public function getHeightTarget(): int
    {
        return $this->heightTarget;
    }

    /**
     * @return int
     */
    public function getWidthSource(): int
    {
        return $this->widthSource;
    }

    /**
     * @return int
     */
    public function getHeightSource(): int
    {
        return $this->heightSource;
    }
}

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

namespace App\Calendar\Design\GdImage;

use App\Calendar\Design\GdImage\Base\DesignBase;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use App\Objects\Image\ImageContainer;
use App\Service\CalendarBuilderService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use GdImage;
use JetBrains\PhpStorm\ArrayShape;
use LogicException;

/**
 * Class DesignDefault
 *
 * Creates the default calendar design.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DesignDefault extends DesignBase
{
    /**
     * Constants.
     */
    private const FONT = 'OpenSansCondensed-Light.ttf';

    private const CALENDAR_BOX_BOTTOM_SIZE = 9/48;

    protected const MAX_LENGTH_EVENT_CAPTION = 28;

    private const MAX_LENGTH_ADD = '...';



    /**
     * Calculated values (by zoom).
     */
    protected int $fontSizeTitle = 60;

    protected int $fontSizePosition = 30;

    protected int $fontSizeYear = 100;

    protected int $fontSizeMonth = 220;

    protected int $fontSizeDay = 60;

    protected int $fontSizeTitlePage = 200;

    protected int $fontSizeTitlePageSubtext = 70;

    protected int $fontSizeTitlePageAuthor = 40;

    protected int $dayDistance = 40;

    protected int $paddingCalendarDays = 160;

    protected int $qrCodeVersion = 5;

    protected int $widthQrCode = 250;

    protected int $heightQrCode = 250;



    /**
     * Class cached values.
     */
    protected string $pathFont;

    protected int $valignImage;

    protected int $yCalendarBoxBottom;

    protected string $url;

    /** @var int[] $colors */
    protected array $colors;

    /** @var array<string, array{x: int, y: int, align: int, dimension: int[], day: int}> $positionDays */
    protected array $positionDays = [];



    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function init(
        CalendarBuilderService $calendarBuilderService,
        int $qrCodeVersion = CalendarBuilderServiceConstants::DEFAULT_QR_CODE_VERSION,
        bool $useCalendarImagePath = false,
        bool $deleteTargetImages = false
    ): void
    {
        parent::init($calendarBuilderService, $qrCodeVersion, $useCalendarImagePath, $deleteTargetImages);

        /* Clear positions */
        $this->positionDays = [];

        /* Set qr code version */
        $this->qrCodeVersion = $qrCodeVersion;

        /* Font path */
        $pathData = sprintf('%s/data', $this->appKernel->getProjectDir());
        $this->pathFont = sprintf('%s/font/%s', $pathData, self::FONT);

        /* Calculate sizes */
        $this->fontSizeTitle = $this->getSize($this->fontSizeTitle);
        $this->fontSizePosition = $this->getSize($this->fontSizePosition);
        $this->fontSizeYear = $this->getSize($this->fontSizeYear);
        $this->fontSizeMonth = $this->getSize($this->fontSizeMonth);
        $this->fontSizeDay = $this->getSize($this->fontSizeDay);
        $this->fontSizeTitlePage = $this->getSize($this->fontSizeTitlePage);
        $this->fontSizeTitlePageSubtext = $this->getSize($this->fontSizeTitlePageSubtext);
        $this->fontSizeTitlePageAuthor = $this->getSize($this->fontSizeTitlePageAuthor);
        $this->paddingCalendarDays = $this->getSize($this->paddingCalendarDays);
        $this->heightQrCode = $this->getSize($this->heightQrCode);
        $this->widthQrCode = $this->getSize($this->widthQrCode);
        $this->dayDistance = $this->getSize($this->dayDistance);
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
     * Prepare method.
     *
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->createImages();
        $this->createColors();
        $this->calculateVariables();
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
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        $target = $this->calendarBuilderService->getParameterTarget();

        $this->colors = [
            'black' => $this->createColor($this->imageTarget, 0, 0, 0),
            'blackTransparency' => $this->createColor($this->imageTarget, 0, 0, 0, $target->getTransparency()),
            'white' => $this->createColor($this->imageTarget, 255, 255, 255),
            'whiteTransparency' => $this->createColor($this->imageTarget, 255, 255, 255, $target->getTransparency()),
            'red' => $this->createColor($this->imageTarget, 255, 0, 0),
            'redTransparency' => $this->createColor($this->imageTarget, 255, 0, 0, $target->getTransparency()),
        ];
    }

    /**
     * Calculate variables.
     *
     * @throws Exception
     */
    protected function calculateVariables(): void
    {
        $propertiesSource = getimagesize($this->pathSourceAbsolute);

        if ($propertiesSource === false) {
            throw new Exception(sprintf('Unable to get image size (%s:%d)', __FILE__, __LINE__));
        }

        $this->widthSource = $propertiesSource[0];
        $this->heightSource = $propertiesSource[1];

        $this->yCalendarBoxBottom = intval(floor($this->height * (1 - self::CALENDAR_BOX_BOTTOM_SIZE)));
    }

    /**
     * Returns the color of given day.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int
     * @throws Exception
     */
    protected function getDayColor(int $year, int $month, int $day): int
    {
        /* Print day in red if sunday */
        if ($this->calendarBuilderService->getDayOfWeek($year, $month, $day) === CalendarBuilderServiceConstants::DAY_SUNDAY) {
            return $this->colors['red'];
        }

        /* Print day in red if holiday */
        $dayKey = $this->calendarBuilderService->getDayKey($day);
        $holidays = $this->calendarBuilderService->getHolidays();
        if (array_key_exists($dayKey, $holidays) && $holidays[$dayKey] === true) {
            return $this->colors['red'];
        }

        /* Print day in white otherwise */
        return $this->colors['white'];
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
        $positionY = match ($this->valignImage) {
            CalendarBuilderServiceConstants::VALIGN_BOTTOM => $this->yCalendarBoxBottom - $this->height,
            default => 0,
        };

        $positionX = 0;

        imagecopyresampled($this->imageTarget, $this->imageSource, $positionX, $positionY, 0, 0, $this->width, $this->height, $this->widthSource, $this->heightSource);
    }

    /**
     * Add bottom calendar box.
     */
    protected function addRectangle(): void
    {
        /* Add calendar area (rectangle) */
        imagefilledrectangle($this->imageTarget, 0, $this->yCalendarBoxBottom, $this->width, $this->height, $this->colors['blackTransparency']);
    }

    /**
     * Add the title and position.
     *
     * @throws Exception
     */
    protected function addImageDescriptionAndPositionOnCalendarPage(): void
    {
        /* Start y */
        $positionX = $this->paddingCalendarDays;
        $positionY = $this->yCalendarBoxBottom + $this->paddingCalendarDays;

        /* Add title */
        $fontSizeTitle = $this->fontSizeTitle;
        $angleAll = 0;
        imagettftext($this->imageTarget, $this->fontSizeTitle, $angleAll, $positionX, $positionY + $fontSizeTitle, $this->colors['white'], $this->pathFont, $this->calendarBuilderService->getParameterTarget()->getPageTitle());

        /* Add position */
        $anglePosition = 90;
        $dimensionPosition = $this->getDimension($this->calendarBuilderService->getParameterTarget()->getCoordinate(), $this->fontSizePosition, $anglePosition);
        $xPosition = $this->paddingCalendarDays + $dimensionPosition['width'] + $this->fontSizePosition;
        $yPosition = $this->yCalendarBoxBottom - $this->paddingCalendarDays;
        imagettftext($this->imageTarget, $this->fontSizePosition, $anglePosition, $xPosition, $yPosition, $this->colors['white'], $this->pathFont, $this->calendarBuilderService->getParameterTarget()->getCoordinate());
    }

    /**
     * Adds the title page elements (instead of the calendar).
     *
     * @throws Exception
     */
    protected function addTitleOnTitlePage(): void
    {
        $target = $this->calendarBuilderService->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->width / 2));
        $this->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        $paddingTopYear = $this->getSize(0);
        $dimensionYear = $this->addText(sprintf('%s', $target->getTitle()), $this->fontSizeTitlePage, $this->colors['white'], $paddingTopYear, CalendarBuilderServiceConstants::ALIGN_CENTER, CalendarBuilderServiceConstants::VALIGN_TOP);
        $this->addY($dimensionYear['height'] + $paddingTopYear);

        $paddingTopSubtext = $this->getSize(40);
        $dimensionYear = $this->addText(sprintf('%s', $target->getSubtitle()), $this->fontSizeTitlePageSubtext, $this->colors['white'], $paddingTopSubtext, CalendarBuilderServiceConstants::ALIGN_CENTER, CalendarBuilderServiceConstants::VALIGN_TOP);
        $this->addY($dimensionYear['height'] + $paddingTopSubtext);
    }

    /**
     * Add day to calendar.
     *
     * @param int $day
     * @param int $align
     * @throws Exception
     */
    protected function addDay(int $day, int $align = CalendarBuilderServiceConstants::ALIGN_LEFT): void
    {
        $target = $this->calendarBuilderService->getParameterTarget();

        /* Add distance for next day and between calendar weeks */
        $calendarWeekDistance = $this->calendarBuilderService->getDayOfWeek($target->getYear(), $target->getMonth(), $day) === CalendarBuilderServiceConstants::DAY_MONDAY ? $this->dayDistance : 0;

        /* Add x for next day */
        $this->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? ($this->dayDistance + $calendarWeekDistance) : -1 * $this->dayDistance);

        /* Add day */
        $color = $this->getDayColor($target->getYear(), $target->getMonth(), $day);
        $dimension = $this->addText(sprintf('%02d', $day), $this->fontSizeDay, $color, align: $align);

        /* Save position */
        $this->positionDays[$this->calendarBuilderService->getDayKey($day)] = [
            'x' => $this->positionX,
            'y' => $this->positionY,
            'align' => $align,
            'dimension' => $dimension,
            'day' => $day,
        ];

        /* Add x for next day */
        $this->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? $dimension['width'] : -1 * ($dimension['width'] + $calendarWeekDistance));
    }

    /**
     * Adds the calendar (year, month and days).
     *
     * @throws Exception
     */
    protected function addYearMonthAndDays(): void
    {
        $target = $this->calendarBuilderService->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->width / 2) + round($this->width / 8));
        $this->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        /* Add month */
        $paddingTop = $this->getSize(0);
        $dimensionMonth = $this->addText(sprintf('%02d', $target->getMonth()), $this->fontSizeMonth, $this->colors['white'], $paddingTop, CalendarBuilderServiceConstants::ALIGN_CENTER, CalendarBuilderServiceConstants::VALIGN_TOP);
        $this->addY($dimensionMonth['height'] + $paddingTop);

        /* Add year */
        $paddingTop = $this->getSize(20);
        $dimensionYear = $this->addText(sprintf('%s', $target->getYear()), $this->fontSizeYear, $this->colors['white'], $paddingTop, CalendarBuilderServiceConstants::ALIGN_CENTER, CalendarBuilderServiceConstants::VALIGN_TOP);
        $this->addY($dimensionYear['height'] + $paddingTop);

        /* Add days */
        $days = $this->calendarBuilderService->getDays();

        /* Add first days (left side) */
        $this->setPositionX($xCenterCalendar - intval(round($dimensionYear['width'] / 2)));
        $this->addX(-$this->dayDistance);
        for ($day = $days['left']['to']; $day >= $days['left']['from']; $day--) {
            $this->addDay($day, CalendarBuilderServiceConstants::ALIGN_RIGHT);
        }

        /* Add second part of days (right side) */
        $this->setPositionX($xCenterCalendar + intval(round($dimensionYear['width'] / 2)));
        $this->addX($this->dayDistance);
        for ($day = $days['right']['from']; $day <= $days['right']['to']; $day++) {
            $this->addDay($day);
        }
    }

    /**
     * Adds calendar week to day.
     *
     * @param string $dayKey
     * @throws Exception
     */
    protected function addCalendarWeek(string $dayKey): void
    {
        $target = $this->calendarBuilderService->getParameterTarget();

        $positionDay = $this->positionDays[$dayKey];
        $day = $positionDay['day'];
        $dimensionDay = $positionDay['dimension'];
        $align = $positionDay['align'];

        $weekNumber = $this->calendarBuilderService->getCalendarWeekIfMonday($target->getYear(), $target->getMonth(), $day);

        /* Add calendar week, if day is monday */
        if ($weekNumber === null) {
            return;
        }

        /* Set x and y */
        $this->setPositionX($positionDay['x']);
        $this->setPositionY($positionDay['y']);

        /* Set calendar week position (ALIGN_LEFT -> right side) */
        $this->positionX -= $align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width'];
        $this->positionY += intval(round(1.0 * $this->fontSizeDay));

        /* Build calendar week text */
        $weekNumberText = sprintf('KW %02d >', $weekNumber);

        /* Add calendar week */
        $this->addText($weekNumberText, intval(ceil($this->fontSizeDay * 0.5)), $this->colors['white']);

        /* Add line */
        $positionX = $this->positionX - intval(round($this->dayDistance / 1));
        imageline($this->imageTarget, $positionX, $this->positionY, $positionX, $positionDay['y'] - $this->fontSizeDay, $this->colors['white']);
    }

    /**
     * Adds calendar week to days.
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function addCalendarWeeks(): void
    {
        foreach ($this->positionDays as $dayKey => $positionDay) {
            $this->addCalendarWeek($dayKey);
        }
    }

    /**
     * Add holiday or event to day.
     *
     * @param string $dayKey
     * @throws Exception
     */
    protected function addHolidayOrEvent(string $dayKey): void
    {
        $positionDay = $this->positionDays[$dayKey];
        $day = $positionDay['day'];
        $dimensionDay = $positionDay['dimension'];
        $align = $positionDay['align'];

        $dayKey = $this->calendarBuilderService->getDayKey($day);
        
        $eventsAndHolidays = $this->calendarBuilderService->getEventsAndHolidays();

        if (!array_key_exists($dayKey, $eventsAndHolidays)) {
            return;
        }

        $eventOrHoliday = $eventsAndHolidays[$dayKey];

        /* Set x and y */
        $this->setPositionX($positionDay['x']);
        $this->setPositionY($positionDay['y']);

        /* Angle and font size */
        $angleEvent = 80;
        $fontSizeEvent = intval(ceil($this->fontSizeDay * 0.6));

        /* Get name */
        $name = strlen($eventOrHoliday['name']) > self::MAX_LENGTH_EVENT_CAPTION ?
            substr($eventOrHoliday['name'], 0, self::MAX_LENGTH_EVENT_CAPTION - strlen(self::MAX_LENGTH_ADD)).self::MAX_LENGTH_ADD :
            $eventOrHoliday['name'];

        /* Dimension Event */
        $xEvent = $fontSizeEvent + intval(round(($dimensionDay['width'] - $fontSizeEvent) / 2));

        /* Set event position */
        $this->positionX -= $align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width'];
        $this->positionX += $xEvent;
        $this->positionY -= intval(round(1.5 * $this->fontSizeDay));

        /* Add Event */
        $this->addText(text: $name, fontSize: $fontSizeEvent, color: $this->colors['white'], angle: $angleEvent);
    }

    /**
     * Adds holidays and events to days.
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function addHolidaysAndEvents(): void
    {
        foreach ($this->positionDays as $dayKey => $positionDay) {
            $this->addHolidayOrEvent($dayKey);
        }
    }

    /**
     * Adds QR Code.
     *
     * @throws Exception
     */
    protected function addQrCode(): void
    {
        /* Set background color */
        $backgroundColor = [255, 0, 0];

        /* Matrix length of qrCode */
        $matrixLength = 37;

        /* Wanted width (and height) of qrCode */
        $width = 800;

        /* Calculate scale of qrCode */
        $scale = intval(ceil($width / $matrixLength));

        /* Set options for qrCode */
        $options = [
            'eccLevel' => QRCode::ECC_H,
            'outputType' => QRCode::OUTPUT_IMAGICK,
            'version' => $this->qrCodeVersion,
            'addQuietzone' => false,
            'scale' => $scale,
            'markupDark' => '#fff',
            'markupLight' => '#f00',
        ];

        /* Get blob from qrCode image */
        $qrCodeBlob = (new QRCode(new QROptions($options)))->render($this->url);

        if (!is_string($qrCodeBlob)) {
            throw new LogicException('$qrCodeBlob must be a string');
        }

        /* Create GDImage from blob */
        $imageQrCode = imagecreatefromstring(strval($qrCodeBlob));

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

        /* Add dynamically generated qr image to main image */
        imagecopyresized($this->imageTarget, $imageQrCode, $this->paddingCalendarDays, $this->height - $this->paddingCalendarDays - $this->heightQrCode, 0, 0, $this->widthQrCode, $this->heightQrCode, $widthQrCode, $heightQrCode);

        /* Destroy image. */
        imagedestroy($imageQrCode);
    }

    /**
     * Builds the given source image to a calendar page.
     *
     * @return ImageContainer
     * @throws Exception
     */
    public function build(): ImageContainer
    {
        $target = $this->calendarBuilderService->getParameterTarget();
        $source = $this->calendarBuilderService->getParameterSource();

        $this->valignImage = CalendarBuilderServiceConstants::VALIGN_TOP;
        $this->url = 'https://github.com/';

        $this->pathSourceAbsolute = $source->getImage()->getPathReal();
        $this->pathTargetAbsolute = $this->getTargetPathFromSource($source->getImage());

        /* Init */
        $this->prepare();

        /* Add main image */
        $this->addImage();

        /* Add calendar area */
        $this->addRectangle();

        /* Add title, position, etc. */
        $this->addImageDescriptionAndPositionOnCalendarPage();

        /* Add calendar */
        switch (true) {
            case $target->getMonth() === 0:
                $this->addTitleOnTitlePage();
                break;

            default:
                $this->addYearMonthAndDays();
                $this->addCalendarWeeks();
                $this->addHolidaysAndEvents();
                break;
        }

        /* Add qr code */
        $this->addQrCode();

        /* Write image */
        $this->writeImage();

        /* Destroy image */
        $this->destroy();

        return (new ImageContainer())
            ->setSource($this->getImageProperties($this->pathSourceAbsolute))
            ->setTarget($this->getImageProperties($this->pathTargetAbsolute))
        ;
    }
}

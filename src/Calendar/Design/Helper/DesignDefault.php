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

namespace App\Calendar\Design\Helper;

use App\Calendar\Design\Helper\Base\DesignHelperBase;
use App\Constants\Color;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;

/**
 * Class DesignDefault
 *
 * Creates the default calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DesignDefault extends DesignHelperBase
{
    /**
     * Constants.
     */
    private const CALENDAR_BOX_BOTTOM_SIZE = 9/48;

    protected const MAX_LENGTH_EVENT_CAPTION = 28;

    private const MAX_LENGTH_ADD = '...';

    private const DEFAULT_TRANSPARENCY = 60;



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
    protected int $valignImage;

    protected int $yCalendarBoxBottom;

    protected string $url;

    /** @var array<string, array{x: int, y: int, align: int, dimension: int[], day: int}> $positionDays */
    protected array $positionDays = [];



    /**
     * Do the main init for XXXDefault.php
     *
     * @inheritdoc 
     */
    public function doInit(): void
    {
        /* Clear positions */
        $this->positionDays = [];

        /* Set qr code version */
        $this->qrCodeVersion = CalendarBuilderServiceConstants::DEFAULT_QR_CODE_VERSION;

        /* Calculate sizes */
        $this->fontSizeTitle = $this->designBase->getSize($this->fontSizeTitle);
        $this->fontSizePosition = $this->designBase->getSize($this->fontSizePosition);
        $this->fontSizeYear = $this->designBase->getSize($this->fontSizeYear);
        $this->fontSizeMonth = $this->designBase->getSize($this->fontSizeMonth);
        $this->fontSizeDay = $this->designBase->getSize($this->fontSizeDay);
        $this->fontSizeTitlePage = $this->designBase->getSize($this->fontSizeTitlePage);
        $this->fontSizeTitlePageSubtext = $this->designBase->getSize($this->fontSizeTitlePageSubtext);
        $this->fontSizeTitlePageAuthor = $this->designBase->getSize($this->fontSizeTitlePageAuthor);
        $this->paddingCalendarDays = $this->designBase->getSize($this->paddingCalendarDays);
        $this->heightQrCode = $this->designBase->getSize($this->heightQrCode);
        $this->widthQrCode = $this->designBase->getSize($this->widthQrCode);
        $this->dayDistance = $this->designBase->getSize($this->dayDistance);

        $this->yCalendarBoxBottom = intval(floor($this->designBase->getHeightTarget() * (1 - self::CALENDAR_BOX_BOTTOM_SIZE)));

        $this->valignImage = CalendarBuilderServiceConstants::VALIGN_TOP;
        $this->url = $this->designBase->getCalendarBuilderService()->getParameterTarget()->getUrl(
            $this->designBase->getCalendarBuilderService()->getParameterSource()->getIdentification()
        );
    }

    /**
     * Do the main build for XXXDefault.php
     *
     * @inheritdoc
     * @throws Exception
     */
    public function doBuild(): void
    {
        /* Creates some needed colors. */
        $this->createColors();

        /* Adds the main image */
        $this->addImage();

        /* Add calendar area */
        $this->addRectangle();

        /* Add title, position, etc. */
        $this->addImageDescriptionAndPositionOnCalendarPage();

        $target = $this->designBase->getCalendarBuilderService()->getParameterTarget();

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
    }


    /**
     * Returns the transparency from given config.
     *
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    protected function getTransparency(): int
    {
        if (is_null($this->config)) {
            return self::DEFAULT_TRANSPARENCY;
        }

        if (!$this->config->hasKey('transparency')) {
            return self::DEFAULT_TRANSPARENCY;
        }

        return $this->config->getKeyInteger('transparency');
    }

    /**
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        $this->designBase->resetColors();
        $this->designBase->createColor(Color::BLACK, 0, 0, 0);
        $this->designBase->createColor(Color::BLACK_TRANSPARENCY, 0, 0, 0, $this->getTransparency());
        $this->designBase->createColor(Color::RED, 255, 0, 0);
        $this->designBase->createColor(Color::RED_TRANSPARENCY, 255, 0, 0, $this->getTransparency());
        $this->designBase->createColor(Color::WHITE, 255, 255, 255);
        $this->designBase->createColor(Color::WHITE_TRANSPARENCY, 255, 255, 255, $this->getTransparency());
    }

    /**
     * Returns the color of given day.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return string
     * @throws Exception
     */
    protected function getDayColorKey(int $year, int $month, int $day): string
    {
        /* Print day in red if the day is sunday */
        if ($this->designBase->getCalendarBuilderService()->getDayOfWeek($year, $month, $day) === CalendarBuilderServiceConstants::DAY_SUNDAY) {
            return Color::RED;
        }

        /* Print day in red if it is a holiday */
        $dayKey = $this->designBase->getCalendarBuilderService()->getDayKey($day);
        $holidays = $this->designBase->getCalendarBuilderService()->getHolidays();
        if (array_key_exists($dayKey, $holidays) && $holidays[$dayKey] === true) {
            return Color::RED;
        }

        /* Print day in white otherwise */
        return Color::WHITE;
    }

    /**
     * Add image
     */
    protected function addImage(): void
    {
        $positionY = match ($this->valignImage) {
            CalendarBuilderServiceConstants::VALIGN_BOTTOM => $this->yCalendarBoxBottom - $this->designBase->getHeightTarget(),
            default => 0,
        };

        $positionX = 0;

        $this->designBase->addImage(
            $positionX,
            $positionY,
            $this->designBase->getWidthTarget(),
            $this->designBase->getHeightTarget()
        );
    }

    /**
     * Add bottom calendar box.
     */
    protected function addRectangle(): void
    {
        /* Add fullscreen rectangle to image. */
        $this->designBase->addRectangle(
            0,
            $this->yCalendarBoxBottom,
            $this->designBase->getWidthTarget(),
            $this->designBase->getHeightTarget(),
            Color::BLACK_TRANSPARENCY
        );
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
        $this->designBase->addTextRaw(
            $this->designBase->getCalendarBuilderService()->getParameterTarget()->getPageTitle(),
            $this->fontSizeTitle,
            Color::WHITE,
            $positionX,
            $positionY + $this->fontSizeTitle
        );

        /* Add position */
        $anglePosition = $this->designBase->getAngle(90);
        $xPosition = $this->paddingCalendarDays + $this->fontSizePosition;
        $yPosition = $this->yCalendarBoxBottom - $this->paddingCalendarDays;
        $this->designBase->addTextRaw(
            $this->designBase->getCalendarBuilderService()->getParameterTarget()->getCoordinate(),
            $this->fontSizePosition,
            Color::WHITE,
            $xPosition,
            $yPosition,
            $anglePosition
        );
    }

    /**
     * Adds the title page elements (instead of the calendar).
     *
     * @throws Exception
     */
    protected function addTitleOnTitlePage(): void
    {
        $target = $this->designBase->getCalendarBuilderService()->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->designBase->getWidthTarget() / 2));
        $this->designBase->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        $paddingTopYear = $this->designBase->getSize(0);
        $dimensionYear = $this->designBase->addText(
            sprintf('%s', $target->getTitle()),
            $this->fontSizeTitlePage,
            'white',
            $paddingTopYear,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->designBase->addY($dimensionYear['height'] + $paddingTopYear);

        $paddingTopSubtext = $this->designBase->getSize(40);
        $dimensionYear = $this->designBase->addText(
            sprintf('%s', $target->getSubtitle()),
            $this->fontSizeTitlePageSubtext,
            'white',
            $paddingTopSubtext,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->designBase->addY($dimensionYear['height'] + $paddingTopSubtext);
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
        $target = $this->designBase->getCalendarBuilderService()->getParameterTarget();

        /* Add distance for the next day and between calendar weeks */
        $calendarWeekDistance = $this->designBase->getCalendarBuilderService()->getDayOfWeek($target->getYear(), $target->getMonth(), $day) === CalendarBuilderServiceConstants::DAY_MONDAY ? $this->dayDistance : 0;

        /* Add x for next day */
        $this->designBase->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? ($this->dayDistance + $calendarWeekDistance) : -1 * $this->dayDistance);

        /* Add day */
        $colorKey = $this->getDayColorKey($target->getYear(), $target->getMonth(), $day);
        $dimension = $this->designBase->addText(sprintf('%02d', $day), $this->fontSizeDay, $colorKey, align: $align);

        /* Save position */
        $this->positionDays[$this->designBase->getCalendarBuilderService()->getDayKey($day)] = [
            'x' => $this->designBase->getPositionX(),
            'y' => $this->designBase->getPositionY(),
            'align' => $align,
            'dimension' => $dimension,
            'day' => $day,
        ];

        /* Add x for next day */
        $this->designBase->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? $dimension['width'] : -1 * ($dimension['width'] + $calendarWeekDistance));
    }

    /**
     * Adds the calendar (year, month and days).
     *
     * @throws Exception
     */
    protected function addYearMonthAndDays(): void
    {
        $target = $this->designBase->getCalendarBuilderService()->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->designBase->getWidthTarget() / 2) + round($this->designBase->getWidthTarget() / 8));
        $this->designBase->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        /* Add month */
        $paddingTop = $this->designBase->getSize(0);
        $dimensionMonth = $this->designBase->addText(
            sprintf('%02d', $target->getMonth()),
            $this->fontSizeMonth,
            Color::WHITE,
            $paddingTop,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->designBase->addY($dimensionMonth['height'] + $paddingTop);

        /* Add year */
        $paddingTop = $this->designBase->getSize(20);
        $dimensionYear = $this->designBase->addText(
            sprintf('%s', $target->getYear()),
            $this->fontSizeYear,
            Color::WHITE,
            $paddingTop,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->designBase->addY($dimensionYear['height'] + $paddingTop);

        /* Add days */
        $days = $this->designBase->getCalendarBuilderService()->getDays();

        /* Add first days (left side) */
        $this->designBase->setPositionX($xCenterCalendar - intval(round($dimensionYear['width'] / 2)));
        $this->designBase->addX(-$this->dayDistance);
        for ($day = $days['left']['to']; $day >= $days['left']['from']; $day--) {
            $this->addDay($day, CalendarBuilderServiceConstants::ALIGN_RIGHT);
        }

        /* Add second part of days (right side) */
        $this->designBase->setPositionX($xCenterCalendar + intval(round($dimensionYear['width'] / 2)));
        $this->designBase->addX($this->dayDistance);
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
        $target = $this->designBase->getCalendarBuilderService()->getParameterTarget();

        $positionDay = $this->positionDays[$dayKey];
        $day = $positionDay['day'];
        $dimensionDay = $positionDay['dimension'];
        $align = $positionDay['align'];

        $weekNumber = $this->designBase->getCalendarBuilderService()->getCalendarWeekIfMonday($target->getYear(), $target->getMonth(), $day);

        /* Add the calendar week if the day is monday */
        if ($weekNumber === null) {
            return;
        }

        /* Set x and y */
        $this->designBase->setPositionX($positionDay['x']);
        $this->designBase->setPositionY($positionDay['y']);

        /* Set calendar week position (ALIGN_LEFT -> right side) */
        $this->designBase->removeX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width']);
        $this->designBase->addY(intval(round(1.0 * $this->fontSizeDay)));

        /* Build calendar week text */
        $weekNumberText = sprintf('KW %02d >', $weekNumber);

        /* Add calendar week */
        $this->designBase->addText($weekNumberText, intval(ceil($this->fontSizeDay * 0.5)), Color::WHITE);

        /* Add line */
        $positionX = $this->designBase->getPositionX() - intval(round($this->dayDistance));
        $this->designBase->drawLine($positionX, $this->designBase->getPositionY(), $positionX, $positionDay['y'] - $this->fontSizeDay, Color::WHITE);
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

        $dayKey = $this->designBase->getCalendarBuilderService()->getDayKey($day);
        
        $eventsAndHolidays = $this->designBase->getCalendarBuilderService()->getEventsAndHolidays();

        if (!array_key_exists($dayKey, $eventsAndHolidays)) {
            return;
        }

        $eventOrHoliday = $eventsAndHolidays[$dayKey];

        /* Set x and y */
        $this->designBase->setPositionX($positionDay['x']);
        $this->designBase->setPositionY($positionDay['y']);

        /* Angle and font size */
        $angleEvent = $this->designBase->getAngle(80);
        $fontSizeEvent = intval(ceil($this->fontSizeDay * 0.6));

        /* Get name */
        $name = strlen($eventOrHoliday['name']) > self::MAX_LENGTH_EVENT_CAPTION ?
            substr($eventOrHoliday['name'], 0, self::MAX_LENGTH_EVENT_CAPTION - strlen(self::MAX_LENGTH_ADD)).self::MAX_LENGTH_ADD :
            $eventOrHoliday['name'];

        /* Dimension Event */
        $xEvent = $fontSizeEvent + intval(round(($dimensionDay['width'] - $fontSizeEvent) / 2));

        /* Set event position */
        $this->designBase->removeX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width']);
        $this->designBase->addX($xEvent);
        $this->designBase->removeY(intval(round(1.5 * $this->fontSizeDay)));

        /* Add Event */
        $this->designBase->addText(text: $name, fontSize: $fontSizeEvent, keyColor: Color::WHITE, angle: $angleEvent);
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

        $this->designBase->addImageBlob(
            $qrCodeBlob,
            $this->paddingCalendarDays,
            $this->designBase->getHeightTarget() - $this->paddingCalendarDays - $this->heightQrCode,
            $this->widthQrCode,
            $this->heightQrCode,
            $backgroundColor
        );
    }
}

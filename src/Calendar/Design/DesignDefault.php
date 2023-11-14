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

namespace App\Calendar\Design;

use App\Calendar\Design\Base\DesignBase;
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
class DesignDefault extends DesignBase
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
        $this->fontSizeTitle = $this->imageBuilder->getSize($this->fontSizeTitle);
        $this->fontSizePosition = $this->imageBuilder->getSize($this->fontSizePosition);
        $this->fontSizeYear = $this->imageBuilder->getSize($this->fontSizeYear);
        $this->fontSizeMonth = $this->imageBuilder->getSize($this->fontSizeMonth);
        $this->fontSizeDay = $this->imageBuilder->getSize($this->fontSizeDay);
        $this->fontSizeTitlePage = $this->imageBuilder->getSize($this->fontSizeTitlePage);
        $this->fontSizeTitlePageSubtext = $this->imageBuilder->getSize($this->fontSizeTitlePageSubtext);
        $this->fontSizeTitlePageAuthor = $this->imageBuilder->getSize($this->fontSizeTitlePageAuthor);
        $this->paddingCalendarDays = $this->imageBuilder->getSize($this->paddingCalendarDays);
        $this->heightQrCode = $this->imageBuilder->getSize($this->heightQrCode);
        $this->widthQrCode = $this->imageBuilder->getSize($this->widthQrCode);
        $this->dayDistance = $this->imageBuilder->getSize($this->dayDistance);

        $this->yCalendarBoxBottom = intval(floor($this->imageBuilder->getHeightTarget() * (1 - self::CALENDAR_BOX_BOTTOM_SIZE)));

        $this->valignImage = CalendarBuilderServiceConstants::VALIGN_TOP;
        $this->url = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget()->getUrl(
            $this->imageBuilder->getCalendarBuilderService()->getParameterSource()->getIdentification()
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

        $target = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget();

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
        $this->imageBuilder->resetColors();
        $this->imageBuilder->createColor(Color::BLACK, 0, 0, 0);
        $this->imageBuilder->createColor(Color::BLACK_TRANSPARENCY, 0, 0, 0, $this->getTransparency());
        $this->imageBuilder->createColor(Color::RED, 255, 0, 0);
        $this->imageBuilder->createColor(Color::RED_TRANSPARENCY, 255, 0, 0, $this->getTransparency());
        $this->imageBuilder->createColor(Color::WHITE, 255, 255, 255);
        $this->imageBuilder->createColor(Color::WHITE_TRANSPARENCY, 255, 255, 255, $this->getTransparency());
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
        if ($this->imageBuilder->getCalendarBuilderService()->getDayOfWeek($year, $month, $day) === CalendarBuilderServiceConstants::DAY_SUNDAY) {
            return Color::RED;
        }

        /* Print day in red if it is a holiday */
        $dayKey = $this->imageBuilder->getCalendarBuilderService()->getDayKey($day);
        $holidays = $this->imageBuilder->getCalendarBuilderService()->getHolidays();
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
            CalendarBuilderServiceConstants::VALIGN_BOTTOM => $this->yCalendarBoxBottom - $this->imageBuilder->getHeightTarget(),
            default => 0,
        };

        $positionX = 0;

        $this->imageBuilder->addImage(
            $positionX,
            $positionY,
            $this->imageBuilder->getWidthTarget(),
            $this->imageBuilder->getHeightTarget()
        );
    }

    /**
     * Add bottom calendar box.
     */
    protected function addRectangle(): void
    {
        /* Add fullscreen rectangle to image. */
        $this->imageBuilder->addRectangle(
            0,
            $this->yCalendarBoxBottom,
            $this->imageBuilder->getWidthTarget(),
            $this->imageBuilder->getHeightTarget(),
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
        $this->imageBuilder->addTextRaw(
            $this->imageBuilder->getCalendarBuilderService()->getParameterTarget()->getPageTitle(),
            $this->fontSizeTitle,
            Color::WHITE,
            $positionX,
            $positionY + $this->fontSizeTitle
        );

        /* Add position */
        $anglePosition = $this->imageBuilder->getAngle(90);
        $xPosition = $this->paddingCalendarDays + $this->fontSizePosition;
        $yPosition = $this->yCalendarBoxBottom - $this->paddingCalendarDays;
        $this->imageBuilder->addTextRaw(
            $this->imageBuilder->getCalendarBuilderService()->getParameterTarget()->getCoordinate(),
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
        $target = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->imageBuilder->getWidthTarget() / 2));
        $this->imageBuilder->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        $paddingTopYear = $this->imageBuilder->getSize(0);
        $dimensionYear = $this->imageBuilder->addText(
            sprintf('%s', $target->getTitle()),
            $this->fontSizeTitlePage,
            'white',
            $paddingTopYear,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->imageBuilder->addY($dimensionYear['height'] + $paddingTopYear);

        $paddingTopSubtext = $this->imageBuilder->getSize(40);
        $dimensionYear = $this->imageBuilder->addText(
            sprintf('%s', $target->getSubtitle()),
            $this->fontSizeTitlePageSubtext,
            'white',
            $paddingTopSubtext,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->imageBuilder->addY($dimensionYear['height'] + $paddingTopSubtext);
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
        $target = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget();

        /* Add distance for the next day and between calendar weeks */
        $calendarWeekDistance = $this->imageBuilder->getCalendarBuilderService()->getDayOfWeek($target->getYear(), $target->getMonth(), $day) === CalendarBuilderServiceConstants::DAY_MONDAY ? $this->dayDistance : 0;

        /* Add x for next day */
        $this->imageBuilder->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? ($this->dayDistance + $calendarWeekDistance) : -1 * $this->dayDistance);

        /* Add day */
        $colorKey = $this->getDayColorKey($target->getYear(), $target->getMonth(), $day);
        $dimension = $this->imageBuilder->addText(sprintf('%02d', $day), $this->fontSizeDay, $colorKey, align: $align);

        /* Save position */
        $this->positionDays[$this->imageBuilder->getCalendarBuilderService()->getDayKey($day)] = [
            'x' => $this->imageBuilder->getPositionX(),
            'y' => $this->imageBuilder->getPositionY(),
            'align' => $align,
            'dimension' => $dimension,
            'day' => $day,
        ];

        /* Add x for next day */
        $this->imageBuilder->addX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? $dimension['width'] : -1 * ($dimension['width'] + $calendarWeekDistance));
    }

    /**
     * Adds the calendar (year, month and days).
     *
     * @throws Exception
     */
    protected function addYearMonthAndDays(): void
    {
        $target = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget();

        /* Set x and y */
        $xCenterCalendar = intval(round($this->imageBuilder->getWidthTarget() / 2) + round($this->imageBuilder->getWidthTarget() / 8));
        $this->imageBuilder->initXY($xCenterCalendar, $this->yCalendarBoxBottom + $this->paddingCalendarDays);

        /* Add month */
        $paddingTop = $this->imageBuilder->getSize(0);
        $dimensionMonth = $this->imageBuilder->addText(
            sprintf('%02d', $target->getMonth()),
            $this->fontSizeMonth,
            Color::WHITE,
            $paddingTop,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->imageBuilder->addY($dimensionMonth['height'] + $paddingTop);

        /* Add year */
        $paddingTop = $this->imageBuilder->getSize(20);
        $dimensionYear = $this->imageBuilder->addText(
            sprintf('%s', $target->getYear()),
            $this->fontSizeYear,
            Color::WHITE,
            $paddingTop,
            CalendarBuilderServiceConstants::ALIGN_CENTER,
            CalendarBuilderServiceConstants::VALIGN_TOP
        );
        $this->imageBuilder->addY($dimensionYear['height'] + $paddingTop);

        /* Add days */
        $days = $this->imageBuilder->getCalendarBuilderService()->getDays();

        /* Add first days (left side) */
        $this->imageBuilder->setPositionX($xCenterCalendar - intval(round($dimensionYear['width'] / 2)));
        $this->imageBuilder->addX(-$this->dayDistance);
        for ($day = $days['left']['to']; $day >= $days['left']['from']; $day--) {
            $this->addDay($day, CalendarBuilderServiceConstants::ALIGN_RIGHT);
        }

        /* Add second part of days (right side) */
        $this->imageBuilder->setPositionX($xCenterCalendar + intval(round($dimensionYear['width'] / 2)));
        $this->imageBuilder->addX($this->dayDistance);
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
        $target = $this->imageBuilder->getCalendarBuilderService()->getParameterTarget();

        $positionDay = $this->positionDays[$dayKey];
        $day = $positionDay['day'];
        $dimensionDay = $positionDay['dimension'];
        $align = $positionDay['align'];

        $weekNumber = $this->imageBuilder->getCalendarBuilderService()->getCalendarWeekIfMonday($target->getYear(), $target->getMonth(), $day);

        /* Add the calendar week if the day is monday */
        if ($weekNumber === null) {
            return;
        }

        /* Set x and y */
        $this->imageBuilder->setPositionX($positionDay['x']);
        $this->imageBuilder->setPositionY($positionDay['y']);

        /* Set calendar week position (ALIGN_LEFT -> right side) */
        $this->imageBuilder->removeX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width']);
        $this->imageBuilder->addY(intval(round(1.0 * $this->fontSizeDay)));

        /* Build calendar week text */
        $weekNumberText = sprintf('KW %02d >', $weekNumber);

        /* Add calendar week */
        $this->imageBuilder->addText($weekNumberText, intval(ceil($this->fontSizeDay * 0.5)), Color::WHITE);

        /* Add line */
        $positionX = $this->imageBuilder->getPositionX() - intval(round($this->dayDistance));
        $this->imageBuilder->drawLine($positionX, $this->imageBuilder->getPositionY(), $positionX, $positionDay['y'] - $this->fontSizeDay, Color::WHITE);
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

        $dayKey = $this->imageBuilder->getCalendarBuilderService()->getDayKey($day);
        
        $eventsAndHolidays = $this->imageBuilder->getCalendarBuilderService()->getEventsAndHolidays();

        if (!array_key_exists($dayKey, $eventsAndHolidays)) {
            return;
        }

        $eventOrHoliday = $eventsAndHolidays[$dayKey];

        /* Set x and y */
        $this->imageBuilder->setPositionX($positionDay['x']);
        $this->imageBuilder->setPositionY($positionDay['y']);

        /* Angle and font size */
        $angleEvent = $this->imageBuilder->getAngle(80);
        $fontSizeEvent = intval(ceil($this->fontSizeDay * 0.6));

        /* Get name */
        $name = strlen($eventOrHoliday['name']) > self::MAX_LENGTH_EVENT_CAPTION ?
            substr($eventOrHoliday['name'], 0, self::MAX_LENGTH_EVENT_CAPTION - strlen(self::MAX_LENGTH_ADD)).self::MAX_LENGTH_ADD :
            $eventOrHoliday['name'];

        /* Dimension Event */
        $xEvent = $fontSizeEvent + intval(round(($dimensionDay['width'] - $fontSizeEvent) / 2));

        /* Set event position */
        $this->imageBuilder->removeX($align === CalendarBuilderServiceConstants::ALIGN_LEFT ? 0 : $dimensionDay['width']);
        $this->imageBuilder->addX($xEvent);
        $this->imageBuilder->removeY(intval(round(1.5 * $this->fontSizeDay)));

        /* Add Event */
        $this->imageBuilder->addText(text: $name, fontSize: $fontSizeEvent, keyColor: Color::WHITE, angle: $angleEvent);
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

        $this->imageBuilder->addImageBlob(
            $qrCodeBlob,
            $this->paddingCalendarDays,
            $this->imageBuilder->getHeightTarget() - $this->paddingCalendarDays - $this->heightQrCode,
            $this->widthQrCode,
            $this->heightQrCode,
            $backgroundColor
        );
    }
}

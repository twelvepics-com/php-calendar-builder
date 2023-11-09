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
use App\Objects\Image\ImageContainer;
use App\Service\CalendarBuilderService;
use Exception;
use GdImage;

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
    protected CalendarBuilderService $calendarBuilderService;

    protected GdImage $imageTarget;

    protected GdImage $imageSource;

    protected int $width;

    protected int $height;

    protected float $aspectRatio;

    protected int $widthSource;

    protected int $heightSource;

    protected int $positionX;

    protected int $positionY;

    /**
     * Init function.
     *
     * @param CalendarBuilderService $calendarBuilderService
     * @param int $qrCodeVersion
     * @param bool $useCalendarImagePath
     * @param bool $deleteTargetImages
     * @return void
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    abstract public function init(
        CalendarBuilderService $calendarBuilderService,
        int $qrCodeVersion = CalendarBuilderServiceConstants::DEFAULT_QR_CODE_VERSION,
        bool $useCalendarImagePath = false,
        bool $deleteTargetImages = false
    ): void;

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
}

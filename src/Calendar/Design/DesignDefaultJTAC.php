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

use App\Constants\Color;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use Exception;

/**
 * Class DesignDefaultJTAC
 *
 * Creates the default-jtac calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DesignDefaultJTAC extends DesignDefault
{
    /**
     * Calculated values (by zoom).
     */
    protected int $fontSizeImage = 400;



    /**
     * Do the main init for XXXDefault.php
     *
     * @inheritdoc 
     */
    public function doInit(): void
    {
        parent::doInit();

        $this->fontSizeImage = $this->imageBuilder->getSize($this->fontSizeImage);
    }



    /**
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        parent::createColors();

        $this->imageBuilder->createColorFromConfig(Color::CUSTOM, 'color');
    }

    /**
     * Overwrites the image background with a rectangle and text.
     *
     * @throws Exception
     */
    protected function addImage(): void
    {
        /* Add calendar area (rectangle) */
        $this->imageBuilder->addRectangle(
            0,
            0,
            $this->imageBuilder->getWidthTarget(),
            $this->imageBuilder->getHeightTarget(),
            Color::CUSTOM
        );

        $xCenterCalendar = intval(round($this->imageBuilder->getWidthTarget() / 2));
        $yCenterCalendar = intval(round($this->imageBuilder->getHeightTarget() / 2));
        $this->imageBuilder->initXY($xCenterCalendar, $yCenterCalendar);

        $this->imageBuilder->addText(
            $this->imageBuilder->getCalendarBuilderService()->getParameterTarget()->getPageTitle(),
            $this->fontSizeImage,
            Color::WHITE,
            align: CalendarBuilderServiceConstants::ALIGN_CENTER
        );
    }
}

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
use Exception;

/**
 * Class DesignBlankJTAC
 *
 * Creates the blank-jtac calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
class DesignBlankJTAC extends DesignBase
{
    /**
     * Configures the configuration for the current design.
     *
     * @inheritdoc
     */
    protected function configureDefaultConfiguration(): void
    {
    }

    /**
     * Calculated values (by zoom).
     */
    protected int $fontSizeImage = 400;

    /**
     * Do the main init for XXXBlankJTAC.php
     *
     * @inheritdoc 
     */
    public function doInit(): void
    {
        $this->fontSizeImage = $this->imageBuilder->getSize($this->fontSizeImage);
    }

    /**
     * Do the main build for XXXBlankJTAC.php
     *
     * @inheritdoc
     * @throws Exception
     */
    public function doBuild(): void
    {
        /* Creates some needed colors. */
        $this->imageBuilder->createColor(Color::WHITE, 255, 255, 255);
        $this->imageBuilder->createColorFromConfig(Color::CUSTOM, 'color');

        $this->imageBuilder->addImage(0, 0, $this->imageBuilder->getWidthTarget(), $this->imageBuilder->getHeightTarget());

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

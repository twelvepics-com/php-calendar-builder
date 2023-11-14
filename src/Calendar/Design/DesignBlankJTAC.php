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
        $this->fontSizeImage = $this->designBase->getSize($this->fontSizeImage);
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
        $this->designBase->createColor(Color::WHITE, 255, 255, 255);
        $this->designBase->createColorFromConfig(Color::CUSTOM, 'color');

        $this->designBase->addImage(0, 0, $this->designBase->getWidthTarget(), $this->designBase->getHeightTarget());

        $xCenterCalendar = intval(round($this->designBase->getWidthTarget() / 2));
        $yCenterCalendar = intval(round($this->designBase->getHeightTarget() / 2));
        $this->designBase->initXY($xCenterCalendar, $yCenterCalendar);

        $this->designBase->addText(
            $this->designBase->getCalendarBuilderService()->getParameterTarget()->getPageTitle(),
            $this->fontSizeImage,
            Color::WHITE,
            align: CalendarBuilderServiceConstants::ALIGN_CENTER
        );
    }
}

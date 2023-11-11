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

use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use Exception;

/**
 * Class DesignDefault
 *
 * Creates the default calendar design.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-11)
 * @since 0.1.0 (2023-11-11) First version.
 */
class DesignDefaultJTAC extends DesignDefault
{
    protected int $fontSizeImage = 400;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function doInit(): void
    {
        parent::doInit();

        $this->fontSizeImage = $this->getSize($this->fontSizeImage);
    }

    /**
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        parent::createColors();

        $this->colors['custom'] = $this->createColorFromConfig($this->imageTarget, 'color');
    }

    /**
     * Add image
     * @throws Exception
     */
    protected function addImage(): void
    {
        /* Add calendar area (rectangle) */
        imagefilledrectangle($this->imageTarget, 0, 0, $this->widthTarget, $this->heightTarget, $this->colors['custom']);

        $xCenterCalendar = intval(round($this->widthTarget / 2));
        $yCenterCalendar = intval(round($this->heightTarget / 2));
        $this->initXY($xCenterCalendar, $yCenterCalendar);

        $this->addText(
            $this->calendarBuilderService->getParameterTarget()->getPageTitle(),
            $this->fontSizeImage,
            $this->colors['white'],
            align: CalendarBuilderServiceConstants::ALIGN_CENTER
        );
    }
}

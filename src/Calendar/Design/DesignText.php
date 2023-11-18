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
use App\Constants\KeyJson;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
use Exception;
use LogicException;

/**
 * Class DesignText
 *
 * Creates the blank-jtac calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
class DesignText extends DesignBase
{
    /**
     * Configures the configuration for the current design.
     *
     * @inheritdoc
     */
    protected function configureDefaultConfiguration(): void
    {
        /* settings.defaults.design.config.background-color */
        $this->addDefaultConfiguration(KeyJson::BACKGROUND_COLOR, [255, 0, 0]);

        /* settings.defaults.design.config.background-color */
        $this->addDefaultConfiguration(KeyJson::BOX_BOTTOM_RATIO, 9/48);

        /* settings.defaults.design.config.text */
        $this->addDefaultConfiguration(KeyJson::TEXT, 'Some nice text.');

        /* settings.defaults.design.config.text-font-size */
        $this->addDefaultConfiguration(KeyJson::TEXT_FONT_SIZE, 300);

        /* settings.defaults.design.config.author */
        $this->addDefaultConfiguration(KeyJson::AUTHOR, 'Author name');

        /* settings.defaults.design.config.author-font-size */
        $this->addDefaultConfiguration(KeyJson::AUTHOR_FONT_SIZE, 100);

        /* settings.defaults.design.config.author-font-size */
        $this->addDefaultConfiguration(KeyJson::AUTHOR_DISTANCE, 400);
    }

    /**
     * Calculated values (by zoom).
     */
    protected string $text;

    protected int $textFontSize;

    protected string $author;

    protected int $authorFontSize;

    protected int $authorDistance;

    /**
     * Do the main init for XXXBlankJTAC.php
     *
     * @inheritdoc
     * @throws Exception
     */
    public function doInit(): void
    {
        $this->text = $this->getConfigurationValueString(KeyJson::TEXT);
        $this->textFontSize = $this->imageBuilder->getSize($this->getConfigurationValueInteger(KeyJson::TEXT_FONT_SIZE));

        $this->author = $this->getConfigurationValueString(KeyJson::AUTHOR);
        $this->authorFontSize = $this->imageBuilder->getSize($this->getConfigurationValueInteger(KeyJson::AUTHOR_FONT_SIZE));
        $this->authorDistance = $this->imageBuilder->getSize($this->getConfigurationValueInteger(KeyJson::AUTHOR_DISTANCE));

        $this->createColors();
    }

    /**
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        $this->imageBuilder->resetColors();
        $this->imageBuilder->createColor(Color::WHITE, 255, 255, 255);

        $backgroundColor = $this->getConfigurationValueArray(KeyJson::BACKGROUND_COLOR);

        $red = $backgroundColor[0];
        $green = $backgroundColor[1];
        $blue = $backgroundColor[2];

        if (!is_int($red) ||!is_int($green) ||!is_int($blue)) {
            throw new LogicException('Invalid value type for background color. "int" expected.');
        }

        $this->imageBuilder->createColor('background-color', $red, $green, $blue);
    }

    /**
     * Do the main build for XXXBlankJTAC.php
     *
     * @inheritdoc
     * @throws Exception
     */
    public function doBuild(): void
    {
        /* Add calendar area (rectangle) */
        $this->imageBuilder->addRectangle(
            0,
            0,
            $this->imageBuilder->getWidthTarget(),
            $this->imageBuilder->getHeightTarget(),
            'background-color'
        );

        $boxBottomRatio = $this->getConfigurationValueFloat(KeyJson::BOX_BOTTOM_RATIO);

        $xCenterCalendar = intval(round($this->imageBuilder->getWidthTarget() / 2));
        $yCenterCalendar = intval(round(($this->imageBuilder->getHeightTarget() - $this->imageBuilder->getHeightTarget() * $boxBottomRatio) / 2));
        $this->imageBuilder->initXY($xCenterCalendar, $yCenterCalendar);

        $dimension = $this->imageBuilder->addText(
            $this->text,
            $this->textFontSize,
            Color::WHITE,
            align: CalendarBuilderServiceConstants::ALIGN_CENTER,
            valign: CalendarBuilderServiceConstants::VALIGN_MIDDLE,
        );

        $this->imageBuilder->addY(intval(round($dimension['height'] / 2)) + $this->authorDistance + $this->authorFontSize);
        $this->imageBuilder->addText(
            sprintf('- %s -', $this->author),
            $this->authorFontSize,
            Color::WHITE,
            align: CalendarBuilderServiceConstants::ALIGN_CENTER,
            valign: CalendarBuilderServiceConstants::VALIGN_MIDDLE,
        );
    }
}

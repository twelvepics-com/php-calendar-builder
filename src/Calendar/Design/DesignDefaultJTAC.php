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
use App\Constants\KeyJson;
use App\Constants\Service\Calendar\CalendarBuilderService as CalendarBuilderServiceConstants;
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
 * Class DesignDefaultJTAC
 *
 * Creates the default-jtac calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
class DesignDefaultJTAC extends DesignDefault
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

        /* settings.defaults.design.config.text */
        $this->addDefaultConfiguration(KeyJson::TEXT, 'CHANGEME');
    }

    /**
     * Calculated values (by zoom).
     */
    protected string $text = 'Text';
    protected int $textFontSize = 400;
    protected string $author = 'Author';
    protected int $authorFontSize = 100;
    protected int $authorDistance = 400;

    /**
     * Do the main init for XXXDefault.php
     *
     * @inheritdoc
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    public function doInit(): void
    {
        parent::doInit();

        $this->text = $this->getConfigurationValueString(KeyJson::TEXT);
        $this->textFontSize = $this->imageBuilder->getSize($this->getConfigurationValueInteger(KeyJson::TEXT_FONT_SIZE));

        $this->author = $this->getConfigurationValueString(KeyJson::AUTHOR);
        $this->authorFontSize = $this->imageBuilder->getSize($this->getConfigurationValueInteger(KeyJson::AUTHOR_FONT_SIZE));
        $this->authorDistance = $this->imageBuilder->getSize($this->authorDistance);
    }

    /**
     * Create the colors and save the integer values to color.
     *
     * @throws Exception
     */
    protected function createColors(): void
    {
        parent::createColors();

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
            'background-color'
        );

        $xCenterCalendar = intval(round($this->imageBuilder->getWidthTarget() / 2));
        $yCenterCalendar = intval(round(($this->imageBuilder->getHeightTarget() - $this->imageBuilder->getHeightTarget() * self::CALENDAR_BOX_BOTTOM_SIZE) / 2));
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

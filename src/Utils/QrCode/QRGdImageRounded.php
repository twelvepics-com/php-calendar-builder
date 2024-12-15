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

namespace App\Utils\QrCode;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QROptions;
use chillerlan\Settings\SettingsContainerInterface;
use GdImage;
use LogicException;

/**
 * Class QRGdImageRounded
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-12-14)
 * @since 0.1.0 (2024-12-14) First version.
 */
class QRGdImageRounded extends QRGdImagePNG
{
    /**
     * @throws QRCodeOutputException
     */
    public function __construct(
        private readonly SettingsContainerInterface|QROptions $qrOptions,
        QRMatrix $matrix
    )
    {
        $this->init();

        parent::__construct($this->qrOptions, $matrix);
    }

    private function init(): void
    {
    }

    /**
     * Overwrite module builder.
     *
     * @throws QRCodeOutputException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function module(int $x, int $y, int $M_TYPE): void
    {
        if (!$this->image instanceof GdImage) {
            throw new LogicException('Unable to load image');
        }

        /**
         * The bit order (starting from 0):
         *
         *   0 1 2
         *   7 # 3
         *   6 5 4
         */
        $neighbours = $this->matrix->checkNeighbours($x, $y);

        $posX1 = ($x * $this->scale);
        $posY1 = ($y * $this->scale);
        $posX2 = (($x + 1) * $this->scale);
        $posY2 = (($y + 1) * $this->scale);
        $rectangleSize = (int) ($this->scale / 2);

        $light = $this->getModuleValue($M_TYPE);

        if (!is_int($light)) {
            throw new LogicException('Invalid image color.');
        }

        $dark = $this->getModuleValue($M_TYPE | QRMatrix::IS_DARK);

        if (!is_int($dark)) {
            throw new LogicException('Invalid image color.');
        }

        /**
         * ------------------
         * Outer rounding
         * ------------------
         */
        if (($neighbours & (1 << 7))) { // neighbour left
            // top left
            imagefilledrectangle($this->image, $posX1, $posY1, ($posX1 + $rectangleSize), ($posY1 + $rectangleSize), $light);
            // bottom left
            imagefilledrectangle($this->image, $posX1, ($posY2 - $rectangleSize), ($posX1 + $rectangleSize), $posY2, $light);
        }

        if(($neighbours & (1 << 3))){ // neighbour right
            // top right
            imagefilledrectangle($this->image, ($posX2 - $rectangleSize), $posY1, $posX2, ($posY1 + $rectangleSize), $light);
            // bottom right
            imagefilledrectangle($this->image, ($posX2 - $rectangleSize), ($posY2 - $rectangleSize), $posX2, $posY2, $light);
        }

        if(($neighbours & (1 << 1))){ // neighbour top
            // top left
            imagefilledrectangle($this->image, $posX1, $posY1, ($posX1 + $rectangleSize), ($posY1 + $rectangleSize), $light);
            // top right
            imagefilledrectangle($this->image, ($posX2 - $rectangleSize), $posY1, $posX2, ($posY1 + $rectangleSize), $light);
        }

        if(($neighbours & (1 << 5))){ // neighbour bottom
            // bottom left
            imagefilledrectangle($this->image, $posX1, ($posY2 - $rectangleSize), ($posX1 + $rectangleSize), $posY2, $light);
            // bottom right
            imagefilledrectangle($this->image, ($posX2 - $rectangleSize), ($posY2 - $rectangleSize), $posX2, $posY2, $light);
        }

        // ---------------------
        // inner rounding
        // ---------------------

        if(!$this->matrix->check($x, $y)){

            if(($neighbours & 1) && ($neighbours & (1 << 7)) && ($neighbours & (1 << 1))){
                // top left
                imagefilledrectangle($this->image, $posX1, $posY1, ($posX1 + $rectangleSize), ($posY1 + $rectangleSize), $dark);
            }

            if(($neighbours & (1 << 1)) && ($neighbours & (1 << 2)) && ($neighbours & (1 << 3))){
                // top right
                imagefilledrectangle($this->image, ($posX2 - $rectangleSize), $posY1, $posX2, ($posY1 + $rectangleSize), $dark);
            }

            if(($neighbours & (1 << 7)) && ($neighbours & (1 << 6)) && ($neighbours & (1 << 5))){
                // bottom left
                imagefilledrectangle($this->image, $posX1, ($posY2 - $rectangleSize), ($posX1 + $rectangleSize), $posY2, $dark);
            }

            if(($neighbours & (1 << 3)) && ($neighbours & (1 << 4)) && ($neighbours & (1 << 5))){
                // bottom right
                imagefilledrectangle($this->image, ($posX2 - $rectangleSize), ($posY2 - $rectangleSize), $posX2, $posY2, $dark);
            }
        }

        imagefilledellipse(
            $this->image,
            (int)($x * $this->scale + $this->scale / 2),
            (int)($y * $this->scale + $this->scale / 2),
            ($this->scale - 1),
            ($this->scale - 1),
            $light,
        );
    }
}

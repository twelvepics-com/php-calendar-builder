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

namespace App\Calendar\ImageBuilder\Text;

use App\Tests\Unit\Calendar\ImageBuilder\Text\RowTest;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Row
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-02)
 * @since 0.1.0 (2023-12-02) First version.
 * @link RowTest
 */
readonly class Row
{
    /**
     * @param array<int, Text> $row
     */
    public function __construct(private array $row)
    {
    }

    /**
     * Returns the metrics of given text, font, font size and angle.
     *
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @return array{width: int, height: int, x: int, y: int, row: array<int, array{width: int, height: int, x: int, y: int}>}
     */
    #[ArrayShape(['width' => 'int', 'height' => 'int', 'x' => 'int', 'y' => 'int', 'row' => 'array'])]
    public function getMetrics(int $positionX = 0, int $positionY = 0, int $align = Align::LEFT, int $valign = Valign::BOTTOM): array
    {
        $width = 0;
        $height = 0;

        $row = [];

        foreach ($this->row as $text) {
            $dimension = $text->getMetrics($positionX, $positionY, $align, $valign);

            $width += $dimension['width'];
            $height = max($height, $dimension['height']);
        }

        $position = new Position($positionX, $positionY, $align, $valign);

        /* Get max y position. */
        $positionYOverall = 0;
        foreach ($this->row as $text) {
            ['y' => $currentPositionY] = $text->getMetrics($positionX, $positionY, $align, $valign);
            $positionYOverall = max($positionYOverall, $currentPositionY);
        }

        $positionXOverall = $position->getPositionX($width);
        foreach ($this->row as $text) {
            ['width' => $currentWidth, 'height' => $currentHeight] = $text->getMetrics($positionX, $positionY, $align, $valign);

            /* Alignment done with $positionXOverall: Align::LEFT */
            $positionCurrent = new Position($positionXOverall, $positionY, Align::LEFT, $valign);

            $row[] = [
                'width' => $currentWidth,
                'height' => $currentHeight,
                'x' => $positionCurrent->getPositionX($currentWidth),
                'y' => $positionYOverall,
                'text' => $text->getText(),
                'font' => $text->getFont(),
                'font-size' => $text->getFontSize(),
                'angle' => $text->getAngle(),
            ];

            $positionXOverall += $currentWidth;
        }

        return [
            'width' => $width,
            'height' => $height,
            'x' => $position->getPositionX($width),
            'y' => $position->getPositionY($height),
            'row' => $row,
        ];
    }
}

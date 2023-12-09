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

use App\Tests\Unit\Calendar\ImageBuilder\Text\RowsTest;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Rows
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-02)
 * @since 0.1.0 (2023-12-02) First version.
 * @link RowsTest
 */
readonly class Rows
{
    /**
     * @param array<int, Row> $rows
     */
    public function __construct(private array $rows, private int $rowDistance = 0)
    {
    }

    /**
     * Returns the metrics of given text, font, font size and angle.
     *
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @return array{width: int, height: int, rows: array<int, mixed>}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[ArrayShape(['width' => "int", 'height' => "int", 'x' => 'int', 'y' => 'int', 'rows' => "array"])]
    public function getMetrics(int $positionX = 0, int $positionY = 0, int $align = Align::LEFT, int $valign = Valign::BOTTOM): array
    {
        $width = 0;
        $height = 0;

        $rows = [];

        $positionYOverall = $positionY;
        foreach ($this->rows as $row) {
            $dimension = $row->getMetrics($positionX, $positionYOverall, $align, $valign);
            $positionYOverall += $dimension['height'] + $this->rowDistance;

            $width = max($width, $dimension['width']);
            $height += $dimension['height'];

            $rows[] = $dimension;
        }

        if (count($this->rows) > 1) {
            $height += (count($this->rows) - 1) * $this->rowDistance;
        }

        return [
            'width' => $width,
            'height' => $height,
            'x' => $positionX,
            'y' => $positionY,
            'rows' => $rows,
        ];
    }
}

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

namespace App\Tests\Unit\Calendar\ImageBuilder\Text;

use App\Calendar\ImageBuilder\Text\Align;
use App\Calendar\ImageBuilder\Text\Row;
use App\Calendar\ImageBuilder\Text\Text;
use App\Calendar\ImageBuilder\Text\Valign;
use PHPUnit\Framework\TestCase;

/**
 * Class RowTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2022-12-30)
 * @since 0.1.0 (2022-12-30) First version.
 * @link Row
 */
final class RowTest extends TestCase
{
    /**
     * Test wrapper for Row::getDimension.
     *
     * @dataProvider dataProvider
     *
     * @test
     * @testdox $number) Test Row::getDimension
     * @param int $number
     * @param Row $row
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @param array<string, int> $expected
     */
    public function wrapper(int $number, Row $row, int $positionX, int $positionY, int $align, int $valign, array $expected): void
    {
        /* Arrange */

        /* Act */
        $metrics = $row->getMetrics($positionX, $positionY, $align, $valign);

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertEquals($expected, $metrics);
    }

    /**
     * Data provider for Row::getDimension.
     *
     * @return array<int, array<int, mixed>>
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        $number = 0;

        return [
            /* Single Text */
            [++$number, new Row([
                new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 80,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1 = 'Text Text Text', $font1, $fontSize1, $angle1),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1 = 'AÄÀÁÅ OÖÒÓ UÜÙ', $font1, $fontSize1, $angle1),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],


            /* Alignment changes */
            [++$number, new Row([
                new Text($text1 = 'Text', $font1, $fontSize1, $angle1),
            ]), 200, 100, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 200,
                'y' => 100,
                'row' => [
                    [
                        'width' => 80,
                        'height' => 20,
                        'x' => 200,
                        'y' => 100,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
            ]), 200, 100, Align::CENTER, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 200 - 40,
                'y' => 100,
                'row' => [
                    [
                        'width' => 80,
                        'height' => 20,
                        'x' => 200 - 40,
                        'y' => 100,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1 = 24, $angle1),
            ]), 200, 100, Align::RIGHT, Valign::TOP, [
                'width' => 96,
                'height' => 24,
                'x' => 200 - 96,
                'y' => 100 + 24,
                'row' => [
                    [
                        'width' => 96,
                        'height' => 24,
                        'x' => 200 - 96,
                        'y' => 100 + 24,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                ],
            ]],


            /* 2 - Multiple Text */
            [++$number, new Row([
                new Text($text1 = 'Text ', $font1, $fontSize1 = 20, $angle1),
                new Text($text2 = 'Text Text Text', $font2 = 'Arial', $fontSize2 = 20, $angle2 = 0),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 380,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => 100,
                        'y' => 0,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2, $angle2),
            ]), 0, 0, Align::RIGHT, Valign::BOTTOM, [
                'width' => 380,
                'height' => 20,
                'x' => -380,
                'y' => 0,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => -380,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => -280,
                        'y' => 0,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2, $angle2),
            ]), 0, 0, Align::CENTER, Valign::TOP, [
                'width' => 380,
                'height' => 20,
                'x' => -190,
                'y' => 20,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => -190,
                        'y' => 20,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => -90,
                        'y' => 20,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                ],
            ]],


            /* 2 - Multiple Text with alignment and font size changes */
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2 = 24, $angle2),
            ]), 0, 0, Align::CENTER, Valign::TOP, [
                'width' => 436,
                'height' => 24,
                'x' => -218,
                'y' => 24,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => -218,
                        'y' => 24,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 336,
                        'height' => 24,
                        'x' => -118,
                        'y' => 24,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2, $angle2),
            ]), 0, 0, Align::CENTER, Valign::MIDDLE, [
                'width' => 436,
                'height' => 24,
                'x' => -218,
                'y' => 12,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => -218,
                        'y' => 12,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 336,
                        'height' => 24,
                        'x' => -118,
                        'y' => 12,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                ],
            ]],


            /* 3 - Multiple Text */
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2 = 'Text Text Text ', $font2, $fontSize2 = 20, $angle2),
                new Text($text3 = 'AÄÀÁÅ OÖÒÓ UÜÙ', $font3 = 'Arial', $fontSize3 = 20, $angle3 = 0),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 680,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 300,
                        'height' => 20,
                        'x' => 100,
                        'y' => 0,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                    [
                        'width' => 280,
                        'height' => 20,
                        'x' => 400,
                        'y' => 0,
                        'text' => $text3,
                        'font' => $font3,
                        'font-size' => $fontSize3,
                        'angle' => $angle3,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2 = 24, $angle2),
                new Text($text3, $font3, $fontSize3 = 16, $angle3),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 684,
                'height' => 24,
                'x' => 0,
                'y' => 0,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => 0,
                        'y' => 0,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 360,
                        'height' => 24,
                        'x' => 100,
                        'y' => 0,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                    [
                        'width' => 224,
                        'height' => 16,
                        'x' => 460,
                        'y' => 0,
                        'text' => $text3,
                        'font' => $font3,
                        'font-size' => $fontSize3,
                        'angle' => $angle3,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1, $angle1),
                new Text($text2, $font2, $fontSize2, $angle2),
                new Text($text3, $font3, $fontSize3, $angle3),
            ]), 200, 100, Align::LEFT, Valign::BOTTOM, [
                'width' => 684,
                'height' => 24,
                'x' => 200,
                'y' => 100,
                'row' => [
                    [
                        'width' => 100,
                        'height' => 20,
                        'x' => 200,
                        'y' => 100,
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => 360,
                        'height' => 24,
                        'x' => 200 + 100,
                        'y' => 100,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                    [
                        'width' => 224,
                        'height' => 16,
                        'x' => 200 + 460,
                        'y' => 100,
                        'text' => $text3,
                        'font' => $font3,
                        'font-size' => $fontSize3,
                        'angle' => $angle3,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1 = 20, $angle1),
                new Text($text2, $font2, $fontSize2 = 24, $angle2),
                new Text($text3, $font3, $fontSize3 = 16, $angle3),
            ]), $positionX = 200, $positionY = 100, Align::LEFT, Valign::MIDDLE, [
                'width' => $width = 684,
                'height' => $height = $fontSize2, // highest font size
                'x' => $positionX,
                'y' => $positionY + $height/2,
                'row' => [
                    [
                        'width' => $width1 = 100,
                        'height' => $fontSize1,
                        'x' => $positionX,
                        'y' => $positionY + (int) round($height / 2),
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => $width2 = 360,
                        'height' => $fontSize2,
                        'x' => $positionX + $width1,
                        'y' => $positionY + $height/2,
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                    [
                        'width' => 224,
                        'height' => $fontSize3,
                        'x' => $positionX + $width1 + $width2,
                        'y' => $positionY + $height/2,
                        'text' => $text3,
                        'font' => $font3,
                        'font-size' => $fontSize3,
                        'angle' => $angle3,
                    ],
                ],
            ]],
            [++$number, new Row([
                new Text($text1, $font1, $fontSize1 = 20, $angle1),
                new Text($text2, $font2, $fontSize2 = 24, $angle2),
                new Text($text3, $font3, $fontSize3 = 16, $angle3),
            ]), $positionX, $positionY, Align::RIGHT, Valign::MIDDLE, [
                'width' => $width,
                'height' => $height = $fontSize2, // highest font size
                'x' => $positionX - $width,
                'y' => $positionY + $height/2,
                'row' => [
                    [
                        'width' => $width1,
                        'height' => $fontSize1,
                        'x' => $positionX - $width,
                        'y' => $positionY + (int) round($height / 2),
                        'text' => $text1,
                        'font' => $font1,
                        'font-size' => $fontSize1,
                        'angle' => $angle1,
                    ],
                    [
                        'width' => $width2,
                        'height' => $fontSize2,
                        'x' => $positionX + $width1 - $width,
                        'y' => $positionY + (int) round($height / 2),
                        'text' => $text2,
                        'font' => $font2,
                        'font-size' => $fontSize2,
                        'angle' => $angle2,
                    ],
                    [
                        'width' => 224,
                        'height' => $fontSize3,
                        'x' => $positionX + $width1 + $width2 - $width,
                        'y' => $positionY + (int) round($height / 2),
                        'text' => $text3,
                        'font' => $font3,
                        'font-size' => $fontSize3,
                        'angle' => $angle3,
                    ],
                ],
            ]],
        ];
    }
}

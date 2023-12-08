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
use App\Calendar\ImageBuilder\Text\Rows;
use App\Calendar\ImageBuilder\Text\Text;
use App\Calendar\ImageBuilder\Text\Valign;
use PHPUnit\Framework\TestCase;

/**
 * Class RowsTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2022-12-30)
 * @since 0.1.0 (2022-12-30) First version.
 * @link Rows
 */
final class RowsTest extends TestCase
{
    /**
     * Test wrapper for Row::getDimension.
     *
     * @dataProvider dataProvider
     *
     * @test
     * @testdox $number) Test Row::getDimension
     * @param int $number
     * @param Rows $rows
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @param array<string, int> $expected
     */
    public function wrapper(int $number, Rows $rows, int $positionX, int $positionY, int $align, int $valign, array $expected): void
    {
        /* Arrange */

        /* Act */
        $metrics = $rows->getMetrics($positionX, $positionY, $align, $valign);

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
            [++$number, new Rows([
                new Row([
                    new Text($text = 'Text', $font = 'Arial', $fontSize = 20, $angle = 0),
                ])
            ], $distance = 0), $positionX = 0, $positionY = 0, Align::LEFT, Valign::BOTTOM, [
                'width' => $width = mb_strlen($text) * 20,
                'height' => $height = $fontSize,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width,
                        'height' => $height,
                        'x' => $positionX,
                        'y' => $positionY,
                        'row' => [
                            [
                                'width' => $width,
                                'height' => $height,
                                'x' => $positionX,
                                'y' => $positionY,
                                'text' => $text,
                                'font' => $font,
                                'font-size' => $fontSize,
                                'angle' => $angle,
                            ]
                        ]
                    ]
                ],
            ]],

            /* Two lines text */
            [++$number, new Rows([
                new Row([
                    new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
                ]),
                new Row([
                    new Text($text2 = 'Text', $font2 = 'Arial', $fontSize2 = 20, $angle2 = 0),
                ]),
            ]), $positionX, $positionY, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text1) * 20,
                'height' => $fontSize1  + $fontSize2 + $distance,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1 = mb_strlen($text1) * 20,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX,
                        'y' => $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $height1,
                                'x' => $positionX,
                                'y' => $positionY,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => mb_strlen($text2) * 20,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1,
                        'row' => [
                            [
                                'width' => $width,
                                'height' => $height2,
                                'x' => $positionX2,
                                'y' => $positionY2,
                                'text' => $text2,
                                'font' => $font2,
                                'font-size' => $fontSize2,
                                'angle' => $angle2,
                            ],
                        ],
                    ],
                ],
            ]],

//            /* Two lines text with distance */
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text', 'Arial', 20, 0),
//                ]),
//                new Row([
//                    new Text('Text', 'Arial', 20, 0),
//                ]),
//            ], 10), 0, 0, Align::LEFT, Valign::BOTTOM, [
//                'width' => 80,
//                'height' => 50,
//                'x' => 0,
//                'y' => 0,
//                'rows' => [],
//            ]],
//
//            /* Two lines text with distance and different text lengths */
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text', 'Arial', 20, 0),
//                ]),
//                new Row([
//                    new Text('Text Text', 'Arial', 20, 0),
//                ]),
//            ], 10), 0, 0, Align::LEFT, Valign::BOTTOM, [
//                'width' => 180,
//                'height' => 50,
//                'x' => 0,
//                'y' => 0,
//                'rows' => [],
//            ]],

        ];
    }
}

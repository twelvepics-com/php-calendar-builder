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
                'width' => max($width1 = mb_strlen($text1) * 20,  $width2 = mb_strlen($text2) * 20),
                'height' => $fontSize1 + $distance + $fontSize2,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $fontSize1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width2,
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
            [++$number, new Rows([
                new Row([
                    new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
                ]),
                new Row([
                    new Text($text2 = 'Text 123', $font2 = 'Arial', $fontSize2 = 20, $angle2 = 0),
                ]),
            ]), $positionX = 2, $positionY = 4, Align::LEFT, Valign::BOTTOM, [
                'width' => max($width1 = mb_strlen($text1) * $fontSize1,  $width2 = mb_strlen($text2) * $fontSize2),
                'height' => $fontSize1 + $fontSize2 + $distance,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $height1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width2,
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

            /* Two lines text with distance */
            [++$number, new Rows([
                new Row([
                    new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
                ]),
                new Row([
                    new Text($text2 = 'Text', $font2 = 'Arial', $fontSize2 = 20, $angle2 = 0),
                ]),
            ], $distance = 10), $positionX = 1, $positionY = 2, Align::LEFT, Valign::BOTTOM, [
                'width' => max($width1 = mb_strlen($text1) * $fontSize1,  $width2 = mb_strlen($text2) * $fontSize2),
                'height' => $fontSize1 + $distance + $fontSize2,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $height1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width2,
                                'height' => $height2,
                                'x' => $positionX2,
                                'y' => $positionY2,
                                'text' => $text2,
                                'font' => $font2,
                                'font-size' => $fontSize2,
                                'angle' => $angle2,
                            ],
                        ],
                    ]
                ],
            ]],

            /* Two lines text with distance and different text lengths */
            [++$number, new Rows([
                new Row([
                    new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
                ]),
                new Row([
                    new Text($text2 = 'Text Text', $font2 = 'Arial', $fontSize2 = 20, $angle2 = 0),
                ]),
            ], $distance = 10), $positionX = 200, $positionY = 300, Align::LEFT, Valign::BOTTOM, [
                'width' => max($width1 = mb_strlen($text1) * $fontSize1,  $width2 = mb_strlen($text2) * $fontSize2),
                'height' => $fontSize1 + $distance + $fontSize2,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $height1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width2,
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

            /* Two lines text with distance and different text length and text sizes */
            [++$number, new Rows([
                new Row([
                    new Text($text1 = 'Text', $font1 = 'Arial', $fontSize1 = 20, $angle1 = 0),
                ]),
                new Row([
                    new Text($text2 = 'Text Text', $font2 = 'Arial', $fontSize2 = 24, $angle2 = 0),
                ]),
            ], $distance = 10), $positionX = 10, $positionY = 20, Align::LEFT, Valign::BOTTOM, [
                'width' => max($width1 = mb_strlen($text1) * $fontSize1,  $width2 = mb_strlen($text2) * $fontSize2),
                'height' => $fontSize1 + $distance + $fontSize2,
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1 = $fontSize1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width1,
                                'height' => $height1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text1,
                                'font' => $font1,
                                'font-size' => $fontSize1,
                                'angle' => $angle1,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2 = $fontSize2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width2,
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

            /* Two lines text with more than one text */
            [++$number, new Rows([
                new Row([
                    new Text($text11 = 'Text ', $font11 = 'Arial', $fontSize11 = 20, $angle11 = 0),
                    new Text($text12 = 'Text', $font12 = 'Arial', $fontSize12 = 20, $angle12 = 0),
                ]),
                new Row([
                    new Text($text21 = 'Text Text ', $font21 = 'Arial', $fontSize21 = 24, $angle21 = 0),
                    new Text($text22 = 'Text Text', $font22 = 'Arial', $fontSize22 = 24, $angle22 = 0),
                ]),
            ], $distance = 10), $positionX = 0, $positionY = 0, Align::LEFT, Valign::BOTTOM, [
                'width' => max(
                    $width1 = ($width11 = mb_strlen($text11) * $fontSize11) + ($width12 = mb_strlen($text12) * $fontSize12),
                    $width2 = ($width21 = mb_strlen($text21) * $fontSize21) + ($width22 = mb_strlen($text22) * $fontSize22)),
                'height' => ($height1 = max($fontSize11, $fontSize12)) + $distance + ($height2 = max($fontSize21, $fontSize22)),
                'x' => $positionX,
                'y' => $positionY,
                'rows' => [
                    [
                        'width' => $width1,
                        'height' => $height1,
                        'x' => $positionX1 = $positionX,
                        'y' => $positionY1 = $positionY,
                        'row' => [
                            [
                                'width' => $width11,
                                'height' => $height1,
                                'x' => $positionX1,
                                'y' => $positionY1,
                                'text' => $text11,
                                'font' => $font11,
                                'font-size' => $fontSize11,
                                'angle' => $angle11,
                            ],
                            [
                                'width' => $width12,
                                'height' => $height1,
                                'x' => $positionX1 + $width11,
                                'y' => $positionY1,
                                'text' => $text12,
                                'font' => $font12,
                                'font-size' => $fontSize12,
                                'angle' => $angle12,
                            ],
                        ],
                    ],
                    [
                        'width' => $width2,
                        'height' => $height2,
                        'x' => $positionX2 = $positionX,
                        'y' => $positionY2 = $positionY + $height1 + $distance,
                        'row' => [
                            [
                                'width' => $width21,
                                'height' => $height2,
                                'x' => $positionX2,
                                'y' => $positionY2,
                                'text' => $text21,
                                'font' => $font21,
                                'font-size' => $fontSize21,
                                'angle' => $angle2,
                            ],
                            [
                                'width' => $width22,
                                'height' => $height2,
                                'x' => $positionX2 + $width21,
                                'y' => $positionY2,
                                'text' => $text22,
                                'font' => $font22,
                                'font-size' => $fontSize22,
                                'angle' => $angle22,
                            ],
                        ],
                    ],
                ],
            ]],
        ];
    }
}

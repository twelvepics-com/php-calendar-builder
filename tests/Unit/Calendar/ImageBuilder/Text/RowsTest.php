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
                    new Text('Text', 'Arial', 20, 0),
                ]),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 0,
                'y' => 0,
                'rows' => [
                    [
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
                                'text' => 'Text',
                                'font' => 'Arial',
                                'font-size' => 20,
                                'angle' => 0,
                            ]
                        ]
                    ]
                ],
            ]],

            /* Two lines text */
            [++$number, new Rows([
                new Row([
                    new Text('Text', 'Arial', 20, 0),
                ]),
                new Row([
                    new Text('Text', 'Arial', 20, 0),
                ]),
            ]), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 40,
                'x' => 0,
                'y' => 0,
                'rows' => [
                    [
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
                                'text' => 'Text',
                                'font' => 'Arial',
                                'font-size' => 20,
                                'angle' => 0,
                            ],
                        ],
                    ],
                    [
                        'width' => 80,
                        'height' => 20,
                        'x' => 20,
                        'y' => 0,
                        'row' => [
                            [
                                'width' => 80,
                                'height' => 20,
                                'x' => 20,
                                'y' => 0,
                                'text' => 'Text',
                                'font' => 'Arial',
                                'font-size' => 20,
                                'angle' => 0,
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

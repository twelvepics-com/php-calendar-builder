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

use App\Calendar\ImageBuilder\Text\Row;
use App\Calendar\ImageBuilder\Text\Rows;
use App\Calendar\ImageBuilder\Text\Text;
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
     * @param array<string, int> $expected
     */
    public function wrapper(int $number, Rows $rows, array $expected): void
    {
        /* Arrange */

        /* Act */
        $metrics = $rows->getMetrics();

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertEquals($expected, $metrics);
    }

    /**
     * Data provider for Row::getDimension.
     *
     * @return array<int, array<int, mixed>>
     */
    public function dataProvider(): array
    {
        $number = 0;

        return [
            /* Single Text */
            [++$number, new Rows([
                new Row([
                    new Text('Text', 'Arial', 20, 0),
                ])
            ]), [
                'width' => 80,
                'height' => 20,
                'rows' => [],
            ]],
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text Text Text', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 280,
//                'height' => 20,
//            ]],
//            [++$number, new Rows([
//                new Row([
//                    new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 280,
//                'height' => 20,
//            ]],
//            [++$number, new Rows([
//                new Row([
//                    new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0),
//                ])
//            ], 10), [
//                'width' => 280,
//                'height' => 20,
//            ]],
//
//            /* Multiple Text */
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text ', 'Arial', 20, 0),
//                    new Text('Text Text Text', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 380,
//                'height' => 20,
//            ]],
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text ', 'Arial', 20, 0),
//                    new Text('Text Text Text ', 'Arial', 20, 0),
//                    new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 680,
//                'height' => 20,
//            ]],
//
//            /* Multiple Rows */
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text ', 'Arial', 20, 0),
//                    new Text('Text Text Text', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 380,
//                'height' => 20,
//            ]],
//            [++$number, new Rows([
//                new Row([
//                    new Text('Text ', 'Arial', 20, 0),
//                    new Text('Text Text Text ', 'Arial', 20, 0),
//                    new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0),
//                ])
//            ]), [
//                'width' => 680,
//                'height' => 20,
//            ]],
        ];
    }
}

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
use App\Calendar\ImageBuilder\Text\Text;
use App\Calendar\ImageBuilder\Text\Valign;
use PHPUnit\Framework\TestCase;

/**
 * Class TextTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2022-12-30)
 * @since 0.1.0 (2022-12-30) First version.
 * @link Text
 */
final class TextTest extends TestCase
{
    /**
     * Test wrapper for Text::getDimension.
     *
     * @dataProvider dataProvider
     *
     * @test
     * @testdox $number) Test Text::getDimension
     * @param int $number
     * @param Text $text
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @param array<string, int> $expected
     */
    public function wrapper(int $number, Text $text, int $positionX, int $positionY, int $align, int $valign, array $expected): void
    {
        /* Arrange */

        /* Act */
        $metrics = $text->getMetrics($positionX, $positionY, $align, $valign);

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertEquals($expected, $metrics);
    }

    /**
     * Data provider for Text::getDimension.
     *
     * @return array<int, array<int, mixed>>
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        $number = 0;

        return [
            /* Default alignment */
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 0,
                'y' => 0,
            ]],
            [++$number, new Text('Text Text Text', 'Arial', 20, 0), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 0,
                'y' => 0,
            ]],
            [++$number, new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 0,
                'y' => 0,
            ]],


            /* Position */
            [++$number, new Text('Text', 'Arial', 20, 0), 200, 100, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 200,
                'y' => 100,
            ]],
            [++$number, new Text('Text Text Text', 'Arial', 20, 0), 200, 100, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 200,
                'y' => 100,
            ]],
            [++$number, new Text('AÄÀÁÅ OÖÒÓ UÜÙ', 'Arial', 20, 0), 200, 100, Align::LEFT, Valign::BOTTOM, [
                'width' => 280,
                'height' => 20,
                'x' => 200,
                'y' => 100,
            ]],


            /* Alignment */
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::LEFT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => 0,
                'y' => 0,
            ]],
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::CENTER, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => -40,
                'y' => 0,
            ]],
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::RIGHT, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => -80,
                'y' => 0,
            ]],


            /* Vertical alignment */
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::CENTER, Valign::BOTTOM, [
                'width' => 80,
                'height' => 20,
                'x' => -40,
                'y' => 0,
            ]],
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::CENTER, Valign::MIDDLE, [
                'width' => 80,
                'height' => 20,
                'x' => -40,
                'y' => 10,
            ]],
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::CENTER, Valign::TOP, [
                'width' => 80,
                'height' => 20,
                'x' => -40,
                'y' => 20,
            ]],


            /* Combination */
            [++$number, new Text('Text', 'Arial', 20, 0), 0, 0, Align::CENTER, Valign::MIDDLE, [
                'width' => 80,
                'height' => 20,
                'x' => -40,
                'y' => 10,
            ]],
            [++$number, new Text('Text', 'Arial', 20, 0), 200, 100, Align::CENTER, Valign::MIDDLE, [
                'width' => 80,
                'height' => 20,
                'x' => 200 - 40,
                'y' => 100 + 10,
            ]],
        ];
    }
}

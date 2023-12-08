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

use App\Calendar\ImageBuilder\Text\Metrics;
use PHPUnit\Framework\TestCase;

/**
 * Class MetricsTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2022-12-30)
 * @since 0.1.0 (2022-12-30) First version.
 * @link Metrics
 */
final class MetricsTest extends TestCase
{
    /**
     * Test wrapper for Metrics::getMetrics.
     *
     * @dataProvider dataProvider
     *
     * @test
     * @testdox $number) Test Metrics::getMetrics
     * @param int $number
     * @param string $text
     * @param string $font
     * @param int $fontSize
     * @param int $angle
     * @param array<string, int> $expected
     */
    public function wrapper(int $number, string $text, string $font, int $fontSize, int $angle, array $expected): void
    {
        /* Arrange */

        /* Act */
        $metrics = new Metrics();

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertEquals($expected, $metrics->getMetrics($text, $font, $fontSize, $angle));
    }

    /**
     * Data provider for Metrics::getMetrics.
     *
     * @return array<int, array<int, mixed>>
     */
    public function dataProvider(): array
    {
        $number = 0;

        return [
            [++$number, $text = 'Text', $font = 'Arial', $fontSize = 20, $angle = 0, [
                'width' => 80,
                'height' => 20,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, $text = 'Text Text Text', $font, $fontSize, $angle, [
                'width' => 280,
                'height' => 20,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, $text = 'AÄÀÁÅ OÖÒÓ UÜÙ', $font, $fontSize, $angle, [
                'width' => 280,
                'height' => 20,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
        ];
    }
}

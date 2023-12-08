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
            [++$number, new Text($text = 'Text', $font = 'Arial', $fontSize = 20, $angle = 0), $positionX = 0, $positionY = 0, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height = $fontSize,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text = 'Text Text Text', $font, $fontSize, $angle), $positionX, $positionY, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text = 'AÄÀÁÅ OÖÒÓ UÜÙ', $font, $fontSize, $angle), $positionX, $positionY, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],


            /* Position */
            [++$number, new Text($text = 'Text', $font, $fontSize, $angle), $positionX = 200, $positionY = 100, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text = 'Text Text Text', $font, $fontSize, $angle), $positionX, $positionY, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text = 'AÄÀÁÅ OÖÒÓ UÜÙ', $font, $fontSize, $angle), $positionX, $positionY, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],


            /* Alignment */
            [++$number, new Text($text = 'Text', $font, $fontSize, $angle), $positionX = 0, $positionY = 0, Align::LEFT, Valign::BOTTOM, [
                'width' => mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::CENTER, Valign::BOTTOM, [
                'width' => $width = mb_strlen($text) * 20,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::RIGHT, Valign::BOTTOM, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - $width,
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],


            /* Vertical alignment */
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::CENTER, Valign::BOTTOM, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::CENTER, Valign::MIDDLE, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY + (int) round($height / 2),
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::CENTER, Valign::TOP, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY + $height,
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],


            /* Combination */
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX, $positionY, Align::CENTER, Valign::MIDDLE, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY + (int) round($height / 2),
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
            [++$number, new Text($text, $font, $fontSize, $angle), $positionX = 200, $positionY = 100, Align::CENTER, Valign::MIDDLE, [
                'width' => $width,
                'height' => $height,
                'x' => $positionX - (int) round($width / 2),
                'y' => $positionY + (int) round($height / 2),
                'text' => $text,
                'font' => $font,
                'font-size' => $fontSize,
                'angle' => $angle,
            ]],
        ];
    }
}

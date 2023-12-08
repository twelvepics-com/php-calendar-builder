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

use App\Tests\Unit\Calendar\ImageBuilder\Text\TextTest;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Text
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-02)
 * @since 0.1.0 (2023-12-02) First version.
 * @link TextTest
 */
class Text
{
    /**
     * @param string $text
     * @param string $font
     * @param int $fontSize
     * @param int $angle
     * @param Metrics $metrics
     */
    public function __construct(protected string $text, protected string $font, protected int $fontSize, protected int $angle = 0, protected Metrics $metrics = new Metrics())
    {
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getFont(): string
    {
        return $this->font;
    }

    /**
     * @return int
     */
    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    /**
     * @return int
     */
    public function getAngle(): int
    {
        return $this->angle;
    }

    /**
     * @return int
     */
    public function getTextLength(): int
    {
        return mb_strlen($this->getText());
    }

    /**
     * Returns the metrics of given text, font, font size and angle.
     *
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     * @return array{width: int, height: int, x: int, y: int}
     */
    #[ArrayShape(['width' => 'int', 'height' => 'int', 'x' => 'int', 'y' => 'int', 'text' => 'string', 'font' =>'string', 'font-size' => 'int', 'angle' => 'int'])]
    public function getMetrics(int $positionX = 0, int $positionY = 0, int $align = Align::LEFT, int $valign = Valign::BOTTOM): array
    {
        ['width' => $width, 'height' => $height] = $this->metrics->getMetrics(
            $this->getText(),
            $this->getFont(),
            $this->getFontSize(),
            $this->getAngle()
        );

        $position = new Position($positionX, $positionY, $align, $valign);

        return [
            'width' => $width,
            'height' => $height,
            'x' => $position->getPositionX($width),
            'y' => $position->getPositionY($height),
            'text' => $this->getText(),
            'font' => $this->getFont(),
            'font-size' => $this->getFontSize(),
            'angle' => $this->getAngle(),
        ];
    }
}

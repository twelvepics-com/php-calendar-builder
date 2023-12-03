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

use App\Tests\Unit\Calendar\ImageBuilder\Text\MetricsTest;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Metrics
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-02)
 * @since 0.1.0 (2023-12-02) First version.
 * @link MetricsTest
 */
class Metrics
{
    /**
     * Returns the dimension of given text, font size and angle.
     *
     * @param string $text
     * @param string $font
     * @param int $fontSize
     * @param int $angle
     * @return array{width: int, height: int}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[ArrayShape(['width' => "int", 'height' => "int"])]
    public function getMetrics(string $text, string $font, int $fontSize, int $angle = 0): array
    {
        $height = (int) round($fontSize);
        $width = (int) round($fontSize * mb_strlen($text));

        return [
            'width' => $width,
            'height' => $height,
        ];
    }
}

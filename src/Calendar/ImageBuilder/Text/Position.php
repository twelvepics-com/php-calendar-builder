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

use LogicException;

/**
 * Class Position
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-02)
 * @since 0.1.0 (2023-12-02) First version.
 */
readonly class Position
{
    /**
     * @param int $positionX
     * @param int $positionY
     * @param int $align
     * @param int $valign
     */
    public function __construct(private int $positionX = 0, private int $positionY = 0, private int $align = Align::LEFT, private int $valign = Valign::BOTTOM)
    {
    }

    /**
     * Returns the position x according to given width.
     *
     * @param int $width
     * @return int
     */
    public function getPositionX(int $width): int
    {
        return match ($this->align) {
            /* | -> */
            Align::LEFT => $this->positionX,
            /* | ->   <- | */
            Align::CENTER => $this->positionX - intval(round($width / 2)),
            /* <- | */
            Align::RIGHT => $this->positionX - $width,

            default => throw new LogicException(sprintf('Invalid alignment "%s"', $this->align)),
        };
    }

    /**
     * Returns the position y according to given height.
     *
     * @param int $height
     * @return int
     */
    public function getPositionY(int $height): int
    {
        /* According to baseline */
        return match ($this->valign) {
            Valign::TOP => $this->positionY + $height,
            Valign::MIDDLE => $this->positionY + intval(round($height / 2)),
            Valign::BOTTOM => $this->positionY,
            default => throw new LogicException(sprintf('Invalid vertical alignment "%s"', $this->valign)),
        };
    }
}

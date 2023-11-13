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

namespace App\Calendar\Design\GdImage;

use App\Calendar\Design\GdImage\Base\GdImageBase;

/**
 * Class DesignBlank
 *
 * Creates the blank calendar design.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-10)
 * @since 0.1.0 (2023-11-10) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class GdImageBlank extends GdImageBase
{
    /**
     * @inheritdoc
     */
    public function doInit(): void
    {
    }

    /**
     * @inheritdoc
     */
    public function doBuild(): void
    {
        /* Add the main image */
        $this->addImage();
    }
}

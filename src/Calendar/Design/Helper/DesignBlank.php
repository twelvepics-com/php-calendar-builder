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

namespace App\Calendar\Design\Helper;

use App\Calendar\Design\Helper\Base\DesignHelperBase;

/**
 * Class DesignBlank
 *
 * Creates the blank calendar design. Shared between GdImage and Imagick libraries.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-13)
 * @since 0.1.0 (2023-11-13) First version.
 */
class DesignBlank extends DesignHelperBase
{
    /**
     * Do the main init for XXXDefault.php
     *
     * @inheritdoc 
     */
    public function doInit(): void
    {
    }

    /**
     * Do the main build for XXXDefault.php
     *
     * @inheritdoc
     */
    public function doBuild(): void
    {
        /* Add the main image */
        $this->designBase->addImage(0, 0, $this->designBase->getWidthTarget(), $this->designBase->getHeightTarget());
    }
}

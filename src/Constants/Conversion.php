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

namespace App\Constants;

/**
 * Class Conversion
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-29)
 * @since 0.1.0 (2023-12-29) First version.
 */
class Conversion
{
    final public const MILLISECONDS_TO_SECONDS = 1000;

    final public const BYTES_TO_KILOBYTES = 1024;

    final public const BYTES_TO_MEGABYTES = self::BYTES_TO_KILOBYTES ** 2;
}

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
 * Class ImageType
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-16)
 * @since 0.1.0 (2023-12-16) First version.
 */
class ImageType
{
    final public const SOURCE = 'source';

    final public const TARGET = 'target';

    final public const ALLOWED_IMAGE_TYPES = [self::SOURCE, self::TARGET];
}

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

namespace App\Constants\Parameter;

/**
 * Class Argument
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class Argument
{
    final public const CALENDAR = 'calendar';

    final public const CONFIG = 'config';

    final public const PATH = 'path';

    final public const IMAGE = 'image';

    final public const SOURCE = 'source';

    final public const TARGET = 'target';

    /* Holiday arguments */
    final public const COUNTRY = 'country';
    final public const STATE = 'state';
}

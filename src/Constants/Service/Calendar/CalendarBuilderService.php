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

namespace App\Constants\Service\Calendar;

/**
 * Class CalendarBuilderService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 */
class CalendarBuilderService
{
    final public const BIRTHDAY_YEAR_NOT_GIVEN = 2100;

    final public const ALIGN_LEFT = 1;

    final public const ALIGN_CENTER = 2;

    final public const ALIGN_RIGHT = 3;

    final public const VALIGN_TOP = 1;

    final public const VALIGN_BOTTOM = 2;

    final public const TARGET_HEIGHT = 4000;

    final public const DAY_SUNDAY = 0;

    final public const DAY_MONDAY = 1;

    final public const IMAGE_PNG = 'png';

    final public const IMAGE_JPG = 'jpg';

    final public const DEFAULT_QR_CODE_VERSION = 5;
}

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
    final public const CONFIG_FILENAME = 'config.yml';

    final public const PATH_CALENDAR_ABSOLUTE = '%s/data/calendar/%s';

    final public const PATH_CALENDAR_RELATIVE = 'data/calendar/%s';

    final public const PATH_CONFIG_RELATIVE = 'data/calendar/%s/'.self::CONFIG_FILENAME;

    final public const PATH_IMAGE_ABSOLUTE = self::PATH_CALENDAR_ABSOLUTE.'/%s';

    final public const PATH_IMAGE_RELATIVE = self::PATH_CALENDAR_RELATIVE.'/%s';

    final public const PATH_FONT_ABSOLUTE = '%s/data/font/%s';

    final public const PATH_EXAMPLE_RELATIVE = 'data/examples/%s';

    final public const PATH_IMAGES_READY = 'ready';

    final public const BIRTHDAY_YEAR_NOT_GIVEN = 2100;

    final public const ALIGN_LEFT = 1;

    final public const ALIGN_CENTER = 2;

    final public const ALIGN_RIGHT = 3;

    final public const VALIGN_TOP = 1;

    final public const VALIGN_MIDDLE = 2;

    final public const VALIGN_BOTTOM = 3;

    final public const TARGET_HEIGHT = 4000;

    final public const DAY_SUNDAY = 0;

    final public const DAY_MONDAY = 1;

    final public const IMAGE_PNG = 'png';

    final public const IMAGE_JPG = 'jpg';

    final public const IMAGE_JPEG = 'jpeg';

    final public const QR_CODE_VERSION_5 = 5;

    final public const QR_CODE_VERSION_6 = 6;

    final public const QR_CODE_VERSION_DEFAULT = self::QR_CODE_VERSION_5;

    final public const ERROR_BACKGROUND_COLOR = [47, 141, 171];

    final public const ERROR_TEXT_COLOR = [255, 255, 255];

    final public const ERROR_FONT_SIZE_FACTOR = 40;

    final public const ERROR_WIDTH = 6000;

    final public const ERROR_HEIGHT = 4000;

    final public const SALT = 'f7b5704d840f6b4f6b1ef4b3be39e2aa';
}

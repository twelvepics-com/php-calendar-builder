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

namespace App\Constants\Service\Photo;

/**
 * Class PhotoBuilderService
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-28)
 * @since 0.1.0 (2024-11-28) First version.
 */
class PhotoBuilderService
{
    final public const CONFIG_FILENAME = 'config.yml';

    final public const PATH_PHOTO_ABSOLUTE = '%s/data/photo/%s';

    final public const PATH_CALENDAR_RELATIVE = 'data/photo/%s';

    final public const PATH_CONFIG_RELATIVE = 'data/photo/%s/'.self::CONFIG_FILENAME;

    final public const PATH_IMAGE_ABSOLUTE = self::PATH_PHOTO_ABSOLUTE.'/%s';

    final public const PATH_IMAGE_RELATIVE = self::PATH_CALENDAR_RELATIVE.'/%s';
}

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

use Ixnode\PhpContainer\Image;

/**
 * Class Format
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-15)
 * @since 0.1.0 (2023-12-15) First version.
 */
class Format
{
    final public const HTML = 'html';

    final public const JSON = 'json';

    final public const ALLOWED_IMAGE_FORMATS = [Image::FORMAT_JPG, Image::FORMAT_PNG];
}

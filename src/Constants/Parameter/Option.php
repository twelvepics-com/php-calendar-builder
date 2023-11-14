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
class Option
{
    /* Source options */
    final public const SOURCE = 'source';

    /* Target options */
    final public const MONTH = 'month';
    final public const YEAR = 'year';

    final public const TARGET = 'target';
    final public const PAGE_TITLE = 'page-title';
    final public const TITLE = 'title';
    final public const SUBTITLE = 'subtitle';
    final public const URL = 'url';
    final public const COORDINATE = 'coordinate';

    /* Output options */
    final public const OUTPUT_FORMAT = 'format';
    final public const OUTPUT_QUALITY = 'quality';

    /* Design options (default) */
    final public const DESIGN_ENGINE = 'design-engine';
    final public const DESIGN_TYPE = 'design-type';
    final public const DESIGN_CONFIG = 'design-config';

    /* Design options (via page) */
    final public const DESIGN_ENGINE_DEFAULT = 'design-engine-default';
    final public const DESIGN_TYPE_DEFAULT = 'design-type-default';
    final public const DESIGN_CONFIG_DEFAULT = 'design-config-default';
}

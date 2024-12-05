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

namespace App\Error;

use App\Error\Base\Error;

/**
 * Class ErrorFileNotExists
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-06)
 * @since 0.1.0 (2024-12-06) First version.
 */
class ErrorFileNotExists extends Error
{
    /**
     */
    public function __construct(string $file, string $type = null, string $additionalInfo = null)
    {
        $message = match (true) {
            is_null($type) => sprintf('File "%s" does not exists.', $file),
            default => sprintf('%s file "%s" does not exists.', ucfirst(strtolower($type)),  $file)
        };

        if (!is_null($additionalInfo)) {
            $message .= ' '.$additionalInfo;
        }

        parent::__construct($message);
    }
}

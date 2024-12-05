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

namespace App\Error\Base;

/**
 * Class Error
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-06)
 * @since 0.1.0 (2024-12-06) First version.
 */
abstract class Error
{
    /**
     */
    public function __construct(protected string $message)
    {
    }

    /**
     * Returns the error message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}

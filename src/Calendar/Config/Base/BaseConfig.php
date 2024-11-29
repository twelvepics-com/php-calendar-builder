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

namespace App\Calendar\Config\Base;

use App\Objects\Exif\ExifCoordinate;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;
use LogicException;

/**
 * Class BaseConfig
 *
 * The class for base configuration
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-28)
 * @since 0.1.0 (2024-11-28) First version.
 */
abstract class BaseConfig extends Json
{
    /**
     * Returns the google maps link from given image.
     *
     * @param string $imagePath
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function getTranslatedCoordinate(string $imagePath, array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (is_string($coordinate) && $coordinate !== 'auto') {
            return $coordinate;
        }

        $coordinate = (new ExifCoordinate($imagePath))->getCoordinate();

        if (is_null($coordinate)) {
            return null;
        }

        return sprintf('%s, %s', $coordinate->getLatitude(), $coordinate->getLongitude());
    }

    /**
     * Returns coordinate dms string.
     *
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function getCoordinateDms(array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (!is_string($coordinate) || $coordinate === 'auto') {
            return null;
        }

        $coordinate = (new Coordinate($coordinate));

        return sprintf('%s, %s', $coordinate->getLatitudeDMS(), $coordinate->getLongitudeDMS());
    }

    /**
     * Returns coordinate decimal string.
     *
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function getCoordinateDecimal(array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (!is_string($coordinate) || $coordinate === 'auto') {
            return null;
        }

        $coordinate = (new Coordinate($coordinate));

        return sprintf('%s, %s', $coordinate->getLatitudeDecimal(), $coordinate->getLongitudeDecimal());
    }

    /**
     * Returns the google maps link from given image.
     *
     * @param array<int|string, mixed> $image
     * @return string|null
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    protected function getGoogleMapsLink(array $image): string|null
    {
        if (!array_key_exists('coordinate', $image)) {
            return null;
        }

        $coordinate = $image['coordinate'];

        if (is_string($coordinate) && $coordinate !== 'auto') {
            return (new Coordinate($coordinate))->getLinkGoogle();
        }

        return null;
    }

    /**
     * Strip the given string.
     *
     * @param string $string
     * @return string
     */
    protected function stripString(string $string): string
    {
        $string = strip_tags($string);

        $string = preg_replace('~ +~', ' ', $string);

        if (!is_string($string)) {
            throw new LogicException('Unable to replace subtitle string.');
        }

        return $string;
    }
}

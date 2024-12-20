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

namespace App\Objects\Exif;

use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\Parser\ParserException;

/**
 * Class ExifCoordinate
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-25)
 * @since 0.1.0 (2023-11-25) First version.
 */
class ExifCoordinate
{
    private Coordinate|null $coordinate = null;

    /**
     * @param string $path
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function __construct(protected string $path)
    {
        $this->init();
    }

    /**
     * Returns the coordinate.
     *
     * @return Coordinate|null
     */
    public function getCoordinate(): Coordinate|null
    {
        return $this->coordinate;
    }

    /**
     * Calculates a given string.
     *
     * @param string $value
     * @return int|float
     */
    private function calculateString(string $value): int|float
    {
        $matches = [];

        if (!preg_match('~(\-?[0-9]+)(/)([0-9]+)+~', $value, $matches)) {
            return intval($value);
        }

        return intval($matches[1]) / (intval(intval($matches[3]) === 0 ? 1 : $matches[3]));
    }

    /**
     * @param string[] $coordinate
     * @param string $ref
     * @return string
     */
    protected function extractCoordinate(array $coordinate, string $ref): string
    {
        $value1 = $this->calculateString($coordinate[0]);
        $value2 = $this->calculateString($coordinate[1]);
        $value3 = $this->calculateString($coordinate[2]);

        /* value3 given with value2 (from decimal points) */
        if (gettype($value2) === 'double') {
            $value3 += 60 * ($value2 - floor($value2));
        }

        return sprintf('%d°%d′%.4f″%s', $value1, $value2, $value3, $ref);
    }

    /**
     * Initializes the coordinate holder.
     *
     * @return void
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    private function init(): void
    {
        $dataExif = @exif_read_data($this->path, 'EXIF');

        if ($dataExif === false) {
            return;
        }

        if (!array_key_exists('GPSLatitude', $dataExif) || !array_key_exists('GPSLongitude', $dataExif)) {
            return;
        }

        $latitude = $this->extractCoordinate($dataExif['GPSLatitude'], $dataExif['GPSLatitudeRef']);
        $longitude = $this->extractCoordinate($dataExif['GPSLongitude'], $dataExif['GPSLongitudeRef']);

        $this->coordinate = new Coordinate($latitude, $longitude);
    }
}

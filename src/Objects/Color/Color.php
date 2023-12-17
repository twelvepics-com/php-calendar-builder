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

namespace App\Objects\Color;

use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use LogicException;

/**
 * Class Color
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-17)
 * @since 0.1.0 (2023-12-17) First version.
 */
class Color
{
    private const COLOR_COUNT = 5;

    /** @var array<int, string> */
    private array $mainColors = [];

    /**
     * @param string $path
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    public function __construct(protected string $path)
    {
        $this->init();
    }

    /**
     * @return array<int, string>
     */
    public function getMainColors(): array
    {
        return $this->mainColors;
    }

    /**
     * Initializes the properties.
     *
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function init(): void
    {
        $image = new Image(new File($this->path));

        $imageString = $image->getImageString(100);

        if (is_null($imageString)) {
            throw new LogicException('Unable to get the image string.');
        }

        $gdImage = imagecreatefromstring($imageString);

        if ($gdImage === false) {
            throw new LogicException('Unable to create the GD image.');
        }

        $palette = Palette::createPaletteFromGdImage($gdImage);

        $colorDetector = new ColorDetectorCiede2000($palette);

        $colors = $colorDetector->extract(self::COLOR_COUNT);

        $this->mainColors = [];
        foreach ($colors as $color) {
            $this->mainColors[] = ColorConverter::convertIntToHex($color);
        }
    }
}

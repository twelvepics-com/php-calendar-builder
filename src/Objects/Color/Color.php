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
     * Returns the color config path.
     *
     * @return string
     */
    private function getColorsConfigPath(): string
    {
        return sprintf('%s/%s.%s', dirname($this->path), pathinfo($this->path, PATHINFO_FILENAME), 'cnf');
    }

    /**
     * Read the main colors from config file.
     *
     * @param string $configPath
     * @return array<int, string>|null
     */
    private function readColorConfig(string $configPath): array|null
    {
        if (!file_exists($configPath) || (filemtime($this->path) > filemtime($configPath))) {
            return null;
        }

        $serializedData = file_get_contents($configPath);

        if ($serializedData === false) {
            throw new LogicException('Unable to read colors config file.');
        }

        $data = unserialize($serializedData);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Writes the main colors to config file.
     *
     * @param string $configPath
     * @param array<int, string> $mainColors
     * @return void
     */
    private function writeColorConfig(string $configPath, array $mainColors): void
    {
        file_put_contents($configPath, serialize($mainColors));
    }

    /**
     * Extracts the main colors from the image.
     *
     * @return array<int, string>
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function extractMainColorsFromImage(): array
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

        $mainColors = [];
        foreach ($colors as $color) {
            $mainColors[] = ColorConverter::convertIntToHex($color);
        }

        return $mainColors;
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
        $configPath = $this->getColorsConfigPath();

        $mainColors = $this->readColorConfig($configPath);

        /* Read the main colors from config file. */
        if (!is_null($mainColors)) {
            $this->mainColors = $mainColors;
            return;
        }

        $mainColors = $this->extractMainColorsFromImage();

        $this->writeColorConfig($configPath, $mainColors);

        $this->mainColors = $mainColors;
    }
}

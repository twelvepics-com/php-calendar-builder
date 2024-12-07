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

namespace App\Objects\Image;

use Exception;
use ImagickException;
use Ixnode\PhpCliImage\CliImage;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpSizeByte\SizeByte;
use LogicException;

/**
 * Class Image
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 */
class Image
{
    /**
     * @param string $projectDir
     */
    public function __construct(protected string $projectDir)
    {
    }

    final public const CLI_IMAGE_WIDTH = 120;

    private string|null $pathAbsolute = null;

    private string|null $pathRelative = null;

    private int $width;

    private int $height;

    private string $mimeType;

    private int $sizeByte;

    private string $sizeHuman;

    private string $type;

    private string $imageString;

    /**
     * @return string|null
     */
    public function getPathAbsolute(): string|null
    {
        return $this->pathAbsolute;
    }

    /**
     * @param string|null $pathAbsolute
     * @return self
     */
    public function setPathAbsolute(string|null $pathAbsolute): self
    {
        if (is_null($pathAbsolute)) {
            $this->pathAbsolute = null;
            $this->pathRelative = null;
            return $this;
        }

        $this->pathAbsolute = $pathAbsolute;

        $pathRelative = preg_replace('~^'.$this->projectDir.'/~', '', $pathAbsolute);

        if (!is_string($pathRelative)) {
            throw new LogicException('Unable to build relative path.');
        }

        $this->setPathRelative($pathRelative);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPathRelative(): string|null
    {
        return $this->pathRelative;
    }

    /**
     * @param string $pathRelative
     * @return self
     */
    public function setPathRelative(string $pathRelative): self
    {
        $this->pathRelative = $pathRelative;

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     * @return self
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int $height
     * @return self
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     * @return self
     */
    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return int
     */
    public function getSizeByte(): int
    {
        return $this->sizeByte;
    }

    /**
     * @param int $sizeByte
     * @return self
     * @throws Exception
     */
    public function setSizeByte(int $sizeByte): self
    {
        $this->sizeByte = $sizeByte;

        $this->setSizeHuman((new SizeByte($sizeByte))->getHumanReadable());

        return $this;
    }

    /**
     * @return string
     */
    public function getSizeHuman(): string
    {
        return $this->sizeHuman;
    }

    /**
     * @param string $sizeHuman
     * @return self
     */
    public function setSizeHuman(string $sizeHuman): self
    {
        $this->sizeHuman = $sizeHuman;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageString(): string
    {
        return $this->imageString;
    }

    /**
     * @param string $imageString
     * @return self
     */
    public function setImageString(string $imageString): self
    {
        $this->imageString = $imageString;

        return $this;
    }

    /**
     * @return CliImage
     * @throws CaseUnsupportedException
     * @throws ImagickException
     */
    public function getCliImage(): CliImage
    {
        return new CliImage(
            image: $this->imageString,
            width: self::CLI_IMAGE_WIDTH,
            engineType: CliImage::ENGINE_IMAGICK
        );
    }
}

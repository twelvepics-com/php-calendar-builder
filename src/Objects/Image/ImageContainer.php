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

/**
 * Class ImageContainer
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-09)
 * @since 0.1.0 (2023-11-09) First version.
 */
class ImageContainer
{
    final public const TYPE_SOURCE ='source';

    final public const TYPE_TARGET = 'target';

    private Image $source;

    private Image $target;

    /**
     * @return Image
     */
    public function getSource(): Image
    {
        return $this->source;
    }

    /**
     * @param Image $source
     * @return self
     */
    public function setSource(Image $source): self
    {
        $this->source = $source->setType(self::TYPE_SOURCE);

        return $this;
    }

    /**
     * @return Image
     */
    public function getTarget(): Image
    {
        return $this->target;
    }

    /**
     * @param Image $target
     * @return self
     */
    public function setTarget(Image $target): self
    {
        $this->target = $target->setType(self::TYPE_TARGET);

        return $this;
    }
}

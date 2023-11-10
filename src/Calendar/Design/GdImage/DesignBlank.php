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

namespace App\Calendar\Design\GdImage;

use App\Calendar\Design\GdImage\Base\DesignBase;
use App\Objects\Image\ImageContainer;
use Exception;

/**
 * Class DesignDefault
 *
 * Creates the default calendar design.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2023-11-10)
 * @since 0.1.0 (2023-11-10) First version.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DesignBlank extends DesignBase
{
    /**
     * Prepare method.
     *
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->createImages();
        $this->calculateVariables();
    }

    /**
     * Calculate variables.
     *
     * @throws Exception
     */
    protected function calculateVariables(): void
    {
        $propertiesSource = getimagesize($this->pathSourceAbsolute);

        if ($propertiesSource === false) {
            throw new Exception(sprintf('Unable to get image size (%s:%d)', __FILE__, __LINE__));
        }

        $this->widthSource = $propertiesSource[0];
        $this->heightSource = $propertiesSource[1];
    }

    /**
     * Add image
     */
    protected function addImage(): void
    {
        imagecopyresampled($this->imageTarget, $this->imageSource, 0, 0, 0, 0, $this->width, $this->height, $this->widthSource, $this->heightSource);
    }

    /**
     * Builds the given source image to a calendar page.
     *
     * @return ImageContainer
     * @throws Exception
     */
    public function build(): ImageContainer
    {
        $source = $this->calendarBuilderService->getParameterSource();

        $this->pathSourceAbsolute = $source->getImage()->getPathReal();
        $this->pathTargetAbsolute = $this->getTargetPathFromSource($source->getImage());

        /* Init */
        $this->prepare();

        /* Add main image */
        $this->addImage();

        /* Write image */
        $this->writeImage();

        /* Destroy image */
        $this->destroy();

        return (new ImageContainer())
            ->setSource($this->getImageProperties($this->pathSourceAbsolute))
            ->setTarget($this->getImageProperties($this->pathTargetAbsolute))
        ;
    }
}

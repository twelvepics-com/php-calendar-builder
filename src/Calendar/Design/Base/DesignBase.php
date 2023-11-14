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

namespace App\Calendar\Design\Base;

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use Ixnode\PhpContainer\Json;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract class DesignHelperBase
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-14)
 * @since 0.1.0 (2023-11-14) First version.
 */
abstract class DesignBase
{
    protected BaseImageBuilder $imageBuilder;

    protected KernelInterface $appKernel;

    protected Json|null $config = null;

    /**
     * Do the main init for XXXDefault.php
     */
    abstract public function doInit(): void;

    /**
     * Do the main build for XXXDefault.php
     */
    abstract public function doBuild(): void;

    /**
     * @param BaseImageBuilder $imageBuilder
     * @return self
     */
    public function setImageBuilder(BaseImageBuilder $imageBuilder): self
    {
        $this->imageBuilder = $imageBuilder;

        return $this;
    }

    /**
     * @param KernelInterface $appKernel
     * @return self
     */
    public function setAppKernel(KernelInterface $appKernel): self
    {
        $this->appKernel = $appKernel;

        return $this;
    }

    /**
     * @param Json|null $config
     * @return self
     */
    public function setConfig(?Json $config): self
    {
        $this->config = $config;

        return $this;
    }
}

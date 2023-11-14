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

use App\Calendar\Design\GdImage\Base\GdImageBase;
use App\Calendar\Design\Helper\Base\DesignHelperBase;
use App\Calendar\Design\Helper\DesignDefaultJTAC;
use Exception;
use Ixnode\PhpContainer\Json;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class GdImageDefaultJTAC
 *
 * Creates the default calendar design.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-11)
 * @since 0.1.0 (2023-11-11) First version.
 */
class GdImageDefaultJTAC extends GdImageBase
{
    protected DesignHelperBase $designHelper;

    /**
     * @param KernelInterface $appKernel
     * @param Json|null $config
     */
    public function __construct(protected KernelInterface $appKernel, protected Json|null $config = null)
    {
        $this->designHelper = new DesignDefaultJTAC($this, $appKernel, $config);

        parent::__construct($appKernel, $config);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function doInit(): void
    {
        $this->designHelper->doInit();
    }

    /**
     * @inheritdoc
     */
    public function doBuild(): void
    {
        $this->designHelper->doBuild();
    }
}

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

namespace App\Calendar\ImageBuilder;

use App\Calendar\Design\DesignDefault;
use App\Calendar\Design\DesignDefaultJTAC;
use App\Calendar\Design\DesignImage;
use App\Calendar\Design\DesignText;
use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Constants\Design;
use App\Constants\Parameter\Option;
use App\Objects\Parameter\ParameterWrapper;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;

/**
 * Abstract class ImageBuilderFactory
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-28)
 * @since 0.1.0 (2023-11-28) First version.
 */
readonly class ImageBuilderFactory
{
    /**
     * @param string $projectDir
     * @param ParameterWrapper $parameterWrapper
     */
    public function __construct(private string $projectDir, private ParameterWrapper $parameterWrapper)
    {
    }

    /**
     * Returns the image builder and the design according to the config.
     *
     * @param Json|null $config
     * @return BaseImageBuilder
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getImageBuilder(Json $config = null): BaseImageBuilder
    {
        $designEngine = null;
        $designType = null;
        $designConfigJson = null;

        if (!is_null($config)) {
            $designEngine = $config->getKeyString('engine');
            $designType = $config->getKeyString('type');
            $designConfigJson = $config->getKeyJson('config');
        }

        $designEngine ??= $this->getDesignEngine();
        $designType ??= $this->getDesignType();
        $designConfigJson ??= $this->getDesignConfig();

        return match ($designEngine) {
            'gdimage' => match ($designType) {
                Design::DEFAULT => new GdImageImageBuilder($this->projectDir, new DesignDefault(), $designConfigJson),
                Design::DEFAULT_JTAC => new GdImageImageBuilder($this->projectDir, new DesignDefaultJTAC(), $designConfigJson),
                Design::IMAGE => new GdImageImageBuilder($this->projectDir, new DesignImage(), $designConfigJson),
                Design::TEXT => new GdImageImageBuilder($this->projectDir, new DesignText(), $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            'imagick' => match ($designType) {
                Design::DEFAULT => new ImageMagickImageBuilder($this->projectDir, new DesignDefault(), $designConfigJson),
                Design::DEFAULT_JTAC => new ImageMagickImageBuilder($this->projectDir, new DesignDefaultJTAC(), $designConfigJson),
                Design::IMAGE => new ImageMagickImageBuilder($this->projectDir, new DesignImage(), $designConfigJson),
                Design::TEXT => new ImageMagickImageBuilder($this->projectDir, new DesignText(), $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            default => throw new LogicException(sprintf('Unsupported design engine "%s" was given.', $designEngine)),
        };
    }

    /**
     * Returns the design engine.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getDesignEngine(): string
    {
        $engine = match (true) {
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_ENGINE) => $this->parameterWrapper->getOptionFromConfig(Option::DESIGN_ENGINE),
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT) => $this->parameterWrapper->getOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT),
            default => 'imagick',
        };

        if (!is_string($engine)) {
            return 'imagick';
        }

        return $engine;
    }

    /**
     * Returns the design type.
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getDesignType(): string
    {
        $type = match (true) {
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_TYPE) => $this->parameterWrapper->getOptionFromConfig(Option::DESIGN_TYPE),
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => $this->parameterWrapper->getOptionFromConfig(Option::DESIGN_TYPE_DEFAULT),
            default => Design::DEFAULT,
        };

        if (!is_string($type)) {
            return Design::DEFAULT;
        }

        return $type;
    }

    /**
     * Returns the design configuration.
     *
     * @return Json|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getDesignConfig(): Json|null
    {
        $designConfigSource = match (true) {
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_TYPE) => Option::DESIGN_CONFIG,
            $this->parameterWrapper->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => Option::DESIGN_CONFIG_DEFAULT,
            default => null,
        };

        $designConfig = match (true) {
            is_null($designConfigSource) => throw new LogicException('Invalid design config.'),
            $this->parameterWrapper->hasOptionFromConfig($designConfigSource) => $this->parameterWrapper->getOptionFromConfigAsArray($designConfigSource),
            default => null,
        };

        return match (true) {
            is_array($designConfig) => new Json($designConfig),
            default => null,
        };
    }
}

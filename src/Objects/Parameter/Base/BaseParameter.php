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

namespace App\Objects\Parameter\Base;

use App\Calendar\Design\DesignImage;
use App\Calendar\Design\DesignText;
use App\Calendar\Design\DesignDefault;
use App\Calendar\Design\DesignDefaultJTAC;
use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Calendar\ImageBuilder\GdImageImageBuilder;
use App\Calendar\ImageBuilder\ImageMagickImageBuilder;
use App\Constants\Design;
use App\Constants\Parameter\Argument;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract class BaseParameter
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-08)
 * @since 0.1.0 (2023-11-08) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseParameter
{
    /* Constants. */
    final public const CONFIG_NAME = 'config.yml';

    protected const URL_TWELVEPICS_LIST = 'https://c.twelvepics.com/l/%s';

    protected const URL_TWELVEPICS_DETAIL = 'https://c.twelvepics.com/d/%s/%d';

    private ParameterWrapper $parameterWrapper;

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(
        protected readonly KernelInterface $appKernel
    )
    {
    }

    /**
     * Returns the parameter wrapper.
     *
     * @return ParameterWrapper
     */
    public function getParameterWrapper(): ParameterWrapper
    {
        return $this->parameterWrapper;
    }

    /**
     * Sets the parameter wrapper.
     *
     * @param ParameterWrapper $parameterWrapper
     * @return $this
     */
    public function setParameterWrapper(ParameterWrapper $parameterWrapper): self
    {
        $this->parameterWrapper = $parameterWrapper;

        return $this;
    }

    /**
     * Returns the configuration.
     *
     * @return Json
     */
    public function getConfig(): Json
    {
        return $this->parameterWrapper->getConfig();
    }

    /**
     * Returns the year.
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->parameterWrapper->getYear();
    }

    /**
     * Returns the month.
     *
     * @return int
     */
    public function getMonth(): int
    {
        return $this->parameterWrapper->getMonth();
    }

    /**
     * Returns the page number according to the given year and month.
     *
     * @return int
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getPageNumber(): int
    {
        return $this->parameterWrapper->getPageNumber();
    }

    /**
     * Returns the source directory of the project.
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getSourceDirectory(InputInterface $input): string
    {
        $source = $input->getArgument(Argument::SOURCE);

        if (!is_string($source)) {
            throw new LogicException(sprintf('Invalid argument for source "%s".', Argument::SOURCE));
        }

        $sourceDirectory = pathinfo($source, PATHINFO_DIRNAME);

        if (!is_string($sourceDirectory)) {
            throw new LogicException(sprintf('Invalid argument for source "%s".', Argument::SOURCE));
        }

        return $sourceDirectory;
    }

    /**
     * Returns the identifier from the image.
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getIdentifier(InputInterface $input): string
    {
        $sourcePath = $input->getArgument(Argument::SOURCE);

        if (!is_string($sourcePath)) {
            throw new LogicException(sprintf('Invalid argument for source "%s".', Argument::SOURCE));
        }

        $configGiven = is_dir($sourcePath) || basename($sourcePath) === self::CONFIG_NAME;

        if (!$configGiven) {
            throw new LogicException('Only given config.yml is supported yet.');
        }

        return basename(dirname($sourcePath));
    }

    /**
     * Returns the target path from the image.
     *
     * @param InputInterface $input
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getTargetPath(InputInterface $input): string
    {
        $sourceDirectory = $this->getSourceDirectory($input);

        if (!$this->parameterWrapper->hasOptionFromConfig(Option::TARGET)) {
            throw new LogicException(sprintf('Missing option "%s".', Option::TARGET));
        }

        $target = $this->parameterWrapper->getOptionFromConfig(Option::TARGET);

        if (!is_string($target)) {
            throw new LogicException(sprintf('Invalid value for option "%s".', Option::TARGET));
        }

        return sprintf('%s/%s', $sourceDirectory, $target);
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
    public function getDesignEngine(): string
    {
        $engine = match (true) {
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_ENGINE) => $this->getParameterWrapper()->getOptionFromConfig(Option::DESIGN_ENGINE),
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT) => $this->getParameterWrapper()->getOptionFromConfig(Option::DESIGN_ENGINE_DEFAULT),
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
    public function getDesignType(): string
    {
        $type = match (true) {
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_TYPE) => $this->getParameterWrapper()->getOptionFromConfig(Option::DESIGN_TYPE),
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => $this->getParameterWrapper()->getOptionFromConfig(Option::DESIGN_TYPE_DEFAULT),
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
    public function getDesignConfig(): Json|null
    {
        $designConfigSource = match (true) {
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_TYPE) => Option::DESIGN_CONFIG,
            $this->getParameterWrapper()->hasOptionFromConfig(Option::DESIGN_TYPE_DEFAULT) => Option::DESIGN_CONFIG_DEFAULT,
            default => null,
        };

        $designConfig = match (true) {
            is_null($designConfigSource) => throw new LogicException('Invalid design config.'),
            $this->getParameterWrapper()->hasOptionFromConfig($designConfigSource) => $this->getParameterWrapper()->getOptionFromConfigAsArray($designConfigSource),
            default => null,
        };

        return match (true) {
            is_array($designConfig) => new Json($designConfig),
            default => null,
        };
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
                Design::DEFAULT => new GdImageImageBuilder($this->appKernel, new DesignDefault(), $designConfigJson),
                Design::DEFAULT_JTAC => new GdImageImageBuilder($this->appKernel, new DesignDefaultJTAC(), $designConfigJson),
                Design::IMAGE => new GdImageImageBuilder($this->appKernel, new DesignImage(), $designConfigJson),
                Design::TEXT => new GdImageImageBuilder($this->appKernel, new DesignText(), $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            'imagick' => match ($designType) {
                Design::DEFAULT => new ImageMagickImageBuilder($this->appKernel, new DesignDefault(), $designConfigJson),
                Design::DEFAULT_JTAC => new ImageMagickImageBuilder($this->appKernel, new DesignDefaultJTAC(), $designConfigJson),
                Design::IMAGE => new ImageMagickImageBuilder($this->appKernel, new DesignImage(), $designConfigJson),
                Design::TEXT => new ImageMagickImageBuilder($this->appKernel, new DesignText(), $designConfigJson),
                default => throw new LogicException(sprintf('Unsupported design type "%s" for engine "%s" was given.', $designType, $designEngine)),
            },
            default => throw new LogicException(sprintf('Unsupported design engine "%s" was given.', $designEngine)),
        };
    }
}

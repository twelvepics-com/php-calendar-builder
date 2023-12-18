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

namespace App\Calendar\Config;

use App\Constants\Format;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 *
 * The class for calendar configuration
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-18)
 * @since 0.1.0 (2023-12-18) First version.
 */
class Config extends Json
{
    final public const CONFIG_FILENAME = 'config.yml';

    final public const PATH_CALENDAR_ABSOLUTE = '%s/data/calendar/%s';

    final public const PATH_CALENDAR_RELATIVE = 'data/calendar/%s';

    final public const PATH_CONFIG_ABSOLUTE = '%s/data/calendar/%s/'.self::CONFIG_FILENAME;

    final public const PATH_CONFIG_RELATIVE = 'data/calendar/%s/'.self::CONFIG_FILENAME;

    final public const ENDPOINT_CALENDAR_IMAGE = '/v/%s/0.%s';

    final public const ENDPOINT_CALENDAR = '/v/%s.%s';

    private string|null $error = null;

    /**
     * @param string $identifier
     * @param string $projectDir
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    public function __construct(private readonly string $identifier, private readonly string $projectDir)
    {
        $config = $this->getConfig();

        if ($config->hasKey('error')) {
            $this->error = $config->getKeyString('error');
        }

        parent::__construct($config->getArray());
    }

    /**
     * Returns true if an error occurred while loading the configuration.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Returns the title of the calendar. This is the title of the first page from the calendar.
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
    public function getCalendarName(): string
    {
        $path = ['name'];

        if (!$this->hasKey($path)) {
            return $this->identifier;
        }

        return $this->getKeyString($path);
    }

    /**
     * Returns the title of the calendar. This is the title of the first page from the calendar.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getCalendarTitle(): string|null
    {
        $path = ['pages', '0', 'title'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->stripString($this->getKeyString($path));
    }

    /**
     * Returns the subtitle of the calendar. This is the subtitle of the first page from the calendar.
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getCalendarSubtitle(): string|null
    {
        $path = ['pages', '0','subtitle'];

        if (!$this->hasKey($path)) {
            return null;
        }

        return $this->stripString($this->getKeyString($path));
    }

    /**
     * Returns the main calendar image for given identifier (calendar).
     *
     * @param string $format
     * @return string
     */
    public function getCalendarImageEndpoint(string $format = Image::FORMAT_JPG): string
    {
        return sprintf(self::ENDPOINT_CALENDAR_IMAGE, $this->identifier, $format);
    }

    /**
     * Returns the calendar image for given identifier (calendar).
     *
     * @param string $format
     * @return string
     */
    public function getCalendarEndpoint(string $format = Format::HTML): string
    {
        return sprintf(self::ENDPOINT_CALENDAR, $this->identifier, $format);
    }

    /**
     * Returns the absolute path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarPathAbsolute(): string
    {
        return sprintf(self::PATH_CALENDAR_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarPathRelative(): string
    {
        return sprintf(self::PATH_CALENDAR_RELATIVE, $this->identifier);
    }

    /**
     * Returns the absolute config path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarConfigAbsolute(): string
    {
        return sprintf(self::PATH_CONFIG_ABSOLUTE, $this->projectDir, $this->identifier);
    }

    /**
     * Returns the relative config path to the calendar directory.
     *
     * @return string
     */
    public function getCalendarConfigRelative(): string
    {
        return sprintf(self::PATH_CONFIG_RELATIVE, $this->identifier);
    }

    /**
     * Returns the public state of the calendar.
     *
     * @return bool
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function isPublic(): bool
    {
        $path = ['settings', 'public'];

        if (!$this->hasKey($path)) {
            return false;
        }

        return $this->getKeyBoolean($path);
    }

    /**
     * Returns the identifier of the calendar.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Reads the configuration file.
     *
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function getConfig(): Json
    {
        $pathCalendarAbsolute = sprintf(self::PATH_CALENDAR_ABSOLUTE, $this->projectDir, $this->identifier);

        if (!is_dir($pathCalendarAbsolute)) {
            return new Json(['error' => sprintf('Calendar path "%s" does not exist', $pathCalendarAbsolute)]);
        }

        $configFileRelative = new File(sprintf(self::PATH_CONFIG_RELATIVE, $this->identifier), $this->projectDir);

        if (!$configFileRelative->exist()) {
            return new Json(['error' => sprintf('Config path "%s" does not exist', $configFileRelative->getPath())]);
        }

        $configArray = Yaml::parse($configFileRelative->getContentAsText());

        if (!is_array($configArray)) {
            return new Json(['error' => sprintf('Config file "%s" is not an array', $configFileRelative->getPath())]);
        }

        return new Json($configArray);
    }

    /**
     * Strip the given string.
     *
     * @param string $string
     * @return string
     */
    private function stripString(string $string): string
    {
        $string = strip_tags($string);

        $string = preg_replace('~ +~', ' ', $string);

        if (!is_string($string)) {
            throw new LogicException('Unable to replace subtitle string.');
        }

        return $string;
    }
}

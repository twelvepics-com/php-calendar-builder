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

namespace App\Calendar\Structure;

use App\Constants\Service\Calendar\CalendarBuilderService;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CalendarStructure
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-07)
 * @since 0.1.0 (2023-12-07) First version.
 */
class CalendarStructure
{
    private const CALENDAR_DIRECTORY = '%s/data/calendar';

    private const PROJECT_DIRECTORY = '%s/data/calendar/%s';

    private const CONFIG_FILE = '%s/data/calendar/%s/config.yml';

    private readonly string $calendarDirectory;

    /**
     * @param KernelInterface $appKernel
     */
    public function __construct(protected KernelInterface $appKernel)
    {
        $this->calendarDirectory = sprintf(self::CALENDAR_DIRECTORY, $this->appKernel->getProjectDir());
    }

    /**
     * Returns all calendar paths, id's and names.
     *
     * @return array<int, array{identifier: string, path: string, config: string, url: string, name: string}>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    public function getCalendars(): array
    {
        $calendars = [];

        if (!is_dir($this->calendarDirectory)) {
            return $calendars;
        }

        $scanned = scandir($this->calendarDirectory);

        if ($scanned === false) {
            return $calendars;
        }

        $identifiers = array_filter($scanned, fn($element) => is_dir(sprintf('%s/%s', $this->calendarDirectory, $element)) && !in_array($element, ['.', '..']));

        foreach ($identifiers as $identifier) {
            $configPath = sprintf(self::CONFIG_FILE, $this->appKernel->getProjectDir(), $identifier);

            $config = new File($configPath);

            if (!$config->exist()) {
                throw new LogicException(sprintf('Config file "%s" does not exist.', $configPath));
            }

            $parsedConfig = Yaml::parse($config->getContentAsText());

            if (!is_array($parsedConfig)) {
                throw new LogicException(sprintf('Config file "%s" is not a valid YAML file.', $configPath));
            }

            $json = new Json($parsedConfig);

            $calendars[] = [
                'identifier' => $identifier,
                'path' => sprintf(self::PROJECT_DIRECTORY, $this->appKernel->getProjectDir(), $identifier),
                'config' => sprintf(self::CONFIG_FILE, $this->appKernel->getProjectDir(), $identifier),
                'url' => sprintf('/v/%s/all', $identifier),
                'name' => $json->hasKey('title') ? $json->getKeyString('title') : $identifier,
            ];
        }

        return $calendars;
    }

    /**
     * Returns the config for given identifier.
     *
     * @param string $identifier
     * @return Json
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    public function getConfig(string $identifier): Json
    {
        $pathCalendarAbsolute = sprintf(CalendarBuilderService::PATH_CALENDAR_ABSOLUTE, $this->appKernel->getProjectDir(), $identifier);

        if (!is_dir($pathCalendarAbsolute)) {
            return new Json(['error' => sprintf('Calendar path "%s" does not exist', $pathCalendarAbsolute)]);
        }

        $configFileRelative = new File(sprintf(CalendarBuilderService::PATH_CONFIG_RELATIVE, $identifier), $this->appKernel->getProjectDir());

        if (!$configFileRelative->exist()) {
            return new Json(['error' => sprintf('Config path "%s" does not exist', $configFileRelative->getPath())]);
        }

        $configArray = Yaml::parse($configFileRelative->getContentAsText());

        if (!is_array($configArray)) {
            return new Json(['error' => sprintf('Config file "%s" is not an array', $configFileRelative->getPath())]);
        }

        return new Json($configArray);
    }
}

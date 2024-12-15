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

namespace App\Command\Calendar;

use App\Command\Calendar\Base\BaseCalendarCommand;
use App\Constants\Parameter\Argument;
use App\Constants\Parameter\Option;
use Exception;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use Ixnode\PhpPublicHoliday\PublicHoliday;
use Ixnode\PhpTimezone\Constants\Locale;
use JsonException;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class HolidayCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-05)
 * @since 0.1.0 (2024-12-05) First version.
 * @example bin/console calendar:holiday e04916437c63 DE SN --locale=de_DE'
 * @example bin/console calendar:holiday e04916437c63 DE SN --locale=de_DE --year=2024'
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class HolidayCommand extends BaseCalendarCommand
{
    final public const COMMAND_NAME = 'calendar:holiday';

    final public const COMMAND_DESCRIPTION = 'Adds holidays to config.';

    private PublicHoliday $publicHoliday;

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            /* Default parameter and options. */
            ->addArgument(Argument::COUNTRY, InputArgument::REQUIRED, 'The country code for the public holidays.', null)
            ->addArgument(Argument::STATE, InputArgument::REQUIRED, 'The state code for the public holidays.', null)
            ->addOption(Option::LOCALE, 'i', InputOption::VALUE_OPTIONAL, 'The locale for the public holidays', Locale::DE_DE)

            /* Overwrite options. */
            ->addOption(Option::YEAR, 'y', InputOption::VALUE_OPTIONAL, 'The year for the public holidays', null)

            /* Help content. */
            ->setHelp(
                <<<'EOT'
The <info>calendar:holiday</info> adds holidays to config:
  <info>php %command.full_name%</info>
Adds holidays to config.
EOT
            );
    }

    /**
     * Returns the given country code (default parameter).
     */
    private function getCountryCode(): string
    {
        $countryCode = $this->input->getArgument(Argument::COUNTRY);

        if (!is_string($countryCode)) {
            throw new LogicException('Country code must be a string.');
        }

        return $countryCode;
    }

    /**
     * Returns the given state code (default parameter).
     */
    private function getStateCode(): string
    {
        $stateCode = $this->input->getArgument(Argument::STATE);

        if (!is_string($stateCode)) {
            throw new LogicException('State code must be a string.');
        }

        return $stateCode;
    }

    /**
     * Returns the given or default locale code (default option).
     */
    private function getLocaleCode(): string
    {
        $locale = $this->input->getOption(Option::LOCALE);

        if (!is_string($locale)) {
            throw new LogicException('Locale must be a string.');
        }

        return $locale;
    }

    /**
     * Returns the given or default year (overwrite option).
     */
    private function getYear(): int|null
    {
        $year = $this->input->getOption(Option::YEAR);

        if (is_null($year)) {
            return null;
        }

        if (!is_string($year) && !is_numeric($year)) {
            throw new LogicException('Year must be a integer.');
        }

        if ((string) (int) $year !== $year) {
            throw new LogicException('Year must be a integer.');
        }

        return (int) $year;
    }

    /**
     * Execute the commands.
     *
     * @inheritdoc
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws Exception
     */
    protected function doExecute(): int
    {
        $yamlPath = $this->config->getConfigPathRelative();

        $success = $this->doHoliday();

        if (!$success) {
            $this->output->writeln('Unable to write config file.');
        }

        $this->output->writeln(sprintf('Successfully written config file to %s', $yamlPath));
        return Command::SUCCESS;
    }

    /**
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws Exception
     */
    private function doHoliday(): bool
    {
        /* Default parameters. */
        $countryCode = $this->getCountryCode();
        $stateCode = $this->getStateCode();
        $locale = $this->getLocaleCode();

        /* Overwrite parameters. */
        $year = $this->getYear();

        $pathSettingsDefaultsYear = ['settings', 'defaults', 'year'];

        if (is_null($year)) {
            $year = match (true) {
                $this->config->hasKey($pathSettingsDefaultsYear) => $this->config->getKeyInteger($pathSettingsDefaultsYear),
                default => (int) date('Y'),
            };
        }

        if (!is_int($year)) {
            throw new LogicException('Unable to get calendar year from options or yaml config.');
        }

        $publicHolidays = $this->buildPublicHolidays(
            year: $year,
            countryCode: $countryCode,
            stateCode: $stateCode,
            localeCode: $locale,
        );

        $this->config->addValue('holidays', $publicHolidays->getArray());
        $this->config->addValue(['settings', 'holiday'], [
            'year' => $this->publicHoliday->getYear(),
            'locale-code' => $this->publicHoliday->getLocaleCode(),
            'language' => $this->publicHoliday->getLanguage(),
            'country-code' => $this->publicHoliday->getCountryCode(),
            'country-name' => $this->publicHoliday->getCountry(),
            'state-code' => $this->publicHoliday->getStateCode(),
            'state-name' => $this->publicHoliday->getState(),
        ]);

        /* Remove old holiday-config -> moved to settings.holiday */
        if ($this->config->hasKey('holiday-config')) {
            $this->config->deleteKey('holiday-config');
        }

        $success = $this->config->backupConfigFile();

        /* Unable to back up config file. */
        if (!$success) {
            throw new LogicException('Unable to backup config file.');
        }

        /* Write config file. */
        $success = $this->config->writeConfigFile(backupConfigFile: false);

        /* Unable to write config file. */
        if (!$success) {
            return false;
        }

        return true;
    }

    /**
     * Returns the built public holidays (from class PublicHoliday).
     *
     * @throws Exception
     */
    private function buildPublicHolidays(
        int $year,
        string $countryCode,
        string $stateCode,
        string $localeCode,
    ): Json
    {
        $this->publicHoliday = new PublicHoliday(
            year: $year,
            countryCode: $countryCode,
            stateCode: $stateCode,
            localeCode: $localeCode,
        );

        $publicHolidays = [];

        foreach ($this->publicHoliday->getHolidays() as $holiday) {
            $publicHolidays[$holiday->getDate()->format('Y-m-d')] = $holiday->getName();
        }

        return new Json($publicHolidays);
    }
}

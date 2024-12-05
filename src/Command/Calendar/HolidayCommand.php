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

use App\Constants\FileType;
use App\Constants\Parameter\Argument;
use App\Constants\Parameter\Option;
use App\Error\ErrorFileNotExists;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Class HolidayCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-05)
 * @since 0.1.0 (2024-12-05) First version.
 * @example bin/console calendar:holiday e04916437c63 DE SN --year=2024 --locale=de_DE'
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class HolidayCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:holiday';

    final public const COMMAND_DESCRIPTION = 'Adds holidays to config.';

    private const TEMPLATE_PATH_YAML = 'data/calendar/%s/config.yml';

    private const YAML_CONFIG_INLINE = 4;

    private const YAML_CONFIG_IDENT = 4;

    private PublicHoliday $publicHoliday;

    private OutputInterface $output;

    private InputInterface $input;

    /**
     * CreatePageCommand constructor
     *
     * @param string $projectDir
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    )
    {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(Argument::CALENDAR, InputArgument::REQUIRED, 'The id of the calendar.')
            ->addArgument(Argument::COUNTRY, InputArgument::REQUIRED, 'The country code for the public holidays.', null)
            ->addArgument(Argument::STATE, InputArgument::REQUIRED, 'The state code for the public holidays.', null)

            ->addOption(Option::YEAR, 'y', InputOption::VALUE_OPTIONAL, 'The year for the public holidays', null)
            ->addOption(Option::LOCALE, 'i', InputOption::VALUE_OPTIONAL, 'The locale for the public holidays', Locale::DE_DE)

            ->setHelp(
                <<<'EOT'
The <info>calendar:holiday</info> adds holidays to config:
  <info>php %command.full_name%</info>
Adds holidays to config.
EOT
            );
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        $yamlPath = $this->getYamlPath();

        if ($yamlPath instanceof ErrorFileNotExists) {
            $this->output->writeln($yamlPath->getMessage());
            return Command::FAILURE;
        }

        $success = $this->writeYamlFile($yamlPath);

        if (!$success) {
            $this->output->writeln('Unable to write YAML file.');
        }

        $this->output->writeln(sprintf('Successfully written YAML file to %s', $yamlPath));
        return Command::SUCCESS;
    }

    /**
     * Returns the yaml path.
     *
     * @return string|ErrorFileNotExists
     */
    private function getYamlPath(): string|ErrorFileNotExists
    {
        $calendar = $this->input->getArgument(Argument::CALENDAR);

        if (!is_string($calendar)) {
            throw new LogicException('Calendar must be a string.');
        }

        $yamlPathRelative = sprintf(self::TEMPLATE_PATH_YAML, $calendar);

        $yamlPathAbsolute = sprintf('%s/%s', $this->projectDir, $yamlPathRelative);

        if (!file_exists($yamlPathAbsolute)) {
            return new ErrorFileNotExists(
                file: $yamlPathAbsolute,
                type: FileType::YAML,
                additionalInfo: 'Please check your calendar id.'
            );
        }

        return $yamlPathAbsolute;
    }

    /**
     * Reads the yaml data.
     *
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function readYamlFile(string $yamlPath): Json
    {
        $data = Yaml::parseFile($yamlPath);

        if (!is_array($data)) {
            throw new LogicException('Unable to parse YAML string.');
        }

        return new Json($data);
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
    private function writeYamlFile(string $yamlPath): bool
    {
        $yamlConfig = $this->readYamlFile($yamlPath);

        $countryCode = $this->getCountryCode();
        $stateCode = $this->getStateCode();
        $year = $this->getYear();
        $locale = $this->getLocaleCode();

        $pathSettingsDefaultsYear = ['settings', 'defaults', 'year'];

        if (is_null($year)) {
            $year = match (true) {
                $yamlConfig->hasKey($pathSettingsDefaultsYear) => $yamlConfig->getKeyInteger($pathSettingsDefaultsYear),
                default => (int) date('Y'),
            };
        }

        if (!is_int($year)) {
            throw new LogicException('Unable to get calendar year from options or yaml config.');
        }

        $publicHolidays = $this->getPublicHolidays(
            year: $year,
            countryCode: $countryCode,
            stateCode: $stateCode,
            localeCode: $locale,
        );

        $yamlConfig->addValue('holidays', $publicHolidays->getArray());
        $yamlConfig->addValue('holiday-config', [
            'year' => $this->publicHoliday->getYear(),
            'locale-code' => $this->publicHoliday->getLocaleCode(),
            'language' => $this->publicHoliday->getLanguage(),
            'country-code' => $this->publicHoliday->getCountryCode(),
            'country-name' => $this->publicHoliday->getCountry(),
            'state-code' => $this->publicHoliday->getStateCode(),
            'state-name' => $this->publicHoliday->getState(),
        ]);

        $yamlData = Yaml::dump($yamlConfig->getArray(), self::YAML_CONFIG_INLINE, self::YAML_CONFIG_IDENT);

        /* Make backup of given file. */
        $backupFile = $yamlPath.'.~'.date('Y-m-d_H-i-s');
        $success = rename($yamlPath, $backupFile);

        if (!$success) {
            throw new LogicException('Unable to backup YAML file.');
        }

        /* Write new content to YAML path. */
        $state = file_put_contents($yamlPath, $yamlData);

        /* Unable to write YAML file. */
        if ($state === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns the public holidays.
     * @throws Exception
     */
    private function getPublicHolidays(
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

    /**
     * Returns the given country code.
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
     * Returns the given state code.
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
     * Returns the given or default year.
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
     * Returns the given or default locale code.
     */
    private function getLocaleCode(): string
    {
        $locale = $this->input->getOption(Option::LOCALE);

        if (!is_string($locale)) {
            throw new LogicException('Locale must be a string.');
        }

        return $locale;
    }
}

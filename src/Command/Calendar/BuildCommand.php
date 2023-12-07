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

use App\Calendar\Structure\CalendarStructure;
use App\Constants\Parameter\Argument;
use Exception;
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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BuildCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-11)
 * @since 0.1.0 (2023-11-11) First version.
 * @example bin/console calendar:build data/calendar/bcb37ef651a1814c091c8a24d8f550ee/config.yml
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class BuildCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:build';

    final public const COMMAND_DESCRIPTION = 'Builds all calendar pages';

    /**
     * @param CalendarStructure $calendarStructure
     * @param KernelInterface $appKernel
     * @param string|null $name
     */
    public function __construct(
        private readonly CalendarStructure $calendarStructure,
        protected KernelInterface $appKernel,
        string $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(Argument::CONFIG, InputArgument::OPTIONAL, 'The path to the config file.', null)
            ->setHelp(
                <<<'EOT'
The <info>calendar:build</info> creates all calendar pages:
  <info>php %command.full_name%</info>
Creates a calendar page.
EOT
            );
    }

    /**
     * Sets the config file.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    private function getConfigFile(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');

        $calendars = $this->calendarStructure->getCalendars();

        $calendarIdentifiers = array_map(fn(array $calendar): string => $calendar['path'], $calendars);
        $calendarDescriptions = array_map(fn(array $calendar): string => sprintf('%s: %s', $calendar['path'], $calendar['name']), $calendars);

        $output->writeln('');
        $question = new ChoiceQuestion(
            sprintf(
                '  Please select the calendar (defaults to 0):%s  → %s%s',
                PHP_EOL.PHP_EOL,
                implode(PHP_EOL.'  → ', $calendarDescriptions),
                PHP_EOL
            ),
            $calendarIdentifiers,
            0
        );

        if (!method_exists($helper, 'ask')) {
            throw new LogicException(sprintf('Helper "%s" does not have the "ask" method.', $helper::class));
        }

        $calendar = $helper->ask($input, $output, $question);

        return sprintf('data/calendar/%s/config.yml', $calendar);
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configString = $input->getArgument(Argument::CONFIG);

        /* Ask for config file. */
        if (is_null($configString)) {
            $configString = $this->getConfigFile($input, $output);
        }

        if (!is_string($configString)) {
            throw new LogicException('Unsupported config given.');
        }

        $config = new File($configString);

        if (!$config->exist()) {
            throw new LogicException(sprintf('Config file "%s" does not exist.', $configString));
        }

        $parsedConfig = Yaml::parse($config->getContentAsText());

        if (!is_array($parsedConfig)) {
            throw new LogicException(sprintf('Config file "%s" is not a valid YAML file.', $configString));
        }

        $json = new Json($parsedConfig);

        foreach ($json->getKeyArray('pages') as $page) {
            if (!is_array($page)) {
                throw new LogicException('Invalid configuration given (page must be an array).');
            }

            if (!array_key_exists('year', $page) || !array_key_exists('month', $page)) {
                throw new LogicException('Missing year and month in page.');
            }

            $year = $page['year'];
            $month = $page['month'];

            if (!is_int($year) || !is_int($month)) {
                throw new LogicException('Year and month in page must be an integer.');
            }

            $application = new Application($this->appKernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => PageBuildCommand::COMMAND_NAME,
                Argument::SOURCE => $configString,
                '--year' => (int) $year,
                '--month' => (int) $month,
            ]);

            $application->run($input, $output);
        }

        return Command::SUCCESS;
    }
}

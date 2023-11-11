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

use App\Constants\Parameter\Argument;
use Exception;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CreateCalenderCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-11)
 * @since 0.1.0 (2023-11-11) First version.
 * @example bin/console calendar:create-calendar data/calendar/bcb37ef651a1814c091c8a24d8f550ee/config.yml
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class CreateCalenderCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:create-calendar';

    final public const COMMAND_DESCRIPTION = 'Creates all calendar pages';

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(Argument::CONFIG, InputArgument::REQUIRED, 'The path to the config file.')
            ->setHelp(
                <<<'EOT'
The <info>calendar:create-calendar</info> creates all calendar pages:
  <info>php %command.full_name%</info>
Creates a calendar page.
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configString = $input->getArgument(Argument::CONFIG);

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

            $application = new Application($this->kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => CreatePageCommand::COMMAND_NAME,
                Argument::SOURCE => $configString,
                '--year' => (int) $year,
                '--month' => (int) $month,
            ]);

            $application->run($input, $output);
        }

        return Command::SUCCESS;
    }
}

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

use App\Constants\Parameter\Option;
use App\Constants\Service\Calendar\CalendarBuilderService;
use Exception;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PrepareCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-11)
 * @since 0.1.0 (2023-11-11) First version.
 * @example bin/console calendar:new
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class NewCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:new';

    final public const COMMAND_DESCRIPTION = 'Creates a new calendar from basic example';

    final public const CALENDAR_TYPE_QUOTE = 'quote';

    final public const CALENDAR_TYPE_SIMPLE = 'simple';

    final public const AVAILABLE_CALENDAR_TYPES = [
        self::CALENDAR_TYPE_QUOTE,
        self::CALENDAR_TYPE_SIMPLE,
    ];

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->addOption(Option::CALENDAR_TYPE, 'f', InputOption::VALUE_REQUIRED, sprintf('The calendar type (%s)', implode(', ', self::AVAILABLE_CALENDAR_TYPES)), self::CALENDAR_TYPE_SIMPLE)
            ->setHelp(
                <<<'EOT'
The <info>calendar:new</info> creates all calendar pages:
  <info>php %command.full_name%</info>
Creates a calendar page.
EOT
            );
    }

    /**
     * Returns the next available directory name.
     *
     * @param string $directoryName
     * @return string
     * @throws Exception
     */
    protected function getAvailableDirectoryName(string $directoryName): string
    {
        /* Iterate through directories until a non-existing one was found. */
        do {
            $md5 = substr(md5(random_int(100_000_000, 999_999_999).CalendarBuilderService::SALT), 0, 12);

            $directoryNameNew = sprintf($directoryName, $md5);
        } while (is_dir($directoryNameNew) || file_exists($directoryNameNew));

        return $directoryNameNew;
    }

    /**
     * Copy directory from source to target.
     *
     * @param string $source
     * @param string $target
     * @return void
     */
    private function copyDirectory(string $source, string $target): void
    {
        if (is_dir($source)) {
            if (!is_dir($target)) {
                mkdir($target, 0775, true);
            }

            $dir = opendir($source);

            if ($dir === false) {
                throw new LogicException('Unable to open source directory.');
            }

            while (false !== ($file = readdir($dir))) {
                /* Skip dot and dot-dot files. */
                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                $sourcePath = sprintf('%s/%s', $source, $file);
                $targetPath = sprintf('%s/%s', $target, $file);

                if ($file === 'ready' && is_dir($sourcePath)) {
                    mkdir($targetPath);
                    continue;
                }

                $this->copyDirectory($sourcePath, $targetPath);
            }

            closedir($dir);

            return;
        }

        copy($source, $target);
    }

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $calendarType = $input->getOption(Option::CALENDAR_TYPE);

        if (!is_string($calendarType)) {
            throw new LogicException('The calendar type must be a string.');
        }

        if (!in_array($calendarType, self::AVAILABLE_CALENDAR_TYPES, true)) {
            $output->writeln(sprintf('The calendar type "%s" is not available. Allowed: %s', $calendarType, implode(', ', self::AVAILABLE_CALENDAR_TYPES)));
            return Command::INVALID;
        }

        $source = sprintf(CalendarBuilderService::PATH_EXAMPLE_RELATIVE, $calendarType);

        $pathCalendarRelative = $this->getAvailableDirectoryName(CalendarBuilderService::PATH_CALENDAR_RELATIVE);
        $pathConfigFileRelative = sprintf('%s/%s', $pathCalendarRelative, CalendarBuilderService::CONFIG_FILENAME);
        $pathImagesReady = sprintf('%s/%s/*', $pathCalendarRelative, CalendarBuilderService::PATH_IMAGES_READY);

        $this->copyDirectory($source, $pathCalendarRelative);

        $output->writeln('');
        $output->writeln(sprintf('→ Directory "%s" was successfully created.', $pathCalendarRelative));
        $output->writeln('→ Got to this directory.');
        $output->writeln('→ Add your own images.');
        $output->writeln(sprintf('→ Edit the "%s" config file to your needs.', $pathConfigFileRelative));
        $output->writeln(sprintf('→ Build your calendar with: bin/console %s "%s"', BuildCommand::COMMAND_NAME, $pathConfigFileRelative));
        $output->writeln(sprintf('→ The 13 calendar pages are then located here by default: "%s"', $pathImagesReady));
        $output->writeln('→ Enjoy');
        $output->writeln('');

        return Command::SUCCESS;
    }
}

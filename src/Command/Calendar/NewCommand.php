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

use Exception;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    private const PATH_EXAMPLE = 'data/examples/simple';

    private const PATH_TARGET = 'data/calendar/%s';

    private const SALT = 'f7b5704d840f6b4f6b1ef4b3be39e2aa';

    private const CONFIG_FILE_NAME = 'config.yml';

    private const FOLDER_IMAGES = 'ready';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
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
            $md5 = substr(md5(random_int(100_000_000, 999_999_999).self::SALT), 0, 12);

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
                if (($file != '.') && ($file != '..')) {
                    $this->copyDirectory(sprintf('%s/%s', $source, $file), sprintf('%s/%s', $target, $file));
                }
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
        $source = self::PATH_EXAMPLE;
        $target = $this->getAvailableDirectoryName(self::PATH_TARGET);
        $configFile = sprintf('%s/%s', $target, self::CONFIG_FILE_NAME);
        $pathImages = sprintf('%s/%s/*', $target, self::FOLDER_IMAGES);

        $this->copyDirectory($source, $target);

        $output->writeln('');
        $output->writeln(sprintf('→ Directory "%s" was successfully created.', $target));
        $output->writeln('→ Got to this directory.');
        $output->writeln('→ Add your own images.');
        $output->writeln(sprintf('→ Edit the "%s" config file to your needs.', $configFile));
        $output->writeln(sprintf('→ Build your calendar with: bin/console %s "%s"', BuildCommand::COMMAND_NAME, $configFile));
        $output->writeln(sprintf('→ The 13 calendar pages are then located here by default: "%s"', $pathImages));
        $output->writeln('→ Enjoy');
        $output->writeln('');

        return Command::SUCCESS;
    }
}

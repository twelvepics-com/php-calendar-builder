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

namespace App\Command;

use App\Calendar\Config\CalendarConfig;
use App\Constants\Parameter\Argument;
use Exception;
use Ixnode\PhpContainer\Constant\MimeTypeIcons;
use Ixnode\PhpContainer\Directory;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class LsCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-12-19)
 * @since 0.1.1 (2024-12-19) Add calendar overview and detail output.
 * @since 0.1.0 (2024-12-16) First version.
 * @example bin/console ls
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class LsCommand extends Command
{
    final public const COMMAND_NAME = 'ls';

    final public const COMMAND_DESCRIPTION = 'List files';

    private const PATH_CALENDAR = 'data/calendar';

    private InputInterface $input;

    private OutputInterface $output;

    /**
     * @param KernelInterface $appKernel
     * @param string|null $name
     */
    public function __construct(
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
            ->addArgument(Argument::PATH, InputArgument::OPTIONAL, 'The path to be displayed.', null)
            ->setHelp(
                <<<'EOT'
The <info>ls</info> lists all files:
  <info>php %command.full_name%</info>
List files
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $path = $this->getPath();

        /* Print outside paths. */
        if (!$this->isWithinProject($path)) {
            $this->printPath($path);
            return Command::SUCCESS;
        }

        $path = $this->getWithinPath($path);
        $this->printPath($path);

        return Command::SUCCESS;
    }

    /**
     * Prints file information.
     *
     * @param File $file
     * @return void
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws ParserException
     */
    private function printFileDefault(File $file): void
    {
        $this->output->writeln($file->getFileInformationTable());
    }

    /**
     * Prints file information.
     *
     * @param File $file
     * @return void
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws ParserException
     */
    private function printFile(File $file): void
    {
        $this->printFileDefault($file);
    }

    /**
     * Prints directory information.
     *
     * @param Directory $directory
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function printDirectoryDefault(Directory $directory): void
    {
        $this->output->writeln('');
        $this->output->writeln($directory->getDirectoryInformationTable());
    }

    /**
     * Prints directory information.
     *
     * @param Directory $directory
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function printDirectoryCalendarOverview(Directory $directory): void
    {
        $this->output->writeln('');
        $this->output->writeln($directory->getDirectoryInformationTable(
            additional: [
                'Description' => 'Calendar configuration path',
            ],
            callbackDirectories: function (array $directories): array
            {
                $outputArrayDirectories = [];

                /** @var Directory $directory */
                foreach ($directories as $directory) {

                    $config = new CalendarConfig($directory->getBaseName(), $this->appKernel->getProjectDir());

                    $outputArrayDirectories[$directory->getBaseName()] = sprintf(
                        '%s',
                        $config->getName()
                    );
                }

                /* Sort result. */
                arsort($outputArrayDirectories);

                $index = 0;
                foreach ($outputArrayDirectories as &$outputArrayDirectory) {
                    $index++;
                    $outputArrayDirectory = sprintf('Calendar %02d: %s', $index, $outputArrayDirectory);
                }

                return $outputArrayDirectories;
            },
        ));
    }

    /**
     * Prints directory information.
     *
     * @param Directory $directory
     * @return void
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function printDirectoryCalendar(Directory $directory): void
    {
        $config = new CalendarConfig($directory->getBaseName(), $this->appKernel->getProjectDir());

        $additionalBlocks = [];

        /* General information. */
        $name = $config->getName();
        $date = $config->getDate();
        $year = $config->getYear();

        /* Birthdays. */
        $birthdaysCombined = array_map(
            fn($birthdayEntries) => implode(', ', array_column($birthdayEntries, 'name')),
            $config->getBirthdaysAll()
        );
        $additionalBlocks[] = [
            'name' => 'Birthdays',
            'icon' => MimeTypeIcons::CALENDAR,
            'blocks' => [$birthdaysCombined],
        ];

        /* Holidays. */
        $holidaysCombined = array_map(fn($holiday) => array_key_exists('name', $holiday) ? (string) $holiday['name'] : 'N/A', $config->getHolidaysAll());
        $additionalBlocks[] = [
            'name' => 'Holidays',
            'icon' => MimeTypeIcons::CALENDAR,
            'blocks' => [$holidaysCombined]
        ];

        /* Pages. */
        $pages = $config->getPages();
        if (!is_null($pages)) {
            $pageTitleLengthMax = max(array_map(
                fn(Json $item) => $item->hasKey('page-title') ? mb_strwidth($item->getKeyString('page-title')) : 0,
                $pages
            ));
            $pagesCombined = array_combine(
            /* Keys. */
                array_map(
                    fn(Json $item) => sprintf(
                        '%d/%s',
                        $item->getKeyInteger('year'),
                        str_pad((string) $item->getKeyInteger('month'), 2, '0', STR_PAD_LEFT)
                    ),
                    $pages
                ),
                /* Values. */
                array_map(function (Json $item) use ($pageTitleLengthMax) {
                    $pageTitle = $item->hasKey('page-title') ? $item->getKeyString('page-title') : 'N/A';
                    $pageTitleLengthAscii = strlen($pageTitle);
                    $pageTitleLengthUtf8 = mb_strlen($pageTitle);
                    return sprintf(
                        sprintf('%%-%ds // date: %%s; coordinate: %%s', $pageTitleLengthMax + $pageTitleLengthAscii - $pageTitleLengthUtf8),
                        $item->hasKey('page-title') ? $item->getKeyString('page-title') : 'N/A',
                        $item->hasKey('taken') ? $item->getKeyString('taken') : 'N/A',
                        $item->hasKey('coordinate') ? $item->getKeyString('coordinate') : 'N/A',
                    );
                }, $pages)
            );
            $additionalBlocks[] = [
                'name' => 'Pages',
                'icon' => MimeTypeIcons::CALENDAR,
                'blocks' => [$pagesCombined]
            ];
        }

        $this->output->writeln('');
        $this->output->writeln($directory->getDirectoryInformationTable(
            additional: [
                'Calendar' => $name,
                'Date (created)' => $date,
                'Year' => (string) $year,
            ],
            additionalBlocks: $additionalBlocks
        ));
    }

    /**
     * Prints directory information.
     *
     * @param Directory $directory
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function printDirectory(Directory $directory): void
    {
        match (true) {
            $this->isCalendarRootDirectory($directory) => $this->printDirectoryCalendarOverview($directory),
            $this->isCalendarDirectory($directory) => $this->printDirectoryCalendar($directory),
            default => $this->printDirectoryDefault($directory),
        };
    }

    /**
     * @param string $path
     * @return void
     * @throws Exception
     */
    private function printPath(string $path): void
    {
        if (is_file($path)) {
            $this->printFile(new File($path));
            return;
        }

        if (is_dir($path)) {
            $this->printDirectory(new Directory($path));
            return;
        }

        $this->output->writeln('No folder given');
    }

    /**
     * Returns if the given directory is a calendar path.
     *
     * @param Directory|File $directoryFile
     * @return bool
     * @throws FileNotFoundException
     */
    private function isCalendarRootDirectory(Directory|File $directoryFile): bool
    {
        if (!$directoryFile->isDirectory()) {
            return false;
        }

        $pathReal = $directoryFile->getPathReal();

        $calendarPath = sprintf('%s%s%s', $this->appKernel->getProjectDir(), DIRECTORY_SEPARATOR, self::PATH_CALENDAR);

        return $pathReal === $calendarPath;
    }

    /**
     * Returns if the given directory is a calendar path.
     *
     * @param Directory|File $directoryFile
     * @return bool
     * @throws FileNotFoundException
     */
    private function isCalendarDirectory(Directory|File $directoryFile): bool
    {
        if (!$directoryFile->isDirectory()) {
            return false;
        }

        $pathReal = $directoryFile->getPathReal();

        $calendarPath = sprintf('%s%s%s', $this->appKernel->getProjectDir(), DIRECTORY_SEPARATOR, self::PATH_CALENDAR);

        return preg_match(sprintf('~^%s%s[a-f0-9]{12}~', $calendarPath, DIRECTORY_SEPARATOR), $pathReal) === 1;
    }

    /**
     * Returns the given path.
     *
     * @return string
     */
    private function getPath(): string
    {
        $path = $this->input->getArgument(Argument::PATH);

        if (is_null($path)) {
            $path = $this->appKernel->getProjectDir();
        }

        if (!is_string($path)) {
            throw new LogicException('Path must be a string');
        }

        $filesystem = new Filesystem();

        if (!$filesystem->exists($path)) {
            throw new LogicException(sprintf('Path does not exists: "%s"', $path));
        }

        if (!$filesystem->isAbsolutePath($path)) {
            $path = sprintf('%s/%s', $this->appKernel->getProjectDir(), $path);
        }

        $realPath = realpath($path);

        if (!is_string($realPath)) {
            throw new LogicException('Path must be a string');
        }

        return $realPath;
    }

    /**
     * Checks if the given path is within this project.
     *
     * @param string $path
     * @return bool
     */
    private function isWithinProject(string $path): bool
    {
        return str_starts_with($path, $this->appKernel->getProjectDir());
    }

    /**
     * Returns the inside path.
     *
     * @param string $path
     * @return string
     */
    private function getWithinPath(string $path): string
    {
        if (!$this->isWithinProject($path)) {
            throw new LogicException(sprintf('Path is not inside project: "%s"', $path));
        }

        return str_replace($this->appKernel->getProjectDir().'/', '', $path);
    }
}

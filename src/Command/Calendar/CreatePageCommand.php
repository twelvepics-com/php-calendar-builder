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
use App\Constants\Parameter\Option;
use App\Parameter\Source;
use App\Parameter\Target;
use App\Service\Calendar\CalendarBuilderService;
use Exception;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreatePageCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-06)
 * @since 0.1.0 (2023-11-06) First version.
 * @example bin/console calendar:create-page --email "user1@domain.tld" --name "Calendar 1" --year 2022 --month 0
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class CreatePageCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:create-page';

    final public const COMMAND_DESCRIPTION = 'Creates a calendar page';

    private OutputInterface $output;

    /**
     * CreatePageCommand constructor
     *
     * @param CalendarBuilderService $calendarBuilderService
     * @param Source $source
     * @param Target $target
     */
    public function __construct(
        private readonly CalendarBuilderService $calendarBuilderService,
        private readonly Source $source,
        private readonly Target $target
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
            ->addOption(Option::QUALITY, null, InputOption::VALUE_REQUIRED, 'The output quality.', Target::DEFAULT_QUALITY)

            ->addOption(Option::YEAR, 'y', InputOption::VALUE_REQUIRED, 'The year with which the page will be created.', date('Y'))
            ->addOption(Option::MONTH, 'm', InputOption::VALUE_REQUIRED, 'The month with which the page will be created.', date('m'))

            ->addOption(Option::PAGE_TITLE, null, InputOption::VALUE_REQUIRED, 'The page title of the page.', Target::DEFAULT_PAGE_TITLE)
            ->addOption(Option::TITLE, null, InputOption::VALUE_REQUIRED, 'The title of the page.', Target::DEFAULT_TITLE)
            ->addOption(Option::SUBTITLE, null, InputOption::VALUE_REQUIRED, 'The subtitle of the page.', Target::DEFAULT_SUBTITLE)
            ->addOption(Option::COORDINATE, null, InputOption::VALUE_REQUIRED, 'The position/coordinate of the picture.', Target::DEFAULT_COORDINATE)

            ->addArgument(Argument::SOURCE, InputArgument::REQUIRED, 'The path to the source image.')
            ->setHelp(
                <<<'EOT'
The <info>calendar:create-page</info> creates a calendar page:
  <info>php %command.full_name%</info>
Creates a calendar page.
EOT
            );
    }

    /**
     * Prints the parameter to screen.
     *
     * @return void
     * @throws CaseUnsupportedException
     */
    private function printParameter(): void
    {
        $this->output->writeln([
            '',
            '============',
            'Page Creator',
            '============',
            '',
        ]);

        $this->output->writeln($this->source->getCliImage()->getAsciiString());
        $this->output->writeln('');
        $this->output->writeln('Source');
        $this->output->writeln('------');
        $this->output->writeln(sprintf('Path:            %s', $this->source->getPath()));
        $this->output->writeln('');

        $this->output->writeln('Target');
        $this->output->writeln('------');
        $this->output->writeln(sprintf('Year:            %s', $this->target->getYear()));
        $this->output->writeln(sprintf('Month:           %s', $this->target->getMonth()));
        $this->output->writeln(sprintf('Quality:         %s', $this->target->getQuality()));
        $this->output->writeln(sprintf('Title:           %s', $this->target->getTitle()));
        $this->output->writeln(sprintf('Subtitle:        %s', $this->target->getSubtitle()));
        $this->output->writeln(sprintf('Coordinate:      %s', $this->target->getCoordinate()));
    }

    /**
     * Prints the wait screen to screen.
     *
     * @return void
     */
    private function printWaitScreen(): void
    {
        $this->output->writeln('');
        $this->output->write(sprintf('Create calendar at %s. Please wait.. ', date('Y-m-d H:i:s')));
        $this->output->writeln('');
    }

    /**
     * Prints the build information.
     *
     * @param array<string, string|int|null> $buildInformation
     * @param float $timeTaken
     * @return void
     */
    private function printBuildInformation(array $buildInformation, float $timeTaken): void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('→ Time taken: %.2fs', $timeTaken));

        $this->output->writeln('');
        $this->output->writeln('Calendar page built from:');
        $this->output->writeln(sprintf('→ Path:      %s', $buildInformation['pathSource']));
        $this->output->writeln(sprintf('→ Mime:      %s', $buildInformation['mimeSource']));
        $this->output->writeln(sprintf('→ Size:      %s (%d Bytes)', $buildInformation['sizeHumanSource'], $buildInformation['sizeSource']));
        $this->output->writeln(sprintf('→ Dimension: %dx%d', $buildInformation['widthSource'], $buildInformation['heightSource']));

        $this->output->writeln('');
        $this->output->writeln('Calendar page written to:');
        $this->output->writeln(sprintf('→ Path:      %s', $buildInformation['pathTarget']));
        $this->output->writeln(sprintf('→ Mime:      %s', $buildInformation['mimeTarget']));
        $this->output->writeln(sprintf('→ Size:      %s (%d Bytes)', $buildInformation['sizeHumanTarget'], $buildInformation['sizeTarget']));
        $this->output->writeln(sprintf('→ Dimension: %dx%d', $buildInformation['widthTarget'], $buildInformation['heightTarget']));

        $this->output->writeln('');
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

        /* Read arguments (Source). */
        $this->source->readParameter($input);

        /* Read arguments (Target). */
        $this->target->readParameter($input);

        /* Print details */
        $this->printParameter();
        $this->printWaitScreen();

        /* Initialize calendar image */
        $this->calendarBuilderService->init(
            source: $this->source,
            target: $this->target,
        );

        /* Create calendar image */
        $timeStart = microtime(true);
        $this->printBuildInformation(
            $this->calendarBuilderService->build(),
            microtime(true) - $timeStart
        );

        return Command::SUCCESS;
    }
}

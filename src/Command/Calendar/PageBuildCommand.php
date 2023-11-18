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

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Constants\Parameter\Argument;
use App\Constants\Parameter\Option;
use App\Objects\Image\Image;
use App\Objects\Image\ImageContainer;
use App\Objects\Parameter\Source;
use App\Objects\Parameter\Target;
use App\Service\CalendarBuilderService;
use Exception;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PageBuildCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-06)
 * @since 0.1.0 (2023-11-06) First version.
 * @example bin/console calendar:page-build data/calendar/bcb37ef651a1814c091c8a24d8f550ee/DSC03740.png --year 2024 --month 1 --page-title 'Scotland, Edinburgh' --title Edinburgh --subtitle 'With love' --coordinate '55.948815, -3.193105'
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class PageBuildCommand extends Command
{
    final public const COMMAND_NAME = 'calendar:page-build';

    final public const COMMAND_DESCRIPTION = 'Builds a calendar page';

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
            ->addOption(Option::YEAR, null, InputOption::VALUE_REQUIRED, 'The year with which the page will be created.', date('Y'))
            ->addOption(Option::MONTH, null, InputOption::VALUE_REQUIRED, 'The month with which the page will be created.', date('m'))

            ->addOption(Option::PAGE_TITLE, null, InputOption::VALUE_REQUIRED, 'The page title of the page.', Target::DEFAULT_PAGE_TITLE)
            ->addOption(Option::TITLE, null, InputOption::VALUE_REQUIRED, 'The title of the page.', Target::DEFAULT_TITLE)
            ->addOption(Option::SUBTITLE, null, InputOption::VALUE_REQUIRED, 'The subtitle of the page.', Target::DEFAULT_SUBTITLE)
            ->addOption(Option::URL, null, InputOption::VALUE_REQUIRED, 'The url of the page.', Target::DEFAULT_SUBTITLE)
            ->addOption(Option::COORDINATE, null, InputOption::VALUE_REQUIRED, 'The position/coordinate of the picture.', Target::DEFAULT_COORDINATE)

            ->addOption(Option::OUTPUT_FORMAT, null, InputOption::VALUE_REQUIRED, 'The output format.', Target::DEFAULT_OUTPUT_FORMAT)
            ->addOption(Option::OUTPUT_QUALITY, null, InputOption::VALUE_REQUIRED, 'The output quality.', Target::DEFAULT_OUTPUT_QUALITY)

            ->addArgument(Argument::SOURCE, InputArgument::REQUIRED, 'The path to the source image.')
            ->setHelp(
                <<<'EOT'
The <info>calendar:page-build</info> creates a calendar page:
  <info>php %command.full_name%</info>
Creates a calendar page.
EOT
            );
    }

    /**
     * Prints the parameter to screen.
     *
     * @param BaseImageBuilder $imageBuilder
     * @return void
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws CaseUnsupportedException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    private function printParameter(BaseImageBuilder $imageBuilder): void
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
        $this->output->writeln(sprintf('Path:            %s', $this->source->getImage()->getPath()));
        $this->output->writeln('');

        $this->output->writeln('Target');
        $this->output->writeln('------');
        $this->output->writeln(sprintf('Year:            %s', $this->target->getYear()));
        $this->output->writeln(sprintf('Month:           %s', $this->target->getMonth()));
        $this->output->writeln('');
        $this->output->writeln(sprintf('Title:           %s', $this->target->getTitle() ?? 'n/a'));
        $this->output->writeln(sprintf('Subtitle:        %s', $this->target->getSubtitle() ?? 'n/a'));
        $this->output->writeln(sprintf('Page-Title:      %s', $this->target->getPageTitle()));
        $this->output->writeln(sprintf('URL:             %s', $this->target->getUrl($this->source->getIdentification())));
        $this->output->writeln(sprintf('Coordinate:      %s', $this->target->getCoordinate()));
        $this->output->writeln('');

        $this->output->writeln('Output');
        $this->output->writeln('------');
        $this->output->writeln(sprintf('Format:          %s', $this->target->getOutputFormat()));
        $this->output->writeln(sprintf('Quality:         %s', $this->target->getOutputQuality()));
        $this->output->writeln('');

        $this->output->writeln('Config (JSON)');
        $this->output->writeln('-------------');
        $this->output->writeln(sprintf('%s', $imageBuilder->getDesign()->getConfig()->getJsonStringFormatted()));
        $this->output->writeln('-------------');
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
     * @param ImageContainer $buildInformation
     * @param float $timeTaken
     * @return void
     * @throws Exception
     */
    private function printBuildInformation(ImageContainer $buildInformation, float $timeTaken): void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('→ Time taken: %.2fs', $timeTaken));

        $source = $buildInformation->getSource();
        $target = $buildInformation->getTarget();

        /** @var Image $image */
        foreach ([$source, $target] as $image) {
            $caption = $image->getType() === ImageContainer::TYPE_SOURCE ?
                'Calendar page built from:' :
                'Calendar page written to:';

            $this->output->writeln('');
            $this->output->writeln($caption);
            $this->output->writeln(sprintf('→ Path:      %s', $image->getPathRelative()));
            $this->output->writeln(sprintf('→ Mime:      %s', $image->getMimeType()));
            $this->output->writeln(sprintf('→ Size:      %s (%d Bytes)', $image->getSizeHuman(), $image->getSizeByte()));
            $this->output->writeln(sprintf('→ Dimension: %dx%d', $image->getWidth(), $image->getHeight()));
        }

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
        $this->target->readParameter($input, $this->source->getConfig());

        /* Get image builder. */
        $imageBuilder = $this->source->getImageBuilder();

        /* Print details */
        $this->printParameter($imageBuilder);
        $this->printWaitScreen();

        /* Initialize calendar image */
        $this->calendarBuilderService->init(
            parameterSource: $this->source,
            parameterTarget: $this->target,
            imageBuilder: $imageBuilder
        );

        /* Create calendar image */
        $timeStart = microtime(true);
        $this->printBuildInformation(
            $this->calendarBuilderService->build(true),
            microtime(true) - $timeStart
        );

        return Command::SUCCESS;
    }
}

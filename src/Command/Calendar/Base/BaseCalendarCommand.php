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

namespace App\Command\Calendar\Base;

use App\Calendar\Config\CalendarConfig;
use App\Constants\FileType;
use App\Constants\Parameter\Argument;
use App\Error\ErrorFileNotExists;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * Class HolidayCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-14)
 * @since 0.1.0 (2024-12-14) First version.
 */
abstract class BaseCalendarCommand extends Command
{
    private const TEMPLATE_PATH_YAML = 'data/calendar/%s/config.yml';

    protected OutputInterface $output;

    protected InputInterface $input;

    protected CalendarConfig $config;

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
        ;
    }

    /**
     * Executes the command part.
     */
    abstract protected function doExecute(): int;

    /**
     * Execute the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws FunctionReplaceException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        $identifier = $this->input->getArgument(Argument::CALENDAR);

        if (!is_string($identifier)) {
            throw new LogicException('Calendar identifier must be a string.');
        }

        $this->config = new CalendarConfig(
            identifier: $identifier,
            projectDir: $this->projectDir,
        );

        if ($this->config->hasError()) {
            $error = $this->config->getError();

            if (!is_string($error)) {
                throw new LogicException('Error message must be a string.');
            }

            $this->output->writeln($error);

            return Command::FAILURE;
        }

        return $this->doExecute();
    }

    /**
     * Returns the yaml path.
     *
     * @return string|ErrorFileNotExists
     */
    protected function getYamlPath(): string|ErrorFileNotExists
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
    protected function readYamlFile(): Json|null
    {
        $yamlPath = $this->getYamlPath();

        if ($yamlPath instanceof ErrorFileNotExists) {
            $this->output->writeln($yamlPath->getMessage());
            return null;
        }

        $data = Yaml::parseFile($yamlPath);

        if (!is_array($data)) {
            throw new LogicException('Unable to parse YAML string.');
        }

        return new Json($data);
    }
}

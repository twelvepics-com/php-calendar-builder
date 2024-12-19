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
use Exception;
use Imagick;
use ImagickPixel;
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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Class CreateOverviewCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-19)
 * @since 0.1.0 (2024-12-19) First version.
 * @example bin/console calendar:create-overview-image e04916437c63'
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class CreateOverviewImageCommand extends BaseCalendarCommand
{
    final public const COMMAND_NAME = 'calendar:create-overview-image';

    final public const COMMAND_DESCRIPTION = 'Creates the overview image of the calendar.';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setHelp(
                <<<'EOT'
The <info>calendar:create-overview-image</info> command:
  <info>php %command.full_name%</info>
Creates the overview image of the calendar.
EOT
            );
    }

    /**
     * Execute the commands.
     *
     * @return int
     * @throws Exception
     */
    protected function doExecute(): int
    {
        $imagePaths = $this->getImagePaths();
        $imageOutput = $this->getImageOutput();
        $imageDirectory = dirname($imageOutput);

        /* Create output directory. */
        if (!file_exists($imageDirectory)) {
            $success = mkdir($imageDirectory, 0775, true);

            if (!$success) {
                throw new LogicException(sprintf('Unable to create directory "%s"', $imageDirectory));
            }
        }

        /* Dimensions */
        $targetWidth = 5656; /* Width of output image. */
        $targetHeight = 4000; /* Height of output image. */
        $tileWidth = 1414; /* Width of single image. */
        $tileHeight = 1000; /*Height of single image. */

        /* Define postions. */
        $positions = [
            [0, 0], /* First image with doubled size. */
            [2, 0], [3, 0], /* Other images with single size. */
            [2, 1], [3, 1], /* Other images with single size. */
            [0, 2], [1, 2], /* Other images with single size. */
            [2, 2], [3, 2], /* Other images with single size. */
            [0, 3], [1, 3], /* Other images with single size. */
            [2, 3], [3, 3], /* Other images with single size. */
        ];

        $canvas = new Imagick();
        $canvas->newImage($targetWidth, $targetHeight, new ImagickPixel('white'));
        $canvas->setImageFormat('png');

        /* Add single images. */
        foreach ($imagePaths as $index => $path) {
            $image = new Imagick($path);

            /* First image with doubled size. */
            if ($index === 0) {
                $sizeMultiplier = 2;
                $image->resizeImage($tileWidth * $sizeMultiplier, $tileHeight * $sizeMultiplier, Imagick::FILTER_LANCZOS, 1);
                $canvas->compositeImage($image, Imagick::COMPOSITE_DEFAULT, 0, 0);
                continue;
            }

            /* Other images with single size with given position. */
            $image->resizeImage($tileWidth, $tileHeight, Imagick::FILTER_LANCZOS, 1);
            [$columnPosition, $rowPosition] = $positions[$index];
            $xPosition = $columnPosition * $tileWidth;
            $yPostion = $rowPosition * $tileHeight;
            $canvas->compositeImage($image, Imagick::COMPOSITE_DEFAULT, $xPosition, $yPostion);
        }

        $canvas->writeImage($imageOutput);

        $this->output->writeln(sprintf('Overview mage successfully written to "%s"', $imageOutput));

        $canvas->clear();
        $canvas->destroy();

        return Command::SUCCESS;
    }

    /**
     * Returns the image paths to add.
     *
     * @return array<int, string|null>
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function getImagePaths(): array
    {
        $pages = $this->config->getPages();

        if (is_null($pages)) {
            throw new LogicException('Unable to retrieve pages for calendar.');
        }

        return array_map(fn(Json $item) => $item->hasKey('target') ? sprintf(
            '%s%s%s%s%s%s%s',
            $this->projectDir,
            DIRECTORY_SEPARATOR,
            'data/calendar',
            DIRECTORY_SEPARATOR,
            $this->config->getIdentifier(),
            DIRECTORY_SEPARATOR,
            $item->getKeyString('target')
        ) : null, $pages);
    }

    /**
     * Returns the output path.
     *
     * @return string
     */
    private function getImageOutput(): string
    {
        $nameOutputFile = 'overview.png';

        return sprintf(
            '%s%s%s%s%s%s%s%s%s',
            $this->projectDir,
            DIRECTORY_SEPARATOR,
            'data/calendar',
            DIRECTORY_SEPARATOR,
            $this->config->getIdentifier(),
            DIRECTORY_SEPARATOR,
            'overview',
            DIRECTORY_SEPARATOR,
            $nameOutputFile
        );
    }
}

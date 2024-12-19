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

use App\Calendar\Config\CalendarConfig;
use App\Command\Calendar\Base\BaseCalendarCommand;
use App\Constants\Parameter\Option;
use App\Utils\Card\QRCard;
use Exception;
use Ixnode\PhpCliImage\CliImage;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class CreateOverviewQrCodeCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-13)
 * @since 0.1.0 (2024-12-13) First version.
 * @example bin/console calendar:create-overview-qr-code e04916437c63
 * @example bin/console calendar:create-overview-qr-code e04916437c63 -i 02.png
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class CreateOverviewQrCodeCommand extends BaseCalendarCommand
{
    final public const COMMAND_NAME = 'calendar:create-overview-qr-code';

    final public const COMMAND_DESCRIPTION = 'Creates the overview QRrCard with qr code of the calendar.';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(Option::IMAGE, 'i', InputOption::VALUE_OPTIONAL, 'The image on the bottom', null)
        ;

        $this
            ->setHelp(
                <<<'EOT'
The <info>calendar:create-overview-qr-code</info> command:
  <info>php %command.full_name%</info>
Creates the overview QRrCard with qr code of the calendar.
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
        $data = $this->config->getReactViewerCalendarPageQrCode(CalendarConfig::PAGE_AUTO);

        $title = $this->config->getCalendarTitle();
        $subtitle = $this->config->getCalendarSubtitle();

        if (is_null($title) || is_null($subtitle)) {
            throw new LogicException('Title and subtitle are required.');
        }

        $identifier = $this->config->getIdentifier();
        $image = $this->input->getOption(Option::IMAGE);

        if (!is_string($image) && !is_null($image)) {
            throw new LogicException('Image must be a string');
        }

        $path = sprintf('data/calendar/%s/overview/qr-code.png', $identifier);
        $directory = dirname($path);
        $imagePath = is_null($image) ? null : sprintf('data/calendar/%s/%s', $identifier, $image);

        $qrCard = new QRCard(
            width: 2000,
            height: 3000,
            textScanMe: 'Scan mich!',
            textTitle: $title,
            textSubtitle: $subtitle,
            imagePath: $imagePath,
            calendarConfig: $this->config,
            scale: 80,
            border: 1
        );

        /* Get image properties. */
        $imageStream = $qrCard->render(data: $data);

        /* Print QR Code information. */
        $this->output->writeln(sprintf('URL:            %s', $data));
        $this->output->writeln(sprintf('QR Code width:  %s', $qrCard->getWidth()));
        $this->output->writeln(sprintf('QR Code height: %s', $qrCard->getHeight()));

        /* Print QR Code. */
        $cliImage = new CliImage($imageStream, 80);
        $this->output->writeln('');
        $this->output->writeln($cliImage->getAsciiString());
        $this->output->writeln('');

        if (!file_exists($directory)) {
            $success = mkdir($directory, 0775, true);

            if (!$success) {
                $this->output->writeln(sprintf('<error>%s</error>', sprintf('Could not create directory "%s".', $directory)));
                return Command::FAILURE;
            }
        }

        /* Write QR card. */
        $success = file_put_contents($path, $imageStream);

        /* Unable to write QR card. */
        if (!$success) {
            $this->output->writeln(sprintf('<error>Unable to write qr card to "%s".</error>', $path));
            return Command::FAILURE;
        }

        /* Print success message. */
        $this->output->writeln(sprintf('QR card successfully written to "%s".', $path));

        return Command::SUCCESS;
    }
}

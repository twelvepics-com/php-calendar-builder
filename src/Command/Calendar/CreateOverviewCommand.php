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
use App\Utils\Card\QRCard;
use Exception;
use Ixnode\PhpCliImage\CliImage;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Class CreateOverviewCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-13)
 * @since 0.1.0 (2024-12-13) First version.
 * @example bin/console calendar:create-overview e04916437c63'
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class CreateOverviewCommand extends BaseCalendarCommand
{
    final public const COMMAND_NAME = 'calendar:create-overview';

    final public const COMMAND_DESCRIPTION = 'Creates the overview qr code of the calendar.';

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setHelp(
                <<<'EOT'
The <info>calendar:create-overview</info> command:
  <info>php %command.full_name%</info>
Creates the overview qr code of the calendar.
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
        $path = 'data/calendar/e04916437c63/calendar.png';
        $data = $this->config->getReactViewerCalendarPageQrCode(CalendarConfig::PAGE_AUTO);

        $title = $this->config->getCalendarTitle();
        $subtitle = $this->config->getCalendarSubtitle();

        if (is_null($title) || is_null($subtitle)) {
            throw new LogicException('Title and subtitle are required.');
        }

        $qrCard = new QRCard(
            textTitle: $title,
            textSubtitle: $subtitle,
            textScanMe: 'Scan mich!',
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
        $cliImage = new CliImage($imageStream, 160);
        $this->output->writeln('');
        $this->output->writeln($cliImage->getAsciiString());
        $this->output->writeln('');

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

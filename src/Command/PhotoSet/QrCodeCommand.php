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

namespace App\Command\PhotoSet;

use App\Calendar\Config\PhotoConfig;
use App\Utils\QrCode\QRCodeWithLogo;
use chillerlan\QRCode\Data\QRCodeDataException;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\QROptions;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class QrCodeCommand
 *
 * Start-Page
 * ----------
 * - https://c.twelvepics.com/p/a15f16437c83
 *
 * Single Photo:
 * -------------
 * - https://c.twelvepics.com/p/a15f16437c83/b50593fbd618
 * - https://c.twelvepics.com/p/a15f16437c83/a8d8856cdc5c
 * - etc.
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-11)
 * @since 0.1.0 (2024-11-11) First version.
 * @example bin/console photo-set:qr-code
 */
#[AsCommand(
    name: self::COMMAND_NAME,
    description: self::COMMAND_DESCRIPTION
)]
class QrCodeCommand extends Command
{
    final public const COMMAND_NAME = 'photo-set:qr-code';

    final public const COMMAND_DESCRIPTION = 'Creates qr codes from given photo set';

    private const NAME_ARGUMENT_PHOTO_SET_IDENTIFIER = 'identifier';

    public function __construct(
        protected readonly KernelInterface $appKernel,
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
            ->addArgument(self::NAME_ARGUMENT_PHOTO_SET_IDENTIFIER, InputArgument::REQUIRED, 'The photo set identifier.')
            ->setHelp(
                <<<'EOT'
The <info>photo-set:qr-code</info> creates qr codes from given photo set:
  <info>php %command.full_name%</info>
Creates qr codes.
EOT
            );
    }

    /**
     * Builds the logo image.
     *
     * @param string $text
     * @param int $width
     * @param int $height
     * @return string
     * @throws QRCodeException
     */
    private function getLogoImage(string $text, int $width = 50, int $height = 50): string
    {
        $image = imagecreate($width, $height);

        if ($image === false) {
            throw new QRCodeException();
        }

        /* Set background color. */
        imagecolorallocate($image, 255, 255, 255);

        /* Define text color. */
        $textColor = imagecolorallocate($image, 0, 0, 0);

        if ($textColor === false) {
            throw new QRCodeException();
        }

        $fontSize = 5;

        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        $posX = (int) (($width - $textWidth) / 2);
        $posY = (int) (($height - $textHeight) / 2);

        imagestring($image, $fontSize, $posX, $posY, $text, $textColor);

        ob_start();
        imagepng($image);
        $imageString = ob_get_clean();

        imagedestroy($image);

        if ($imageString === false) {
            throw new QRCodeException();
        }

        return $imageString;
    }

    /**
     * Builds the qr code.
     *
     * @param string $path
     * @param string $text
     * @param string $identifier
     * @param string|null $photoIdentifier
     * @return void
     * @throws QRCodeDataException
     * @throws QRCodeException
     * @throws QRCodeOutputException
     */
    private function buildQrCode(
        string $path,
        string $text,
        string $identifier,
        string|null $photoIdentifier = null,
    ): void
    {
        $logo = is_null($photoIdentifier) ? null : $this->getLogoImage($text, 20, 20);

        /* Matrix length of qrCode */
        $matrixLength = 37;

        /* Wanted width (and height) of qrCode */
        $width = 800;

        /* Calculate scale of qrCode */
        $scale = intval(ceil($width / $matrixLength));

        /* Set options for qrCode */
        $options = [
            'addQuietzone' => true,
            'eccLevel' => QRCode::ECC_H,
            'markupDark' => '#000',
            'markupLight' => '#fff',
            'outputType' => QRCode::OUTPUT_IMAGICK,
            'scale' => $scale,
            'version' => 8,
            'imageTransparent' => true,
            'drawCircularModules' => true,
            'circleRadius' => 0.1,
            'keepAsSquare' => [QRMatrix::M_FINDER, QRMatrix::M_FINDER_DOT],
            'imageBase64' => false,
        ];

        $url = match (true) {
            is_null($photoIdentifier) => sprintf('https://c.twelvepics.com/p/%s', $identifier),
            default => sprintf('https://c.twelvepics.com/p/%s/%s', $identifier, $photoIdentifier),
        };

        /* Build options. */
        $qrOption = new QROptions($options);

        /* Build qr code instance. */
        $qrCode = new QRCode($qrOption);

        $qrCodeWithLogo = new QRCodeWithLogo(
            options: $qrOption,
            matrix: $qrCode->getMatrix($url),
            logoWidth: 10,
            logoHeight: 10
        );

        /* Get blob from qrCode image */
        $qrCodeWithLogo->dump(file: $path, logo: $logo);
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
        $identifier = $input->getArgument(self::NAME_ARGUMENT_PHOTO_SET_IDENTIFIER);

        if (!is_string($identifier)) {
            $output->writeln('<error>Invalid QR Code identifier</error>');
            return Command::INVALID;
        }

        /* Build overview qr code. */
        $qrCodePath = sprintf('%s/%s/%s/%s.png', $this->appKernel->getProjectDir(), 'data/photo', $identifier, '0');
        $this->buildQrCode(
            path: $qrCodePath,
            text: '+',
            identifier: $identifier,
        );

        /* Get photo config. */
        $photoConfig = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        $photos = $photoConfig->getPhotos();

        /* No photos were found. */
        if (is_null($photos)) {
            return Command::FAILURE;
        }

        /* Build day qr codes. */
        foreach ($photos as $photoIdentifier => $photo) {
            if (!is_array($photo)) {
                throw new QRCodeException();
            }

            if (!array_key_exists('day', $photo)) {
                throw new QRCodeException();
            }

            $qrCodePath = sprintf('%s/%s/%s/%s.png', $this->appKernel->getProjectDir(), 'data/photo', $identifier, $photo['day']);

            $this->buildQrCode(
                path: $qrCodePath,
                text: (string) $photo['day'],
                identifier: $identifier,
                photoIdentifier: (string) $photoIdentifier,
            );
        }

        return Command::SUCCESS;
    }
}

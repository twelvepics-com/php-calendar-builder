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
use GdImage;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
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

    private const PATH_PHOTO = 'data/photo';

    private const PATH_QR_CARDS = 'cards';

    private const PATH_QR_CODES = 'qr-codes';

    private const TEMPLATE_QR_PAGE = '%s/page%d.png';

    private const PATH_CARDS_QR_CODES = self::PATH_QR_CARDS.'/'.self::PATH_QR_CODES;

    private OutputInterface $output;

    private string|null $pathQrCodeOverview = null;

    /** @var string[] $pathsQrCodePhotos */
    private array $pathsQrCodePhotos = [];

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
     * Build overview qr code
     *
     * @param string $identifier
     * @return void
     * @throws QRCodeDataException
     * @throws QRCodeException
     * @throws QRCodeOutputException
     * @throws FileNotFoundException
     */
    private function buildQrCodeOverview(string $identifier): void
    {
        $pathQrCodes = $this->getPathQrCodes($identifier);

        /* Build overview qr code. */
        $this->pathQrCodeOverview = sprintf('%s/%s.png', $pathQrCodes, '0');
        $this->buildQrCode(
            path: $this->pathQrCodeOverview,
            text: '+',
            identifier: $identifier,
        );

        $this->output->writeln(
            sprintf('Overview QR Code written to "%s".', $this->pathQrCodeOverview)
        );
    }

    /**
     * Returns the path of QR Codes.
     *
     * @throws FileNotFoundException
     */
    private function getPathQrCodes(string $identifier): string
    {
        $pathQrCodes = sprintf(
            '%s/%s/%s/%s',
            $this->appKernel->getProjectDir(),
            self::PATH_PHOTO,
            $identifier,
            self::PATH_CARDS_QR_CODES
        );

        if (!file_exists($pathQrCodes)) {
            mkdir($pathQrCodes, 0775, true);
        }

        if (!file_exists($pathQrCodes)) {
            throw new FileNotFoundException($pathQrCodes);
        }

        return $pathQrCodes;
    }

    /**
     * Returns the path of qr cards.
     *
     * @throws FileNotFoundException
     */
    private function getPathQrCards(string $identifier): string
    {
        $pathQrCards = sprintf(
            '%s/%s/%s/%s',
            $this->appKernel->getProjectDir(),
            self::PATH_PHOTO,
            $identifier,
            self::PATH_QR_CARDS
        );

        if (!file_exists($pathQrCards)) {
            mkdir($pathQrCards, 0775, true);
        }

        if (!file_exists($pathQrCards)) {
            throw new FileNotFoundException($pathQrCards);
        }

        return $pathQrCards;
    }

    /**
     * Build photo QR Codes
     *
     * @param string $identifier
     * @return void
     * @throws QRCodeDataException
     * @throws QRCodeException
     * @throws QRCodeOutputException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     * @throws JsonException
     */
    private function buildQrCodes(string $identifier): void
    {
        /* Get photo config. */
        $photoConfig = new PhotoConfig($identifier, $this->appKernel->getProjectDir());

        $photos = $photoConfig->getPhotos();

        $this->pathsQrCodePhotos = [];

        /* No photos were found. */
        if (is_null($photos)) {
            return;
        }

        $pathQrCodes = $this->getPathQrCodes($identifier);

        /* Build day QR Codes. */
        foreach ($photos as $photoIdentifier => $photo) {
            if (!is_array($photo)) {
                throw new QRCodeException();
            }

            if (!array_key_exists('day', $photo)) {
                throw new QRCodeException();
            }

            $pathImageQrCode = sprintf('%s/%s.png', $pathQrCodes, $photo['day']);

            $this->buildQrCode(
                path: $pathImageQrCode,
                text: (string) $photo['day'],
                identifier: $identifier,
                photoIdentifier: (string) $photoIdentifier,
            );

            $this->pathsQrCodePhotos[] = $pathImageQrCode;

            $this->output->writeln(
                sprintf('QR Code "%d" written to "%s".', $photo['day'], $pathImageQrCode)
            );
        }
    }

    /**
     * Add given file to QR page.
     *
     * @throws QRCodeException
     */
    private function addQrCodeToPage(string $file, GdImage $imagePage, int $posX, int $posY, int $width, int $height): void
    {
        /* Load the QR Code */
        $imageQrCode = imagecreatefrompng($file);

        if (!$imageQrCode instanceof GdImage) {
            throw new QRCodeException();
        }

        /* Resize and place the QR Code. */
        imagecopyresampled(
            $imagePage,
            $imageQrCode,
            $posX,
            $posY,
            0,
            0,
            $width,
            $height,
            imagesx($imageQrCode),
            imagesy($imageQrCode)
        );
        imagedestroy($imageQrCode); /* Free memory for the current QR Code. */
    }

    /**
     * Create QR Page.
     *
     * @param string[] $pageFiles
     * @throws QRCodeException
     */
    private function createQrPage(
        int $pageWidth,
        int $pageHeight,
        array $pageFiles,
        int $borderX,
        int $borderY,
        int $qrCodeTargetWidth,
        int $qrCodeTargetHeight,
        string $pathQrPage
    ): void
    {
        /* Create image. */
        $imagePage = imagecreatetruecolor($pageWidth, $pageHeight);

        if (!$imagePage instanceof GdImage) {
            throw new QRCodeException();
        }

        $backgroundColor = imagecolorallocate($imagePage, 255, 255, 255);

        if (!is_int($backgroundColor)) {
            throw new QRCodeException();
        }

        imagefill($imagePage, 0, 0, $backgroundColor);

        foreach ($pageFiles as $index => $file) {
            $col = $index % 3;
            $row = floor($index / 3);

            /* Calculate x and y positions. */
            $posX = (int) ($col * $qrCodeTargetWidth) + $borderX;
            $posY = (int) ($row * $qrCodeTargetHeight) + $borderY;

            /* Add QR Code to QR page. */
            $this->addQrCodeToPage(
                file: $file,
                imagePage: $imagePage,
                posX: $posX,
                posY: $posY,
                width: $qrCodeTargetWidth,
                height: $qrCodeTargetHeight,
            );
        }

        /* Save the page. */
        imagepng($imagePage, $pathQrPage);
        imagedestroy($imagePage);

        $this->output->writeln(
            sprintf('QR Code Card successfully generated and saved in "%s".', $pathQrPage)
        );
    }

    /**
     * Builds qr cards.
     *
     * @throws FileNotFoundException
     * @throws QRCodeException
     */
    private function buildQrCards(string $identifier): void
    {
        /* Define border. */
        $borderX = 75;
        $borderY = 75;

        /* Define page dimensions (15cm x 10cm at 300 DPI = 1772x1181 pixels). */
        $pageWidth = 1772;
        $pageHeight = 1181;
        $pageWidthInner = $pageWidth - 2 * $borderX;
        $pageHeightInner = $pageHeight - 2 * $borderY;

        /* Define QR Code size and margins. */
        $qrCodeTargetWidth = (int) floor($pageWidthInner / 3);
        $qrCodeTargetHeight = (int) floor($pageHeightInner / 2);

        /* Define source QR code files. */
        $pathQrCards = $this->getPathQrCards($identifier);

        /* Get QR Code paths. */
        $files = array_map(fn($qrCode) => sprintf('%s/%s/%s.png', $pathQrCards, self::PATH_QR_CODES, $qrCode), range(0, 24));

        /* Page for the first QR Code (file 0). */
        $pathQrPage = sprintf(self::TEMPLATE_QR_PAGE, $pathQrCards, 0);

        /* Create QR Page. */
        $this->createQrPage(
            pageWidth: $pageWidth,
            pageHeight: $pageHeight,
            pageFiles: [$files[0]],
            borderX: $borderX,
            borderY: $borderY,
            qrCodeTargetWidth: $qrCodeTargetWidth,
            qrCodeTargetHeight: $qrCodeTargetHeight,
            pathQrPage: $pathQrPage,
        );

        /* Process pages for the remaining QR Codes (1-24) */
        $pages = array_chunk(array_slice($files, 1), 6); // 6 QR codes per page (3x2 layout)
        foreach ($pages as $pageIndex => $pageFiles) {
            /* Page for the first QR code (file index). */
            $pathQrPage = sprintf(self::TEMPLATE_QR_PAGE, $pathQrCards, $pageIndex + 1);

            /* Create QR Page. */
            $this->createQrPage(
                pageWidth: $pageWidth,
                pageHeight: $pageHeight,
                pageFiles: $pageFiles,
                borderX: $borderX,
                borderY: $borderY,
                qrCodeTargetWidth: $qrCodeTargetWidth,
                qrCodeTargetHeight: $qrCodeTargetHeight,
                pathQrPage: $pathQrPage,
            );
        }

        $this->output->writeln(
            sprintf('%d QR Code cards were successfully generated and saved in "%s".', count($pages) + 1, $pathQrCards)
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
        $this->output = $output;

        $identifier = $input->getArgument(self::NAME_ARGUMENT_PHOTO_SET_IDENTIFIER);

        if (!is_string($identifier)) {
            $output->writeln('<error>Invalid QR Code identifier</error>');
            return Command::INVALID;
        }

        /* Write QR Codes. */
        $this->buildQrCodeOverview($identifier);
        $this->buildQrCodes($identifier);

        if ($this->pathsQrCodePhotos === []) {
            $output->writeln('Unable to build photo QR Codes.');
            return Command::FAILURE;
        }

        $this->output->writeln(
            'All QR Codes written'
        );

        /* Write QR Code Cards. */
        $this->buildQrCards($identifier);

        return Command::SUCCESS;
    }
}

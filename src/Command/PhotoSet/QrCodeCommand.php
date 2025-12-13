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
use App\Utils\QrCode\QRGdImageRounded;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRCodeDataException;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QROutputInterface;
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

    private const COLOR_WHITE = [255, 255, 255];

    private const COLOR_BLACK = [0, 0, 0];

    private const COLOR_DARK_BLUE = [0, 0, 127];

    private const FONT_TITLE = 'data/font/OpenSansCondensed-Bold.ttf';

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

        $border = 2;

        /* Calculate scale of qrCode */
        $scale = intval(ceil($width / $matrixLength));

        /* Assign colors. */
        $dotLight = self::COLOR_WHITE;
        $dotDark = self::COLOR_BLACK;
        $finderLight = self::COLOR_WHITE;
        $finderDark = self::COLOR_DARK_BLUE;

        /* Set options for qrCode */
        $options = [
            'eccLevel' => EccLevel::H,
            'addQuietzone' => $border > 0,
            'version' => 7,
            'scale' => $scale,
            'outputBase64' => false,
            'quality' => 100,

            'outputType' => QROutputInterface::CUSTOM,
            'outputInterface' => QRGdImageRounded::class,
            'transparencyColor' => self::COLOR_WHITE,
            'bgColor' => self::COLOR_WHITE,

            'quietzoneSize' => $border,
            'addLogoSpace' => (bool)$logo,
            'circleRadius' => 0.45,
            'drawCircularModules' => true,
            'imageTransparent' => true,
            'keepAsSquare' => [],
            'logoSpaceHeight' => 15,
            'logoSpaceStartX' => 15,
            'logoSpaceStartY' => 15,
            'logoSpaceWidth' => 15,

            'moduleValues' => [
                /* Set light points. */
                QRMatrix::M_ALIGNMENT        => $dotLight,
                QRMatrix::M_DARKMODULE_LIGHT => $dotLight,
                QRMatrix::M_DATA             => $dotLight,
                QRMatrix::M_FINDER           => $finderLight,
                QRMatrix::M_FINDER_DOT_LIGHT => $finderLight,
                QRMatrix::M_FORMAT           => $dotLight,
                QRMatrix::M_LOGO             => $dotLight,
                QRMatrix::M_NULL             => $dotLight,
                QRMatrix::M_QUIETZONE        => $dotLight,
                QRMatrix::M_SEPARATOR        => $dotLight,
                QRMatrix::M_TIMING           => $dotLight,
                QRMatrix::M_VERSION          => $dotLight,

                /* Set dark points. */
                QRMatrix::M_ALIGNMENT_DARK   => $dotDark,
                QRMatrix::M_DARKMODULE       => $dotDark,
                QRMatrix::M_DATA_DARK        => $dotDark,
                QRMatrix::M_FINDER_DARK      => $finderDark,
                QRMatrix::M_FINDER_DOT       => $finderDark,
                QRMatrix::M_FORMAT_DARK      => $dotDark,
                QRMatrix::M_LOGO_DARK        => $dotDark,
                QRMatrix::M_QUIETZONE_DARK   => $dotDark,
                QRMatrix::M_SEPARATOR_DARK   => $dotDark,
                QRMatrix::M_TIMING_DARK      => $dotDark,
                QRMatrix::M_VERSION_DARK     => $dotDark,
            ],
        ];

        $url = match (true) {
            is_null($photoIdentifier) => sprintf('https://c.twelvepics.com/p/%s', $identifier),
            default => sprintf('https://c.twelvepics.com/p/%s/%s', $identifier, $photoIdentifier),
        };

        /* Build options. */
        $qrOptions = new QROptions($options);

        /* Build qr code instance. */
        $qrCode = new QRCode($qrOptions);

        $matrix = $qrCode->getQRMatrix();

        $matrix->setLogoSpace(10, 10);

        $qrCode->render($url, $path);

        $qrImage = imagecreatefrompng($path);

        if ($logo) {
            $logoImage = imagecreatefromstring($logo);

            $qrWidth  = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);

            $logoWidth  = imagesx($logoImage);
            $logoHeight = imagesy($logoImage);

            $targetWidth  = (int) ($qrWidth * 0.2);
            $targetHeight = (int) ($logoHeight * ($targetWidth / $logoWidth));

            $dstX = (int) (($qrWidth  - $targetWidth) / 2);
            $dstY = (int) (($qrHeight - $targetHeight) / 2);

            /* Add logo. */
            imagecopyresampled(
                $qrImage,
                $logoImage,
                $dstX, $dstY,
                0, 0,
                $targetWidth, $targetHeight,
                $logoWidth,  $logoHeight
            );

            imagepng($qrImage, $path);

            imagedestroy($logoImage);
        }

        imagedestroy($qrImage);
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
        string $pathQrPage,
        bool $landscape,
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

        if (count($pageFiles) === 1) {
            $file = $pageFiles[0];

            // Titel & Untertitel
            $title    = "2025";
            $subtitle = "Weihnachtskalender";

            // Footer
            $footer = "für Isa";

            // TTF-Font
            $fontPath = self::FONT_TITLE;

            // Schriftgrößen
            $titleFontSize    = 64;
            $subtitleFontSize = 40;
            $footerFontSize   = $subtitleFontSize; // wie Untertitel

            // Farben
            $black = imagecolorallocate($imagePage, 0, 0, 0);

            // QR-Position wie bisher
            $qrX = (int)(($pageWidth  - $qrCodeTargetWidth)  / 2);
            $qrY = (int)(($pageHeight - $qrCodeTargetHeight) / 2);

            // ---- TEXT POSITION BERECHNEN ----

            // Bounding-Boxes ermitteln
            $titleBox    = imagettfbbox($titleFontSize, 0, $fontPath, $title);
            $subtitleBox = imagettfbbox($subtitleFontSize, 0, $fontPath, $subtitle);
            $footerBox   = imagettfbbox($footerFontSize, 0, $fontPath, $footer);

            $titleWidth     = $titleBox[2] - $titleBox[0];
            $titleHeight    = $titleBox[1] - $titleBox[7];

            $subtitleWidth  = $subtitleBox[2] - $subtitleBox[0];
            $subtitleHeight = $subtitleBox[1] - $subtitleBox[7];

            $footerWidth    = $footerBox[2] - $footerBox[0];
            $footerHeight   = $footerBox[1] - $footerBox[7];

            // Abstände
            $gapTitleToSubtitle = 10;
            $gapSubtitleToQr    = 60;    // Titel weiter hoch setzen
            $gapQrToFooter      = 60;    // Abstand QR → Footer

            // Titel/Untertitel Y-Positionen
            $subtitleY = (int)($qrY - $gapSubtitleToQr);
            $titleY    = (int)($subtitleY - $gapTitleToSubtitle - $subtitleHeight);

            // Footer Y-Position (unter dem QR-Code)
            $footerY = (int)($qrY + $qrCodeTargetHeight + $gapQrToFooter);

            // X für Zentrierung
            $titleX    = (int)(($pageWidth - $titleWidth) / 2);
            $subtitleX = (int)(($pageWidth - $subtitleWidth) / 2);
            $footerX   = (int)(($pageWidth - $footerWidth) / 2);

            // ---- TEXT ZEICHNEN ----
            imagettftext($imagePage, $titleFontSize,    0, $titleX,    $titleY,    $black, $fontPath, $title);
            imagettftext($imagePage, $subtitleFontSize, 0, $subtitleX, $subtitleY, $black, $fontPath, $subtitle);
            imagettftext($imagePage, $footerFontSize,   0, $footerX,   $footerY,   $black, $fontPath, $footer);

            // ---- QR CODE EINSETZEN ----
            $this->addQrCodeToPage(
                file: $file,
                imagePage: $imagePage,
                posX: $qrX,
                posY: $qrY,
                width: $qrCodeTargetWidth,
                height: $qrCodeTargetHeight,
            );
        } else {
            foreach ($pageFiles as $index => $file) {
                $cols = $landscape ? 3 : 2;
                $col = $index % $cols;
                $row = floor($index / $cols);

                /* Calculate x and y positions. */
                $posX = (int)($col * $qrCodeTargetWidth) + $borderX;
                $posY = (int)($row * $qrCodeTargetHeight) + $borderY;

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
    private function buildQrCards(string $identifier, bool $landscape): void
    {
        /* Define border. */
        $borderX = 75;
        $borderY = 75;

        /* Define page dimensions (15cm x 10cm at 300 DPI = 1772x1181 pixels). */
        $pageWidth = $landscape ? 1772 : 1181;
        $pageHeight = $landscape ? 1181 : 1772;
        $pageWidthInner = $pageWidth - 2 * $borderX;
        $pageHeightInner = $pageHeight - 2 * $borderY;

        /* Define QR Code size and margins. */
        $qrCodeTargetWidth = (int) floor($pageWidthInner / ($landscape ? 3 : 2));
        $qrCodeTargetHeight = (int) floor($pageHeightInner / ($landscape ? 2 : 3));

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
            pageFiles: [array_shift($files)],
            borderX: $borderX,
            borderY: $borderY,
            qrCodeTargetWidth: $qrCodeTargetWidth,
            qrCodeTargetHeight: $qrCodeTargetHeight,
            pathQrPage: $pathQrPage,
            landscape: $landscape,
        );

        shuffle($files);

        /* Process pages for the remaining QR Codes (1-24) */
        $pages = array_chunk($files, 6); // 6 QR codes per page (2x3 or 3x2 layout)
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
                landscape: $landscape,
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
        $this->buildQrCards($identifier, false);

        return Command::SUCCESS;
    }
}

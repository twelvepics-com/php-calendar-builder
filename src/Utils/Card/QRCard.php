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

namespace App\Utils\Card;

use App\Utils\QrCode\QRGdImageRounded;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use LogicException;

/**
 * Class QRCard
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-12-14)
 * @since 0.1.0 (2024-12-14) First version.
 */
class QRCard
{
    private QROptions $options;

    private Imagick $qrCard;

    private int $width = 2000, $height = 3000;

    private int $qrCodeBgX, $qrCodeBgY, $qrCodeBgWidth, $qrCodeBgHeight, $qrCodeBgRadius;

    private int $backgroundBottomY;

    private const COLOR_DARK_BLUE = [0, 0, 127];

    private const COLOR_WHITE = [255, 255, 255];

    private const COLOR_BLACK = [0, 0, 0];

    private const COLOR_ALMOST_BLACK = [10, 10, 10];

    private const COLOR_ALMOST_BLACKER = [18, 18, 18];

    private const COLOR_ALMOST_BLACKEST = [26, 26, 26];

    private const COLOR_LIGHT_BLUE = [230, 230, 255];

    private const FONT_SCAN_ME = 'data/font/OpenSansCondensed-Light.ttf';

    /**
     */
    public function __construct(
        private readonly string $textTitle,
        private readonly string $textSubtitle,
        private readonly string $textScanMe,
        private readonly int $scale = 20,
        private readonly int $border = 0,
    )
    {
        $this->init();
    }

    /**
     * Init function.
     */
    private function init(): void
    {
        /* Assign colors. */
        $dotLight = self::COLOR_WHITE;
        $dotDark = self::COLOR_BLACK;
        $finderLight = self::COLOR_WHITE;
        $finderDark = self::COLOR_DARK_BLUE;

        $this->options = new QROptions([
            'outputType' => QROutputInterface::CUSTOM,
            'outputInterface' => QRGdImageRounded::class,

            'addLogoSpace' => false,
            'addQuietzone' => $this->border > 0,
            'bgColor' => self::COLOR_WHITE,
            'circleRadius' => 0.45,
            'drawCircularModules' => true,
            //'eccLevel' => EccLevel::H,
            'imageTransparent' => true,
            'keepAsSquare' => [],
            'logoSpaceHeight' => 15,
            'logoSpaceStartX' => 15,
            'logoSpaceStartY' => 15,
            'logoSpaceWidth' => 15,
            'outputBase64' => false,
            'quality' => 100,
            'quietzoneSize' => $this->border,
            'scale' => $this->scale,
            'transparencyColor' => [255, 255, 255],
            //'version' => 7,
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
        ]);
    }

    /**
     * Returns the width of the image.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Returns the height of the image.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Renders the qr code.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    public function render(
        ?string $data = null,
        ?string $file = null
    ): string
    {
        $this->qrCard = $this->createQrCard();
        $this->addBackground();
        $this->addQrCode($data, $file);
        $this->addScanMe();
        $this->addTitle();
        $this->addSubtitle();

        return $this->qrCard->getImageBlob();
    }

    /**
     * Creates the qr card.
     *
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    private function createQrCard(): Imagick
    {
        /* Create qr card. */
        $qrCard = new Imagick();
        $qrCard->newImage($this->width, $this->height, new ImagickPixel($this->getColor(self::COLOR_ALMOST_BLACK)));
        $qrCard->setImageFormat('png');

        $drawPattern = new ImagickDraw();

        $drawPattern->setFillColor(new ImagickPixel($this->getColor(self::COLOR_ALMOST_BLACKER))); // Leicht helleres Schwarz
        $drawPattern->polygon([
            [
                'x' => 0,
                'y' => $this->height * 0.05
            ],
            [
                'x' => $this->width,
                'y' => $this->height * 0.15
            ],
            [
                'x' => $this->width,
                'y' => $this->height * 1.
            ],
            [
                'x' => 0,
                'y' => $this->height
            ],
        ]);

        $drawPattern->setFillColor(new ImagickPixel($this->getColor(self::COLOR_ALMOST_BLACKEST)));
        $circleX = $this->width * 0.8;
        $circleY = $this->height * 1.2;
        $radius = $this->height;
        $drawPattern->circle($circleX, $circleY, $circleX + $radius, $circleY);

        $qrCard->drawImage($drawPattern);

        $qrCard->blurImage(2, 1);

        return $qrCard;
    }

    /**
     * Returns the qr code image stream.
     */
    private function getQrCode(
        ?string $data = null,
        ?string $file = null
    ): string
    {
        /* Build qr code instance. */
        $qrCode = new QRCode($this->options);

        /* Get image properties. */
        $imageStream = $qrCode->render($data, $file);

        /* Check image stream. */
        if (!is_string($imageStream)) {
            throw new LogicException('QR image stream must be a string');
        }

        return $imageStream;
    }

    /**
     * Adds the background to qr card.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    private function addBackground(): void
    {
        /* Create bottom card part. */
        $backgroundBottom = new ImagickDraw();
        $backgroundBottom->setFillColor(new ImagickPixel($this->getColor(self::COLOR_LIGHT_BLUE)));

        $backgroundBottomX = 0;
        $this->backgroundBottomY = (int)floor($this->height * (3 / 5));
        $backgroundBottomWidth = $this->width;
        $backgroundBottomHeight = $this->height - $this->backgroundBottomY;

        /* Add rectangle. */
        $backgroundBottom->rectangle(
            $backgroundBottomX,
            $this->backgroundBottomY,
            $backgroundBottomWidth,
            $this->backgroundBottomY + $backgroundBottomHeight
        );

        /* Add bottom background. */
        $this->qrCard->drawImage($backgroundBottom);
    }

    /**
     * Add qr code.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    private function addQrCode(
        ?string $data = null,
        ?string $file = null
    ): void
    {
        /* Create qr code. */
        $qrCodeOverlay = new Imagick();
        $qrCodeOverlay->readImageBlob($this->getQrCode($data, $file));

        /* Calculate qr code sizes. */
        $qrCodeWidthSource = $qrCodeOverlay->getImageWidth();
        $qrCodeHeightSource = $qrCodeOverlay->getImageHeight();
        $qrCodeHeightTarget = (int) floor($this->height / 3);
        $qrCodeWidthTarget = (int) floor(($qrCodeWidthSource / $qrCodeHeightSource) * $qrCodeHeightTarget);

        /* Calculate qr code background sizes. */
        $this->qrCodeBgWidth = $qrCodeWidthTarget + $this->scale / 2;
        $this->qrCodeBgHeight = $qrCodeHeightTarget + $this->scale / 2;
        $this->qrCodeBgX = (int) floor(($this->width - $this->qrCodeBgWidth) / 2);
        $this->qrCodeBgY = (int) floor(($this->height - $this->qrCodeBgHeight) / 2);
        $this->qrCodeBgRadius = (int) floor($this->qrCodeBgWidth / 20);

        /* Create qr code background. */
        $qrCodeBg = new ImagickDraw();
        $qrCodeBg->setFillColor(new ImagickPixel($this->getColor(self::COLOR_WHITE)));
        $qrCodeBg->setStrokeColor(new ImagickPixel($this->getColor(self::COLOR_BLACK)));
        $qrCodeBg->setStrokeWidth(1);
        $qrCodeBg->roundRectangle(
            $this->qrCodeBgX,
            $this->qrCodeBgY,
            $this->qrCodeBgX + $this->qrCodeBgWidth,
            $this->qrCodeBgY + $this->qrCodeBgHeight,
            $this->qrCodeBgRadius,
            $this->qrCodeBgRadius
        );

        /* Add qr code background. */
        $this->qrCard->drawImage($qrCodeBg);

        /* Resize qr code. */
        $qrCodeOverlay->resizeImage($qrCodeWidthTarget, $qrCodeHeightTarget, Imagick::FILTER_LANCZOS, 1);

        /* Add qr code. */
        $qrCodeX = (int) floor(($this->width - $qrCodeWidthTarget) / 2);
        $qrCodeY = (int) floor(($this->height - $qrCodeHeightTarget) / 2);
        $this->qrCard->compositeImage($qrCodeOverlay, Imagick::COMPOSITE_OVER, $qrCodeX, $qrCodeY);
    }

    /**
     * Add scan me part.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    private function addScanMe(): void
    {
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->getColor(self::COLOR_LIGHT_BLUE)));

        /* Box settings. */
        $boxDistance = 2 * $this->qrCodeBgRadius;
        $boxTriangleSize = (int) floor($boxDistance / 2);
        $boxRadius = (int) floor($boxDistance / 8);
        $boxX = $this->qrCodeBgX + $this->qrCodeBgWidth + $boxDistance;
        $boxY = $this->qrCodeBgY;
        $boxWidth = (int) floor(1.5 * $boxDistance);
        $boxHeight = $this->backgroundBottomY - $boxY - $boxDistance;

        /* Triangle settings. */
        $triangleX = $boxX;
        $triangleY = $boxY + (int) floor($boxHeight / 2);

        /* Text settings. */
        $textX = (int) floor($boxX + $boxWidth / 2);
        $textY = (int) floor($boxY + $boxHeight / 2);

        $draw->roundRectangle($boxX, $boxY, $boxX + $boxWidth, $boxY + $boxHeight, $boxRadius, $boxRadius);
        $draw->polygon([
            ['x' => $triangleX, 'y' => $triangleY - $boxTriangleSize],
            ['x' => $triangleX - $boxTriangleSize, 'y' => $triangleY],
            ['x' => $triangleX, 'y' => $triangleY + $boxTriangleSize],
        ]);

        /* Scan me text. */
        $drawText = new ImagickDraw();
        $drawText->setFontSize((int) floor($this->qrCodeBgRadius / 1.));
        $drawText->setTextKerning((int) floor($this->qrCodeBgRadius / 2.));
        $drawText->setFont(self::FONT_SCAN_ME);

        /* Get font size. */
        $metrics = $this->qrCard->queryFontMetrics($drawText, $this->textScanMe);
        $textHeight = $metrics['textHeight'];

        /* Scan me text configuration. */
        $drawText->setFillColor(new ImagickPixel($this->getColor(self::COLOR_DARK_BLUE)));
        $drawText->setTextAlignment(Imagick::ALIGN_CENTER);
        $drawText->rotate(-90);
        $drawText->annotation(-$textY, $textX + (int) floor($textHeight * .28), $this->textScanMe);

        /* Add text and box. */
        $this->qrCard->drawImage($draw);
        $this->qrCard->drawImage($drawText);
    }

    /**
     * Add title.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    private function addTitle(): void
    {
        $textX = (int) floor($this->width / 2);
        $textY = (int) floor($this->height * (1 / 5));

        $drawText = new ImagickDraw();
        $drawText->setFontSize($this->qrCodeBgRadius * 2);
        $drawText->setTextKerning((int) floor($this->qrCodeBgRadius / 2.));
        $drawText->setFont(self::FONT_SCAN_ME);
        $drawText->setFillColor(new ImagickPixel($this->getColor(self::COLOR_WHITE)));
        $drawText->setTextAlignment(Imagick::ALIGN_CENTER);
        $drawText->annotation($textX, $textY, $this->textTitle);

        $this->qrCard->drawImage($drawText);
    }

    /**
     * Add subtitle.
     *
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    private function addSubtitle(): void
    {
        $textX = (int) floor($this->width / 2);
        $textY = (int)floor($this->height * (4 / 5));

        $drawText = new ImagickDraw();
        $drawText->setFontSize($this->qrCodeBgRadius * 1.3);
        $drawText->setTextKerning((int) floor($this->qrCodeBgRadius / 2.));
        $drawText->setFont(self::FONT_SCAN_ME);
        $drawText->setFillColor(new ImagickPixel($this->getColor(self::COLOR_DARK_BLUE)));
        $drawText->setTextAlignment(Imagick::ALIGN_CENTER);
        $drawText->annotation($textX, $textY, $this->textSubtitle);

        $this->qrCard->drawImage($drawText);
    }

    /**
     * Returns the rgb color string.
     *
     * @param array{0: int, 1: int, 2: int} $color
     */
    private function getColor(array $color): string
    {
        return sprintf('rgb(%s, %s, %s)', $color[0], $color[1], $color[2]);
    }
}

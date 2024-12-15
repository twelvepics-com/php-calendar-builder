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

namespace App\Utils\QrCode;

use chillerlan\QRCode\Data\QRCodeDataException;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRImage;
use chillerlan\QRCode\QRCodeException;
use chillerlan\Settings\SettingsContainerInterface;
use GdImage;
use LogicException;

/**
 * Class QrCodeWithLogo
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.1 (2024-11-30)
 * @since 0.1.0 (2024-11-30) First version.
 */
class QRCodeWithLogo extends QRImage
{
    public function __construct(
        SettingsContainerInterface $options,
        QRMatrix $matrix,
        string $url,
        private readonly int $logoWidth = 150,
        private readonly int $logoHeight = 150
    )
    {
        $this->$url = $url;

        parent::__construct($options, $matrix);
    }

    /**
     * Returns the qr code.
     *
     * @param string|null $file
     * @return string
     * @throws QRCodeOutputException
     */
    private function getQrCode(string $file = null): string
    {
        $imageData = $this->dumpImage();

        /* Convert to base64. */
        if (property_exists($this->options, 'imageBase64') && $this->options->imageBase64) {
            $outputType = property_exists($this->options, 'outputType') ? $this->options->outputType : 'png';
            $imageData = 'data:image/'.$outputType.';base64,'.base64_encode($imageData);
        }

        /* Save qr code as file. */
        if ($file !== null) {
            $this->saveToFile($imageData, $file);
        }

        return $imageData;
    }

    /**
     * @param string|null $file
     * @param string|null $logo
     * @return string
     * @throws QRCodeOutputException
     * @throws QRCodeDataException
     * @throws QRCodeException
     */
    public function dump(string $file = null, string $logo = null): string
    {
        /* No logo given. */
        if (is_null($logo)) {

            /* Execute the parent dump. */
            parent::dump($file);

            /* Returns the image. */
            return $this->getQrCode($file);
        }

        /* Set logo space. */
        $this->matrix->setLogoSpace(
            $this->logoWidth,
            $this->logoHeight
        );

        /* Execute the parent dump. */
        parent::dump($file);

        /* Build GdImage instance from given image string. */
        $imageLogo = imagecreatefromstring($logo);

        if ($imageLogo === false) {
            throw new QRCodeException();
        }

        $width = imagesx($imageLogo);
        $height = imagesy($imageLogo);

        $scale = property_exists($this->options, 'scale') ? $this->options->scale : 1.;

        $logoWidth = ($this->logoWidth - 2) * $scale;
        $logoHeight = ($this->logoHeight - 2) * $scale;

        $logoScale = $this->matrix->size() * $scale;

        if (!$this->image instanceof GdImage) {
            throw new QRCodeException();
        }

        /* Scale the logo and copy it over. */
        imagecopyresampled(
            $this->image,
            $imageLogo,
            ($logoScale - $logoWidth) / 2,
            ($logoScale - $logoHeight) / 2,
            0,
            0,
            $logoWidth,
            $logoHeight,
            $width,
            $height
        );

        /* Returns the image. */
        return $this->getQrCode($file);
    }

    /**
     * Returns the GdImage instance.
     *
     * @param string|null $file
     * @param string|null $logo
     * @return GdImage
     * @throws QRCodeDataException
     * @throws QRCodeException
     * @throws QRCodeOutputException
     */
    public function getGdImage(string $file = null, string $logo = null): GdImage
    {
        $imageStream = $this->dump(
            file: $file,
            logo: $logo,
        );

        $image = imagecreatefromstring($imageStream);

        if (!$image instanceof GdImage) {
            throw new LogicException('Unable to build image.');
        }

        return $image;
    }
}

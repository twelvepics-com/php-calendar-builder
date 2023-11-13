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

namespace App\Controller;

use GdImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Json;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ImageController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-12)
 * @since 0.1.0 (2023-11-12) First version.
 */
class ImageController extends AbstractController
{
    private const PATH_CALENDAR = '%s/data/calendar/%s';

    private const PATH_CONFIG = 'data/calendar/%s/config.yml';

    private const PATH_IMAGE = self::PATH_CALENDAR.'/%s';

    private const PATH_FONT = '%s/data/font/OpenSansCondensed-Light.ttf';

    private const ERROR_BACKGROUND_COLOR = [47, 141, 171];

    private const ERROR_TEXT_COLOR = [255, 255, 255];

    private const ERROR_FONT_SIZE_FACTOR = 40;

    private const ERROR_WIDTH = 6000;

    private const ERROR_HEIGHT = 4000;

    /**
     * Returns the given error message as png image response.
     *
     * @param string $message
     * @param string $projectDir
     * @return Response
     */
    private function getErrorResponse(string $message, string $projectDir): Response
    {
        $font = sprintf(self::PATH_FONT, $projectDir);

        $image = imagecreatetruecolor(self::ERROR_WIDTH, self::ERROR_HEIGHT);

        if (!$image instanceof GdImage) {
            throw new LogicException('Unable to create image.');
        }

        $backgroundColor = imagecolorallocate($image, self::ERROR_BACKGROUND_COLOR[0], self::ERROR_BACKGROUND_COLOR[1], self::ERROR_BACKGROUND_COLOR[2]);

        if ($backgroundColor === false) {
            throw new LogicException('Unable to create background color.');
        }

        imagefill($image, 0, 0, $backgroundColor);

        $textColor = imagecolorallocate($image, self::ERROR_TEXT_COLOR[0], self::ERROR_TEXT_COLOR[1], self::ERROR_TEXT_COLOR[2]);

        if ($textColor === false) {
            throw new LogicException('Unable to create text color.');
        }

        $fontSize = (int) self::ERROR_WIDTH / self::ERROR_FONT_SIZE_FACTOR;

        $boundingBox = imageftbbox($fontSize, 0, $font, $message);

        if ($boundingBox === false) {
            throw new LogicException('Unable to create font bounding box.');
        }

        $textX = (self::ERROR_WIDTH - $boundingBox[2]) / 2;
        $textY = (self::ERROR_HEIGHT + $boundingBox[1]) / 2;

        imagefttext($image, $fontSize, 0, (int) $textX, (int) $textY, $textColor, $font, $message);

        ob_start();
        imagepng($image);
        $content = ob_get_clean();

        if ($content === false) {
            throw new LogicException('Unable to create image content.');
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'image/png');
        $response->setContent($content);

        return $response;
    }

    /**
     * The controller to show the image.
     *
     * @param string $identifier
     * @param int $number The number of the page (not month!)
     * @param string $projectDir
     * @return Response
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     */
    #[Route('/v/{identifier}/{number}', name: 'app_get_image')]
    public function showImage(
        string $identifier,
        int $number,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ): Response
    {
        $pathCalendar = sprintf(self::PATH_CALENDAR, $projectDir, $identifier);

        if (!is_dir($pathCalendar)) {
            return $this->getErrorResponse(sprintf('Calendar path "%s" does not exist', $pathCalendar), $projectDir);
        }

        $configFile = new File(sprintf(self::PATH_CONFIG, $identifier), $projectDir);

        if (!$configFile->exist()) {
            return $this->getErrorResponse(sprintf('Config path "%s" does not exist', $configFile->getPath()), $projectDir);
        }

        $configArray = Yaml::parse($configFile->getContentAsText());

        if (!is_array($configArray)) {
            return $this->getErrorResponse(sprintf('Config file "%s" is not an array', $configFile->getPath()), $projectDir);
        }

        $config = new Json($configArray);

        $configKeyPath = ['pages', (string) $number, 'target'];

        if (!$config->hasKey($configKeyPath)) {
            return $this->getErrorResponse(sprintf('Page with number "%d" does not exist', $number), $projectDir);
        }

        $target = $config->getKeyString($configKeyPath);

        $pathImage = sprintf(self::PATH_IMAGE, $projectDir, $identifier, $target);

        if (!file_exists($pathImage)) {
            return $this->getErrorResponse(sprintf('Image path "%s" does not exist', $pathImage), $projectDir);
        }

        $response = new BinaryFileResponse($pathImage);

        /* Set the filename for user */
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($pathImage));

        /* Set mimetype */
        $response->headers->set('Content-Type', 'image/jpeg');

        return $response;
    }
}

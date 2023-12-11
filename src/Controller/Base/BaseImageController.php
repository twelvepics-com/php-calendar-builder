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

namespace App\Controller\Base;

use App\Calendar\ImageBuilder\Base\BaseImageBuilder;
use App\Calendar\Structure\CalendarStructure;
use App\Constants\Service\Calendar\CalendarBuilderService;
use GdImage;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseImageController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-11)
 * @since 0.1.0 (2023-12-11) First version.
 */
class BaseImageController extends AbstractController
{
    protected const FORMAT_HTML = 'html';

    protected const FORMAT_JSON = 'json';

    /**
     * @param CalendarStructure $calendarStructure
     */
    public function __construct(
        protected readonly CalendarStructure $calendarStructure
    )
    {
    }

    /**
     * Returns the format of the request.
     *
     * @param Request $request
     * @param string|null $format
     * @return string
     */
    protected function getFormat(Request $request, string $format = null): string
    {
        if (!is_null($format)) {
            return $format;
        }

        $format = $request->getContentTypeFormat();

        if (!is_null($format)) {
            return $format;
        }

        return self::FORMAT_HTML;
    }

    /**
     * Returns the given error message as png image response.
     *
     * @param string $message
     * @param string $projectDir
     * @return Response
     */
    protected function getErrorResponse(string $message, string $projectDir): Response
    {
        $font = sprintf(CalendarBuilderService::PATH_FONT_ABSOLUTE, $projectDir, BaseImageBuilder::FONT_DEFAULT);

        $image = imagecreatetruecolor(
            CalendarBuilderService::ERROR_WIDTH,
            CalendarBuilderService::ERROR_HEIGHT
        );

        if (!$image instanceof GdImage) {
            throw new LogicException('Unable to create image.');
        }

        $backgroundColor = imagecolorallocate(
            $image,
            CalendarBuilderService::ERROR_BACKGROUND_COLOR[0],
            CalendarBuilderService::ERROR_BACKGROUND_COLOR[1],
            CalendarBuilderService::ERROR_BACKGROUND_COLOR[2]
        );

        if ($backgroundColor === false) {
            throw new LogicException('Unable to create background color.');
        }

        imagefill($image, 0, 0, $backgroundColor);

        $textColor = imagecolorallocate(
            $image,
            CalendarBuilderService::ERROR_TEXT_COLOR[0],
            CalendarBuilderService::ERROR_TEXT_COLOR[1],
            CalendarBuilderService::ERROR_TEXT_COLOR[2]
        );

        if ($textColor === false) {
            throw new LogicException('Unable to create text color.');
        }

        $fontSize = (int) CalendarBuilderService::ERROR_WIDTH / CalendarBuilderService::ERROR_FONT_SIZE_FACTOR;

        $boundingBox = imageftbbox($fontSize, 0, $font, $message);

        if ($boundingBox === false) {
            throw new LogicException('Unable to create font bounding box.');
        }

        $textX = (CalendarBuilderService::ERROR_WIDTH - $boundingBox[2]) / 2;
        $textY = (CalendarBuilderService::ERROR_HEIGHT + $boundingBox[1]) / 2;

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
     * Returns the image from given path.
     *
     * @param string $identifier
     * @param int $number
     * @param array<string, mixed> $page
     * @param int|null $width
     * @return array<string, mixed>
     */
    protected function getImageArray(string $identifier, int $number, array $page, int|null $width = null): array
    {
        $pathFullSize = sprintf('/v/%s/%d', $identifier, $number);

        $path = match (true) {
            is_null($width) => $pathFullSize,
            default => sprintf('/v/%s/%d/%d', $identifier, $number, $width)
        };

        $image = [
            'path' => $path,
            'path_fullsize' => $pathFullSize,
            ...$page
        ];

        if (array_key_exists('page-title', $image)) {
            $image['page_title'] = $image['page-title'];
            unset($image['page-title']);
        }

        if (array_key_exists('design', $image)) {
            unset($image['design']);
        }

        return $image;
    }

    /**
     * Returns the resized image string.
     *
     * @param string $imageString
     * @param int|null $width
     * @return string
     */
    protected function getImageResized(string $imageString, int|null $width): string
    {
        if (is_null($width)) {
            return $imageString;
        }

        $imageCurrent = imagecreatefromstring($imageString);

        if ($imageCurrent === false) {
            throw new LogicException('Unable to create image from string.');
        }

        /* Get the current dimensions. */
        $widthCurrent = imagesx($imageCurrent);
        $heightCurrent = imagesy($imageCurrent);

        /* Calculate the height according to the current aspect ratio */
        $height = intval(round(($width / $widthCurrent) * $heightCurrent));

        $image = imagecreatetruecolor($width, $height);

        if (!$image instanceof GdImage) {
            throw new LogicException('Unable to create image.');
        }

        imagecopyresampled($image, $imageCurrent, 0, 0, 0, 0, $width, $height, $widthCurrent, $heightCurrent);
        imagedestroy($imageCurrent);

        ob_start();
        imagejpeg($image, null, 85);
        $imageStringResized = ob_get_clean();

        if ($imageStringResized === false) {
            throw new LogicException('Unable to create image content.');
        }

        imagedestroy($image);

        return $imageStringResized;
    }

    /**
     * Returns all calendars.
     *
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getCalendarsHtml(): Response
    {
        return $this->render('calendars/show.html.twig', [
            'calendars' => $this->calendarStructure->getCalendars()
        ]);
    }

    /**
     * Returns all calendars.
     *
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getCalendarsJson(): Response
    {
        $calendars = $this->calendarStructure->getCalendars();

        foreach ($calendars as &$calendar) {
            unset($calendar['path']);
            unset($calendar['config']);
        }

        return $this->json($calendars);
    }

    /**
     * Returns the images.
     *
     * @param string $identifier
     * @param string $projectDir
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getImagesHtml(
        string $identifier,
        string $projectDir,
    ): Response
    {
        $config = $this->calendarStructure->getConfig($identifier);

        if ($config->hasKey('error')) {
            return $this->getErrorResponse($config->getKeyString('error'), $projectDir);
        }

        $configKeyPath = ['pages'];

        if (!$config->hasKey($configKeyPath)) {
            return $this->getErrorResponse('Pages key do not exist.', $projectDir);
        }

        $pages = $config->getKeyArray($configKeyPath);

        $images = [];
        foreach ($pages as $number => $page) {
            if (!is_int($number)) {
                continue;
            }

            if (!is_array($page)) {
                continue;
            }

            $images[] = $this->getImageArray($identifier, $number, $page, 1280);
        }

        return $this->render('images/show.html.twig', [
            'images' => $images
        ]);
    }

    /**
     * Returns the image.
     *
     * @param string $identifier
     * @param int $number
     * @param int|null $width
     * @param string $format
     * @param string|null $projectDir
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getImage(
        string $identifier,
        int $number,
        int|null $width,
        string $format = 'jpg',
        string $projectDir = null
    ): Response
    {
        if (is_null($projectDir)) {
            throw new LogicException('Unable to get project dir.');
        }

        $config = $this->calendarStructure->getConfig($identifier);

        if ($config->hasKey('error')) {
            return $this->getErrorResponse($config->getKeyString('error'), $projectDir);
        }

        $configKeyPath = ['pages', (string) $number, 'target'];

        if (!$config->hasKey($configKeyPath)) {
            return $this->getErrorResponse(sprintf('Page with number "%d" does not exist', $number), $projectDir);
        }

        $target = $config->getKeyString($configKeyPath);

        $pathImage = sprintf(CalendarBuilderService::PATH_IMAGE_ABSOLUTE, $projectDir, $identifier, $target);

        if (!file_exists($pathImage)) {
            return $this->getErrorResponse(sprintf('Image path "%s" does not exist', $pathImage), $projectDir);
        }

        $imageString = file_get_contents($pathImage);

        if (!is_string($imageString)) {
            return $this->getErrorResponse(sprintf('Unable to get the content of image path "%s".', $pathImage), $projectDir);
        }

        $imageString = $this->getImageResized($imageString, $width);

        $response = new Response();

        /* Set headers */
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'image/jpeg');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s";', basename($pathImage)));
        $response->headers->set('Content-length',  (string) strlen($imageString));

        /* Send headers before outputting anything */
        $response->sendHeaders();
        $response->setContent($imageString);
        $response->sendContent();

        return $response;
    }
}

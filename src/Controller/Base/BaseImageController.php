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
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use GdImage;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class BaseImageController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-11)
 * @since 0.1.0 (2023-12-11) First version.
 */
class BaseImageController extends AbstractController
{
    /**
     * @param CalendarStructure $calendarStructure
     * @param KernelInterface $appKernel
     */
    public function __construct(
        protected readonly CalendarStructure $calendarStructure,
        protected KernelInterface $appKernel
    )
    {
    }

    /**
     * Returns the format from the request header.
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

        return Format::HTML;
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
     * Returns all calendars as json response.
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
    protected function doShowCalendarsJson(): Response
    {
        $calendars = $this->calendarStructure->getCalendars(Format::JSON);

        foreach ($calendars as &$calendar) {
            unset($calendar['path']);
            unset($calendar['config']);
        }

        return $this->json($calendars);
    }

    /**
     * Returns all calendars as html response.
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
    protected function doShowCalendarsHtml(): Response
    {
        return $this->render('calendars/show.html.twig', [
            'calendars' => $this->calendarStructure->getCalendars()
        ]);
    }

    /**
     * Returns the images as json response.
     *
     * @param string $identifier
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function doShowImagesJson(
        string $identifier
    ): Response
    {
        $images = $this->calendarStructure->getImages($identifier);

        if (is_null($images)) {
            return $this->json(['error' => sprintf('Unable to get images from given identifier "%s".', $identifier)]);
        }

        return $this->json($images);
    }

    /**
     * Returns the images as html response.
     *
     * @param string $identifier
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function doShowImagesHtml(
        string $identifier
    ): Response
    {
        $images = $this->calendarStructure->getImages($identifier);

        if (is_null($images)) {
            return $this->getErrorResponse(sprintf('Unable to get images from given identifier "%s".', $identifier), $this->appKernel->getProjectDir());
        }

        return $this->render('images/show.html.twig', [
            'images' => $images
        ]);
    }

    /**
     * Returns the image as image response.
     *
     * @param string $identifier
     * @param int $number
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws InvalidArgumentException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doShowImage(
        string $identifier,
        int $number,
        int|null $width,
        int|null $quality,
        string $format
    ): Response
    {
        $file = $this->calendarStructure->getImageFile($identifier, $number);

        if (!$file instanceof File) {
            return $this->getErrorResponse($file, $this->appKernel->getProjectDir());
        }

        $imageString = $this->calendarStructure->getImageStringFromCache($file, $width, $quality, $format);

        if (is_null($imageString)) {
            return $this->getErrorResponse(sprintf('The given image file "%s" is not an image or it is not possible to create an image string.', $file->getPath()), $this->appKernel->getProjectDir());
        }

        $response = new Response();

        /* Set headers */
        $response->headers->set('Cache-Control', 'max-age=86400');
        $response->headers->set('Content-type', 'image/jpeg');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s";', basename($file->getPath())));
        $response->headers->set('Content-length',  (string) strlen($imageString));

        /* Send headers before outputting anything */
        $response->sendHeaders();
        $response->setContent($imageString);
        $response->sendContent();

        return $response;
    }
}

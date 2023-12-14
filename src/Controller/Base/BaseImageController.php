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
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
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

    private string|null $projectDirectory = null;

    /**
     * @param CalendarStructure $calendarStructure
     */
    public function __construct(
        protected readonly CalendarStructure $calendarStructure,
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
     * Returns the image path.
     *
     * Description:
     * ------------
     * Return a Response object if an error occurred, otherwise returns the image path.
     *
     * @param string $identifier
     * @param int $number
     * @return File|Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    protected function getFile(
        string $identifier,
        int $number
    ): File|Response
    {
        if (is_null($this->projectDirectory)) {
            throw new LogicException('Unable to get project dir.');
        }

        $config = $this->calendarStructure->getConfig($identifier);

        if ($config->hasKey('error')) {
            return $this->getErrorResponse($config->getKeyString('error'), $this->projectDirectory);
        }

        $configKeyPath = ['pages', (string) $number, 'target'];

        if (!$config->hasKey($configKeyPath)) {
            return $this->getErrorResponse(sprintf('Page with number "%d" does not exist', $number), $this->projectDirectory);
        }

        $target = $config->getKeyString($configKeyPath);

        $imagePath = sprintf(CalendarBuilderService::PATH_IMAGE_RELATIVE, $identifier, $target);

        $file = new File($imagePath, $this->projectDirectory);

        if (!$file->exist()) {
            return $this->getErrorResponse(sprintf('Image path "%s" does not exist.', $imagePath), $this->projectDirectory);
        }

        return $file;
    }

    /**
     * Returns the image string.
     *
     * @param File $file
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return string|Response
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function getImageString(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = 'jpg',
    ): string|Response
    {
        if (is_null($this->projectDirectory)) {
            throw new LogicException('Unable to get project dir.');
        }

        $image = new Image($file);

        if (!$image->isImage()) {
            return $this->getErrorResponse(sprintf('The given image "%s" file is not an image.', $file->getPath()), $this->projectDirectory);
        }

        $imageString = $image->getImageString($width, $format, $quality);

        if (is_null($imageString)) {
            return $this->getErrorResponse('Unable to get image string.', $this->projectDirectory);
        }

        return $imageString;
    }

    /**
     * Returns the image.
     *
     * @param string $identifier
     * @param int $number
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @param string|null $projectDirectory
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doShowImage(
        string $identifier,
        int $number,
        int|null $width,
        int|null $quality,
        string $format = 'jpg',
        string $projectDirectory = null
    ): Response
    {
        $this->projectDirectory = $projectDirectory;

        $file = $this->getFile($identifier, $number);

        if ($file instanceof Response) {
            return $file;
        }

        $imageString = $this->getImageString($file, $width, $quality, $format);

        if ($imageString instanceof Response) {
            return $imageString;
        }

        $response = new Response();

        /* Set headers */
        $response->headers->set('Cache-Control', 'private');
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

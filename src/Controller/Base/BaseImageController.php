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

use App\Cache\RedisCache;
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
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;

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

    protected const ALLOWED_IMAGE_FORMATS = [Image::FORMAT_JPG, Image::FORMAT_PNG];

    private string|null $projectDirectory = null;

    /**
     * @param CalendarStructure $calendarStructure
     * @param RedisCache $redisCache
     */
    public function __construct(
        protected readonly CalendarStructure $calendarStructure,
        protected readonly RedisCache $redisCache
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
     * Returns the image string callable for the cache.
     *
     * @param File $file
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return callable
     */
    private function getImageStringCallable(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = 'jpg'
    ): callable
    {
        return function (ItemInterface $item) use ($file, $width, $format, $quality): string|null {
            $item->expiresAfter(3600);

            $image = new Image($file);

            if (!$image->isImage()) {
                return null;
            }

            return $image->getImageString($width, $format, $quality);
        };
    }

    /**
     * Returns the image string.
     *
     * @param File $file
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @return string|Response
     * @throws InvalidArgumentException
     */
    private function getImageString(
        File $file,
        int|null $width,
        int|null $quality,
        string $format = 'jpg'
    ): string|Response
    {
        if (is_null($this->projectDirectory)) {
            throw new LogicException('Unable to get project dir.');
        }

        /* Write or read the cached image string. */
        $imageString = $this->redisCache->getStringOrNull(
            $this->redisCache->getCacheKey($file->getPath(), $width, $quality, $format),
            $this->getImageStringCallable($file, $width, $quality, $format)
        );

        if (is_null($imageString)) {
            return $this->getErrorResponse(sprintf('The given image "%s" file is not an image or it is not possible to generate an image string.', $file->getPath()), $this->projectDirectory);
        }

        return $imageString;
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
        $calendars = $this->calendarStructure->getCalendars();

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
     * Returns the images as html response.
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
    protected function doShowImagesHtml(
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

            $images[] = $this->getImageArray($identifier, $number, $page, $number === 0 ? 1280 : 640);
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
     * @param string|null $projectDirectory
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

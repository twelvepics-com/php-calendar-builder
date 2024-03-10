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
use App\Constants\Conversion;
use App\Constants\Event;
use App\Constants\Format;
use App\Constants\Service\Calendar\CalendarBuilderService;
use GdImage;
use Ixnode\PhpApiVersionBundle\Utils\Version\Version;
use Ixnode\PhpContainer\File;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpException\Type\TypeInvalidException;
use Ixnode\PhpNamingConventions\Exception\FunctionReplaceException;
use JsonException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;

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
     * @param Stopwatch $stopwatch
     */
    public function __construct(
        protected readonly CalendarStructure $calendarStructure,
        protected KernelInterface $appKernel,
        protected Stopwatch $stopwatch
    )
    {
        $stopwatch->start(Event::IMAGE_CONTROLLER);
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
     * @throws FunctionReplaceException
     */
    protected function doShowCalendarsAsJson(): Response
    {
        $calendars = $this->calendarStructure->getCalendars(
            format: Format::JSON,
            onlyPublic: true
        );

        return $this->json(
            $this->getApiResponseSuccess(
                [
                    'calendars' => $calendars,
                ],
                [],
                [
                    'calendars' => $this->countRecursive($calendars),
                ],
            )
        );
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
     * @throws FunctionReplaceException
     */
    protected function doShowCalendarsAsHtml(): Response
    {
        return $this->render('calendars/show.html.twig', [
            'calendars' => $this->calendarStructure->getCalendars()
        ]);
    }

    /**
     * Returns the calendar as json response.
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
     * @throws FunctionReplaceException
     */
    protected function doShowCalendarAsJson(
        string $identifier
    ): Response
    {
        $given = [
            'identifier' => $identifier,
        ];

        $calendar = $this->calendarStructure->getCalendar($identifier);

        if (is_null($calendar)) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get calendar from given identifier "%s".', $identifier),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        if (!array_key_exists('pages', $calendar) || !is_array($calendar['pages'])) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get calendar from given identifier "%s".', $identifier),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        if (!array_key_exists('holidays', $calendar) || !is_array($calendar['holidays'])) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get holidays from given identifier "%s".', $identifier),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        if (!array_key_exists('birthdays', $calendar) || !is_array($calendar['birthdays'])) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get birthdays from given identifier "%s".', $identifier),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->getApiResponseSuccess(
            $calendar,
            $given,
            [
                'pages' => $this->countRecursive($calendar['pages']),
                'holidays' => $this->countRecursive($calendar['holidays']),
                'birthdays' => $this->countRecursive($calendar['birthdays']),
            ]
        ));
    }

    /**
     * Returns the calendar as html response.
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
     * @throws FunctionReplaceException
     */
    protected function doShowCalendarAsHtml(
        string $identifier
    ): Response
    {
        $calendar = $this->calendarStructure->getCalendar($identifier);

        if (is_null($calendar)) {
            return $this->getErrorResponse(sprintf('Unable to get images from given identifier "%s".', $identifier), $this->appKernel->getProjectDir());
        }

        return $this->render('calendar/show.html.twig', [
            'calendar' => $calendar
        ]);
    }

    /**
     * Returns the page/image as json response.
     *
     * @param string $identifier
     * @param int $number
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws FunctionReplaceException
     */
    protected function doShowPageAsJson(
        string $identifier,
        int $number,
    ): Response
    {
        $given = [
            'identifier' => $identifier,
            'number' => $number,
        ];

        $image = $this->calendarStructure->getImage($identifier, $number);

        if (is_null($image)) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get image from given identifier "%s" and number "%d".', $identifier, $number),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        if (!array_key_exists('holidays', $image) || !is_array($image['holidays'])) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get holidays from given identifier "%s" and number "%d".', $identifier, $number),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        if (!array_key_exists('birthdays', $image) || !is_array($image['birthdays'])) {
            return $this->json($this->getApiResponseError(
                sprintf('Unable to get birthdays from given identifier "%s" and number "%d".', $identifier, $number),
                $given
            ), Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $this->getApiResponseSuccess(
                $image,
                $given,
                [
                    'holidays' => $this->countRecursive($image['holidays']),
                    'birthdays' => $this->countRecursive($image['birthdays']),
                ]
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Returns the page/image as image response.
     *
     * @param string $identifier
     * @param int $number
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @param string $imageType
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws FunctionReplaceException
     */
    protected function doShowPageAsImage(
        string $identifier,
        int $number,
        int|null $width,
        int|null $quality,
        string $format,
        string $imageType = CalendarStructure::IMAGE_TYPE_TARGET
    ): Response
    {
        $file = $this->calendarStructure->getImageFile($identifier, $number, $imageType);

        if (!$file instanceof File) {
            return $this->getErrorResponse($file, $this->appKernel->getProjectDir());
        }

        $imageString = $this->calendarStructure->getImageStringFromCache($file, $width, $quality, $format);

        if (is_null($imageString)) {
            return $this->getErrorResponse(sprintf('The given image file "%s" is not an image or it is not possible to create an image string.', $file->getPath()), $this->appKernel->getProjectDir());
        }

        $response = new Response();

        /* Set headers */
        $response->headers->set('Cache-Control', 'max-age=2592000');
        $response->headers->set('Content-type', 'image/jpeg');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s";', basename($file->getPath())));
        $response->headers->set('Content-length',  (string) strlen($imageString));

        /* Send headers before outputting anything */
        $response->sendHeaders();
        $response->setContent($imageString);
        $response->sendContent();

        return $response;
    }

    /**
     * Returns the api response including the version, date, etc.
     *
     * @param array<int|string, mixed> $data
     * @param array<string, int|string> $given
     * @param array<string, int>|null $count
     * @return array<string, mixed>
     */
    private function getApiResponseSuccess(array $data, array $given = [], array $count = null): array
    {
        $event = $this->stopwatch->stop(Event::IMAGE_CONTROLLER);

        $timeTaken = sprintf('%.3f s', $event->getDuration() / Conversion::MILLISECONDS_TO_SECONDS);
        $memoryTaken = sprintf('%.3f MB', $event->getMemory() / Conversion::BYTES_TO_MEGABYTES);

        return [
            'data' => $data,
            ...(is_null($count) ? [] : ['count' => $count]),
            'given' => $given,
            'valid' => true,
            'date' => date('c'),
            'time-taken' => $timeTaken,
            'memory-taken' => $memoryTaken,
            'version' => (new Version())->getVersion(),
        ];
    }

    /**
     * Returns an error response including the version, date, etc.
     *
     * @param string $error
     * @param array<string, int|string> $given
     * @return array<string, mixed>
     */
    private function getApiResponseError(string $error, array $given = []): array
    {
        $event = $this->stopwatch->stop(Event::IMAGE_CONTROLLER);

        $timeTaken = sprintf('%.3f s', $event->getDuration() / Conversion::MILLISECONDS_TO_SECONDS);
        $memoryTaken = sprintf('%.3f MB', $event->getMemory() / Conversion::BYTES_TO_MEGABYTES);

        return [
            'error' => $error,
            'given' => $given,
            'valid' => false,
            'date' => date('c'),
            'time-taken' => $timeTaken,
            'memory-taken' => $memoryTaken,
            'version' => (new Version())->getVersion(),
        ];
    }

    /**
     * Counts the given array recursively.
     *
     * @param array<int|string, mixed> $array
     * @return int
     */
    private function countRecursive(array $array): int
    {
        $count = 0;

        foreach ($array as $element) {
            if (!is_array($element)) {
                $count++;
                continue;
            }

            if (!array_is_list($element)) {
                $count++;
                continue;
            }

            $count += $this->countRecursive($element);
        }

        return $count;
    }
}

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

use App\Constants\Format;
use App\Controller\Base\BaseImageController;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ImageController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-11-12)
 * @since 0.1.0 (2023-11-12) First version.
 */
class ImageController extends BaseImageController
{
    /**
     * The controller to show the root page.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/', name: 'app_home')]
    public function home(Request $request): Response
    {
        $format = $this->getFormat($request);

        return $this->forward('App\Controller\ImageController::showCalendars', [
            'format' => $format
        ]);
    }

    /**
     * The controller to show the root page.
     *
     * @param Request $request
     * @param string|null $format
     * @return Response
     */
    #[Route('/index.{format}', name: 'app_index')]
    public function index(Request $request, string|null $format = null): Response
    {
        $format = $this->getFormat($request, $format);

        return $this->forward('App\Controller\ImageController::showCalendars', [
            'format' => $format
        ]);
    }

    /**
     * The controller to show the image.
     *
     * @param Request $request
     * @param string|null $format
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    #[Route('/v.{format}', name: 'app_get_calendars')]
    public function showCalendars(Request $request, string|null $format = null): Response
    {
        $format = $this->getFormat($request, $format);

        return match ($format) {
            Format::HTML => $this->doShowCalendarsHtml(),
            Format::JSON => $this->doShowCalendarsJson(),
            default => throw new LogicException(sprintf('Format "%s" not supported yet.', $format)),
        };
    }

    /**
     * The controller to show the image.
     *
     * @param string $projectDir
     * @param Request $request
     * @param string $identifier
     * @param string|null $format
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    #[Route('/v/{identifier}/all.{format}', name: 'app_get_images')]
    public function showImages(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        Request $request,
        string $identifier,
        string|null $format = null
    ): Response
    {
        $format = $this->getFormat($request, $format);

        return match ($format) {
            Format::HTML => $this->doShowImagesHtml($identifier, $projectDir),
            Format::JSON => $this->doShowImagesJson($identifier),
            default => throw new LogicException(sprintf('Format "%s" not supported yet.', $format)),
        };
    }

    /**
     * The controller to show the image.
     *
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0.jpg
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0.png
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0.jpg?width=500&quality=50
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0.png?width=500
     * @example etc.
     *
     * @param string $projectDir
     * @param string $identifier
     * @param int $number The number of the page (not month!)
     * @param string $format
     * @param int|null $width
     * @param int|null $quality
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws TypeInvalidException
     */
    #[Route('/v/{identifier}/{number}.{format}', name: 'app_get_image')]
    public function showImage(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        string $identifier,
        int $number,
        string $format = 'jpg',
        #[MapQueryParameter] int|null $width = null,
        #[MapQueryParameter] int|null $quality = null
    ): Response
    {
        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $projectDir);
        }

        return $this->doShowImage($identifier, $number, $width, $quality, $format, $projectDir);
    }

    /**
     * The controller to show the image (with given width).
     *
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0/500.jpg
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0/1024.jpg
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0/1024.jpg?quality=85
     * @example etc.
     *
     * @param string $projectDir
     * @param string $identifier
     * @param int $number The number of the page (not month!)
     * @param int|null $width
     * @param string $format
     * @param int|null $quality
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/v/{identifier}/{number}/{width}.{format}', name: 'app_get_image_width')]
    public function showImageWidth(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        string $identifier,
        int $number,
        int|null $width,
        string $format = 'jpg',
        #[MapQueryParameter] int|null $quality = null
    ): Response
    {
        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $projectDir);
        }

        return $this->doShowImage($identifier, $number, $width, $quality, $format, $projectDir);
    }

    /**
     * The controller to show the image (with given width and quality).
     *
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0/500/85.jpg
     * @example https://www.calendar-builder.localhost/v/9cbdf13be284/0/1024/50.jpg
     * @example etc.
     *
     * @param string $projectDir
     * @param string $identifier
     * @param int $number The number of the page (not month!)
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
    #[Route('/v/{identifier}/{number}/{width}/{quality}.{format}', name: 'app_get_image_width_quality')]
    public function showImageWidthQuality(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        string $identifier,
        int $number,
        int|null $width,
        int|null $quality,
        string $format = 'jpg'
    ): Response
    {
        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $projectDir);
        }

        return $this->doShowImage($identifier, $number, $width, $quality, $format, $projectDir);
    }
}

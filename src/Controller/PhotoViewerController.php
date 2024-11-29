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

use App\Calendar\Structure\CalendarStructure;
use App\Constants\Format;
use App\Constants\ImageType;
use App\Controller\Base\BasePhotoViewerController;
use Ixnode\PhpContainer\Image;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhotoViewerController
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-11-28)
 * @since 0.1.0 (2024-11-28) First version.
 */
class PhotoViewerController extends BasePhotoViewerController
{
    /**
     * The controller to show the photo viewer root page.
     *
     * Examples:
     * - https://www.calendar-builder.localhost/pv
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/pv', name: 'photo_viewer_home')]
    public function home(
        Request $request
    ): Response
    {
        $format = $this->getFormat($request);

        /* @link self::showPhotoSets() */
        return $this->forward('App\Controller\PhotoViewerController::showPhotoSets', [
            'format' => $format
        ]);
    }

    /**
     * The controller to show the photo viewer root page.
     *
     * Examples:
     *  - https://www.calendar-builder.localhost/pv/index.json
     *  - https://www.calendar-builder.localhost/pv/index.html
     *
     * @param Request $request
     * @param string|null $format
     * @return Response
     */
    #[Route('/pv/index.{format}', name: 'photo_viewer_index')]
    public function index(
        Request $request,
        string|null $format = null
    ): Response
    {
        $format = $this->getFormat($request, $format);

        /* @link self::showPhotoSets() */
        return $this->forward('App\Controller\PhotoViewerController::showPhotoSets', [
            'format' => $format
        ]);
    }

    /**
     * The controller to show the photo sets.
     *
     * Examples:
     * - https://www.calendar-builder.localhost/pv.json
     * - https://www.calendar-builder.localhost/pv.html
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
     * @throws FunctionReplaceException
     */
    #[Route('/pv.{format}', name: 'photo_viewer_get_photo_sets')]
    public function showPhotoSets(
        Request $request,
        string|null $format = null
    ): Response
    {
        $format = $this->getFormat($request, $format);

        return match ($format) {
            Format::HTML => $this->doShowPhotoSetsAsHtml(),
            Format::JSON => $this->doShowPhotoSetsAsJson(),
            default => throw new LogicException(sprintf('Format "%s" not supported yet.', $format)),
        };
    }

    /**
     * The controller to show the calendar.
     *
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83.json
     *
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
     * @throws FunctionReplaceException
     */
    #[Route('/pv/{identifier}.{format}', name: 'photo_viewer_get_photo_set')]
    public function showPhotoSet(
        Request $request,
        string $identifier,
        string|null $format = null
    ): Response
    {
        $format = $this->getFormat($request, $format);

        return match ($format) {
            Format::HTML => $this->doShowPhotoSetAsHtml($identifier),
            Format::JSON => $this->doShowPhotoSetAsJson($identifier),
            default => throw new LogicException(sprintf('Format "%s" not supported yet.', $format)),
        };
    }

    /**
     * The controller to show the image/page or show the json data from the page.
     *
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619.json
     *
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619.jpg
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619.png
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619.jpg?width=500&quality=50
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619.jpg?width=500
     * @example etc.
     *
     * @param string $identifier
     * @param string $name
     * @param string $format
     * @param int|null $width
     * @param int|null $quality
     * @param string $type
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws TypeInvalidException
     * @throws CaseUnsupportedException
     * @throws ParserException
     * @throws FunctionReplaceException
     */
    #[Route('/pv/{identifier}/{name}.{format}', name: 'photo_viewer_get_image')]
    public function showPage(
        string $identifier,
        string $name,
        string $format = Image::FORMAT_JPG,
        #[MapQueryParameter] int|null $width = null,
        #[MapQueryParameter] int|null $quality = null,
        #[MapQueryParameter] string $type = CalendarStructure::IMAGE_TYPE_TARGET
    ): Response
    {
        if ($format === Format::JSON) {
            return $this->doShowPhotoAsJson($identifier, $name);
        }

        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $this->appKernel->getProjectDir());
        }

        if (!in_array($type, ImageType::ALLOWED_IMAGE_TYPES)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $type), $this->appKernel->getProjectDir());
        }

        return $this->doShowPhotoAsImage($identifier, $name, $width, $quality, $format, $type);
    }

    /**
     * The controller to show the image (with given width).
     *
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619/500.jpg
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619/1024.jpg
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619/1024.jpg?quality=85
     * @example etc.
     *
     * @param string $identifier
     * @param string $name The name of the photo
     * @param int|null $width
     * @param string $format
     * @param int|null $quality
     * @param string $type
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/pv/{identifier}/{name}/{width}.{format}', name: 'photo_viewer_get_image_width')]
    public function showImageWidth(
        string $identifier,
        string $name,
        int|null $width,
        string $format = Image::FORMAT_JPG,
        #[MapQueryParameter] int|null $quality = null,
        #[MapQueryParameter] string $type = CalendarStructure::IMAGE_TYPE_TARGET
    ): Response
    {
        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $this->appKernel->getProjectDir());
        }

        if (!in_array($type, ImageType::ALLOWED_IMAGE_TYPES)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $type), $this->appKernel->getProjectDir());
        }

        return $this->doShowPhotoAsImage($identifier, $name, $width, $quality, $format);
    }

    /**
     * The controller to show the image (with given width and quality).
     *
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619/500/85.jpg
     * @example https://www.calendar-builder.localhost/pv/a15f16437c83/b50593fbd619/1024/30.jpg
     * @example etc.
     *
     * @param string $identifier
     * @param string $name The name of the photo
     * @param int|null $width
     * @param int|null $quality
     * @param string $format
     * @param string $type
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/pv/{identifier}/{name}/{width}/{quality}.{format}', name: 'photo_viewer_get_image_width_quality')]
    public function showImageWidthQuality(
        string $identifier,
        string $name,
        int|null $width,
        int|null $quality,
        string $format = Image::FORMAT_JPG,
        #[MapQueryParameter] string $type = CalendarStructure::IMAGE_TYPE_TARGET
    ): Response
    {
        if (!in_array($format, Format::ALLOWED_IMAGE_FORMATS)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $format), $this->appKernel->getProjectDir());
        }

        if (!in_array($type, ImageType::ALLOWED_IMAGE_TYPES)) {
            return $this->getErrorResponse(sprintf('The given image format "%s" is not supported yet. Add more if needed.', $type), $this->appKernel->getProjectDir());
        }

        return $this->doShowPhotoAsImage($identifier, $name, $width, $quality, $format);
    }
}

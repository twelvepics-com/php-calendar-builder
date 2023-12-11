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

use App\Controller\Base\BaseImageController;
use Ixnode\PhpException\ArrayType\ArrayKeyNotFoundException;
use Ixnode\PhpException\Case\CaseInvalidException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Function\FunctionJsonEncodeException;
use Ixnode\PhpException\Type\TypeInvalidException;
use JsonException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            BaseImageController::FORMAT_HTML => $this->getCalendarsHtml(),
            BaseImageController::FORMAT_JSON => $this->getCalendarsJson(),
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

        if ($format !== BaseImageController::FORMAT_HTML) {
            throw new LogicException(sprintf('Format "%s" not supported yet.', $format));
        }

        return $this->getImagesHtml($identifier, $projectDir);
    }

    /**
     * The controller to show the image.
     *
     * @param int $number The number of the page (not month!)
     * @param string|null $projectDir
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     */
    #[Route('/v/{identifier}/{number}.{format}', name: 'app_get_image')]
    public function showImage(
        string $identifier,
        int $number,
        string $format = 'jpg',
        #[Autowire('%kernel.project_dir%')]
        string $projectDir = null
    ): Response
    {
        return $this->getImage($identifier, $number, null, $format, $projectDir);
    }

    /**
     * The controller to show the image.
     *
     * @param string $identifier
     * @param int $number The number of the page (not month!)
     * @param int|null $width
     * @param string $format
     * @param string|null $projectDir
     * @return Response
     * @throws ArrayKeyNotFoundException
     * @throws CaseInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws FunctionJsonEncodeException
     * @throws TypeInvalidException
     * @throws JsonException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/v/{identifier}/{number}/{width}.{format}', name: 'app_get_image_width')]
    public function showImageWidth(
        string $identifier,
        int $number,
        int|null $width,
        string $format = 'jpg',
        #[Autowire('%kernel.project_dir%')]
        string $projectDir = null
    ): Response
    {
        return $this->getImage($identifier, $number, $width, $format, $projectDir);
    }
}

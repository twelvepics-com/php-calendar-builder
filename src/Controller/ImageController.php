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
use App\Constants\Service\Calendar\CalendarBuilderService;
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
     * @param CalendarStructure $calendarStructure
     */
    public function __construct(
        private readonly CalendarStructure $calendarStructure
    )
    {
    }

    /**
     * The controller to show the image.
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
    #[Route('/v', name: 'app_get_calendars')]
    public function showCalendars(): Response
    {
        return $this->render('calendars/show.html.twig', [
            'calendars' => $this->calendarStructure->getCalendars()
        ]);
    }

    /**
     * The controller to show the root page.
     *
     * @return Response
     */
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        return $this->forward('App\Controller\ImageController::showCalendars');
    }

    /**
     * The controller to show the image.
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
    #[Route('/v/{identifier}/all', name: 'app_get_images')]
    public function showImages(
        string $identifier,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
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
     * @throws JsonException
     * @throws TypeInvalidException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Route('/v/{identifier}/{number}/{width?}.{_format?jpg}', name: 'app_get_image')]
    public function showImage(
        string $identifier,
        int $number,
        int|null $width,
        string $format = 'jpg',
        #[Autowire('%kernel.project_dir%')]
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

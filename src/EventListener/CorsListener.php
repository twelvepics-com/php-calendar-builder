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

namespace App\EventListener;

use App\Constants\Header;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class CorsListener
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-12-17)
 * @since 0.1.0 (2023-12-17) First version.
 */
final class CorsListener implements EventSubscriberInterface
{
    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(protected ParameterBagInterface $parameterBag)
    {
    }

    /**
     * Enables this class for all kernel requests.
     *
     * @return array<string, array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
            KernelEvents::RESPONSE => ['onKernelResponse', 9999],
        ];
    }

    /**
     * Enables an empty OPTIONS request.
     *
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->isValidOptionRequest($event)) {
            return;
        }

        $response = new Response();

        $event->setResponse($response);
    }

    /**
     * Add CORS header to OPTIONS response.
     *
     * @param ResponseEvent $event
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->isValidOptionRequest($event)) {
            return;
        }

        $response = $event->getResponse();

        /* Allowed methods. */
        $methods = [
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
        ];

        /* Allowed header fields. */
        $headers = [
            Header::CONTENT_TYPE_HEADER,
            Header::AUTHORIZATION_HEADER,
        ];

        $response->headers->set('Access-Control-Allow-Origin', $event->getRequest()->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $methods));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $headers));
    }

    /**
     * Returns true if this is a valid OPTIONS request.
     *
     * @param RequestEvent|ResponseEvent $event
     * @return bool
     */
    protected function isValidOptionRequest(RequestEvent|ResponseEvent $event): bool
    {
        $request = $event->getRequest();
        $method = $request->getRealMethod();

        if ($method !== Request::METHOD_OPTIONS) {
            return false;
        }

        $origin = $request->headers->get('Origin');
        $originRegexp = $this->parameterBag->get('api.cors_allow_origin');

        if (is_null($origin)) {
            return false;
        }

        if (!is_string($originRegexp)) {
            return false;
        }

        if (!preg_match(sprintf('~%s~', $originRegexp), $origin)) {
            return false;
        }

        return true;
    }
}


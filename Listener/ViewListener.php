<?php

namespace Maba\Bundle\OAuthCommerceProxyBundle\Listener;

use Maba\Bundle\OAuthCommerceCommonBundle\Response\OAuthAccessTokenResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ViewListener
{

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        if ($result instanceof OAuthAccessTokenResponse) {
            $defaultHeaders = array(
                'x-oauth-commerce-proxy-special-response' => 'access_token',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            );
            $event->setResponse(
                new JsonResponse($result->getAccessToken()->toArray(), 200, $result->getHeaders() + $defaultHeaders)
            );
        }
    }
}
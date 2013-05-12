<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Exception;


class CodeRequestRedirectException extends CodeRequestException
{
    protected $redirectUri;

    public function __construct($errorCode, $redirectUri, $message = '', $previous = null)
    {
        $this->redirectUri = $redirectUri;
        parent::__construct($errorCode, $message, $previous);
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }
}
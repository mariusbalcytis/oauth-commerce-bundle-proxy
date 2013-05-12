<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Exception;


class CodeRequestException extends \Exception
{
    protected $errorCode;

    public function __construct($errorCode, $message = '', $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
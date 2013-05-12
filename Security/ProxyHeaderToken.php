<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ProxyHeaderToken extends AbstractToken
{
    /**
     * @var string
     */
    protected $providerKey;

    /**
     * Returns the user credentials.
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return null;
    }

    /**
     * @param string $providerKey
     *
     * @return $this
     */
    public function setProviderKey($providerKey)
    {
        $this->providerKey = $providerKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    public function isTypeClient()
    {
        return $this->getAttribute('type') !== 'application';
    }



}
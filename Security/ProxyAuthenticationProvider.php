<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Security;

use Maba\Bundle\OAuthCommerceCommonBundle\Security\AccessTokenData;
use Maba\Bundle\OAuthCommerceCommonBundle\Security\OAuthCredentialsToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ProxyAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $providerKey;

    /**
     * @var string
     */
    protected $algorithm;

    /**
     * @var UserProviderInterface
     */
    protected $clientUserProvider;

    /**
     * @var UserProviderInterface
     */
    protected $applicationUserProvider;


    /**
     * @param string $providerKey
     */
    public function setProviderKey($providerKey)
    {
        $this->providerKey = $providerKey;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $algorithm
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     */
    public function setClientUserProvider($userProvider)
    {
        $this->clientUserProvider = $userProvider;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     */
    public function setApplicationUserProvider($userProvider)
    {
        $this->applicationUserProvider = $userProvider;
    }

    /**
     * Attempts to authenticates a TokenInterface object.
     *
     * @param TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        if ($token instanceof ProxyHeaderToken && $this->checkSignature($token)) {

            if ($token->isTypeClient()) {
                $client = $this->clientUserProvider->loadUserByUsername($token->getAttribute('id'));
                $application = null;
            } else {
                if ($this->applicationUserProvider === null) {
                    throw new AuthenticationException('Application authentication disabled');
                }
                $application = $this->applicationUserProvider->loadUserByUsername($token->getAttribute('id'));
                $client = null;
            }

            if ($client) {
                $authToken = new OAuthCredentialsToken($client->getRoles());
                $authToken->setClient($client);
            } elseif ($application) {
                $authToken = new OAuthCredentialsToken($application->getRoles());
                $authToken->setApplication($application);
            }

            if (isset($authToken)) {
                $authToken->setAuthenticated(true);
                $authToken->setCredentialsId($token->getAttribute('credentials_id'));

                $accessTokenArray = $token->getAttribute('access_token');
                if ($accessTokenArray) {
                    $authToken->setAccessTokenData(new AccessTokenData(
                        $accessTokenArray['user_id'],
                        isset($accessTokenArray['scopes']) ? $accessTokenArray['scopes'] : array()
                    ));
                }

                return $authToken;
            }
        }

        throw new AuthenticationException('Failed to authenticate by proxy header');
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param TokenInterface $token A TokenInterface instance
     *
     * @return Boolean true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof ProxyHeaderToken && $token->getProviderKey() === $this->providerKey;
    }

    /**
     * @param ProxyHeaderToken $token
     *
     * @return bool
     */
    protected function checkSignature(ProxyHeaderToken $token)
    {
        $expected = base64_encode(hash_hmac($this->algorithm, $token->getAttribute('normalized'), $this->secret, true));
        return $expected === $token->getAttribute('signature');
    }

}
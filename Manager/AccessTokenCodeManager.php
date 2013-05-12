<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Manager;

use Maba\Bundle\OAuthCommerceProxyBundle\Exception\CodeRequestException;
use Maba\Bundle\OAuthCommerceProxyBundle\Exception\CodeRequestRedirectException;
use Maba\Bundle\OAuthCommerceProxyBundle\Storage\SessionStorageInterface;
use Maba\OAuthCommerceInternalClient\Entity\AccessTokenCode;
use Maba\OAuthCommerceInternalClient\InternalClient;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Zend\Uri\UriFactory;

class AccessTokenCodeManager
{
    /**
     * @var ClientManager
     */
    protected $clientManager;

    /**
     * @var string[]
     */
    protected $availableScopeRegexps = array();

    /**
     * @var SessionStorageInterface
     */
    protected $codeSessionStorage;

    /**
     * @var InternalClient
     */
    protected $apiClient;


    public function __construct(
        InternalClient $apiClient,
        ClientManager $clientManager,
        SessionStorageInterface $codeSessionStorage
    ) {
        $this->apiClient = $apiClient;
        $this->clientManager = $clientManager;
        $this->codeSessionStorage = $codeSessionStorage;
    }


    /**
     * @param string $regexp
     */
    public function addAvailableScopeRegexp($regexp)
    {
        $this->availableScopeRegexps[] = $regexp;
    }

    /**
     * @param ParameterBag $query
     *
     * @throws \Maba\Bundle\OAuthCommerceProxyBundle\Exception\CodeRequestException
     * @throws \Maba\Bundle\OAuthCommerceProxyBundle\Exception\CodeRequestRedirectException
     * @return string
     */
    public function handleRequest(ParameterBag $query)
    {
        $credentialsId = $query->get('client_id');
        if (!$credentialsId) {
            throw new CodeRequestException('invalid_request');
        }
        $client = $this->clientManager->findClientByCredentialsId($credentialsId);
        if ($client === null) {
            throw new CodeRequestException('unauthorized_client');
        }

        $redirectUri = $query->get('redirect_uri');
        if (strpos($redirectUri, $client->getRedirectUri()) !== 0) {
            throw new CodeRequestException('invalid_request', 'Provided redirect_uri parameter is invalid');
        }

        $state = $query->get('state');

        if ($query->get('response_type') !== 'code') {
            throw $this->createRedirectException('unsupported_response_type', $redirectUri, $state);
        }

        $scopes = array_unique(array_filter(explode(' ', $query->get('scope'))));
        foreach ($scopes as $scope) {
            foreach ($this->availableScopeRegexps as $regexp) {
                if (preg_match($regexp, $scope)) {
                    continue 2;
                }
            }
            throw $this->createRedirectException('invalid_scope', $redirectUri, $state);
        }

        return $this->codeSessionStorage->save(array(
            'credentialsId' => $credentialsId,
            'scopes' => $scopes,
            'redirectUri' => $redirectUri,
            'state' => $state,
        ));
    }

    public function getConfirmationInfoByKey($key)
    {
        $info = $this->codeSessionStorage->load($key);
        $client = $this->clientManager->findClientByCredentialsId($info['credentialsId']);
        return array(
            'client' => $client,
            'scopes' => $info['scopes'],
        );
    }

    public function getRejectResponse($key)
    {
        return $this->getErrorResponse($key, 'access_denied', 'Resource owner has denied the request');
    }

    public function getErrorResponse($key, $error, $description = null)
    {
        $info = $this->codeSessionStorage->load($key);

        $redirectUri = $this->makeUri($info['redirectUri'], array(
            'error' => $error,
            'error_description' => $description,
            'state' => $info['state'],
        ));

        return new RedirectResponse($redirectUri);
    }

    public function getAcceptResponse($key, $userId)
    {
        $info = $this->codeSessionStorage->load($key);
        $redirectUri = $info['redirectUri'];

        $codeValue = $this->apiClient->createCode(
            AccessTokenCode::create()
                ->setRedirectUri($redirectUri)
                ->setUserId($userId)
                ->setScopes($info['scopes'])
                ->setExpires(new \DateTime('+10 minutes'))
                ->setCredentialsId($info['credentialsId'])
        )->getResult();

        $redirectUri = $this->makeUri($redirectUri, array(
            'state' => $info['state'],
            'code' => $codeValue,
        ));
        return new RedirectResponse($redirectUri);
    }

    protected function createRedirectException($errorCode, $redirectUri, $state, $description = null)
    {
        $redirectUri = $this->makeUri($redirectUri, array(
            'error' => $errorCode,
            'error_description' => $description,
            'state' => $state,
        ));
        return new CodeRequestException($errorCode, $redirectUri, $description);
    }

    protected function makeUri($redirectUri, array $queryArray)
    {
        $uri = UriFactory::factory($redirectUri);
        $uri->setQuery(array_filter($queryArray) + $uri->getQueryAsArray());
        $uri->setFragment('');
        return $uri->toString();
    }
}
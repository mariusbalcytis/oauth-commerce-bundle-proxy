<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Manager;

use Doctrine\ORM\EntityRepository;
use Maba\OAuthCommerceClient\Entity\SignatureCredentials\AsymmetricCredentials;
use Maba\OAuthCommerceClient\Entity\SignatureCredentials\SymmetricCredentials;
use Maba\OAuthCommerceClient\Exception\ClientErrorException;
use Maba\OAuthCommerceInternalClient\InternalClient;
use Maba\OAuthCommerceInternalClient\Entity\ClientCredentials;
use Doctrine\ORM\EntityManager;
use Maba\Bundle\OAuthCommerceCommonBundle\Entity\Client;

class ClientManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var InternalClient
     */
    protected $apiClient;

    /**
     * @var EntityRepository
     */
    protected $clientRepository;

    /**
     * @param EntityManager               $entityManager
     * @param InternalClient $apiClient
     * @param EntityRepository            $clientRepository
     */
    public function __construct(
        EntityManager $entityManager,
        InternalClient $apiClient,
        EntityRepository $clientRepository
    ) {
        $this->entityManager = $entityManager;
        $this->apiClient = $apiClient;
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param string $title
     * @param string $redirectUri
     * @param string $algorithm
     * @param null   $publicKey
     *
     * @return Client
     */
    public function createClient($title, $redirectUri, $algorithm = 'hmac-sha-256', $publicKey = null)
    {
        $client = new Client();
        $client->setTitle($title);
        $client->setRedirectUri($redirectUri);
        $this->entityManager->beginTransaction();
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        $credentials = $this->createCredentialsForClient($client->getId(), $algorithm, $publicKey);
        $this->entityManager->commit();

        $client->setGeneratedCredentials($credentials);

        return $client;
    }

    /**
     * @param int    $clientId
     * @param string $algorithm
     * @param string $publicKey
     *
     * @return ClientCredentials
     */
    public function createCredentialsForClient($clientId, $algorithm = 'hmac-sha-256', $publicKey = null)
    {
        if ($publicKey === null) {
            $signatureCredentials = SymmetricCredentials::create()->setAlgorithm($algorithm);
        } else {
            $signatureCredentials = AsymmetricCredentials::create()->setAlgorithm($algorithm)->setPublicKey($publicKey);
        }
        $clientCredentials = ClientCredentials::create()
            ->setClientId($clientId)
            ->setSignatureCredentials($signatureCredentials)
        ;
        return $this->apiClient->createCredentials($clientCredentials)->getResult();
    }

    public function findCredentialsByClientId($clientId)
    {
        return $this->apiClient->getCredentialsByClientId($clientId)->getResult();
    }

    /**
     * @param $credentialsId
     *
     * @return ClientCredentials
     */
    public function findCredentialsById($credentialsId)
    {
        return $this->apiClient->getCredentials($credentialsId)->getResult();
    }

    public function removeCredentialsByClientId($clientId)
    {
        return $this->apiClient->removeCredentialsByClientId($clientId)->getResult();
    }

    public function removeCredentialsById($credentialsId)
    {
        return $this->apiClient->removeCredentials($credentialsId)->getResult();
    }

    /**
     * @param integer $credentialsId
     *
     * @return null|Client
     * @throws \Exception|\Maba\OAuthCommerceClient\Exception\ClientErrorException
     */
    public function findClientByCredentialsId($credentialsId)
    {
        try {
            return $this->clientRepository->find($this->findCredentialsById($credentialsId)->getClientId());
        } catch (ClientErrorException $exception) {
            if ($exception->getErrorCode() === 'not_found') {
                return null;
            } else {
                throw $exception;
            }
        }
    }
}
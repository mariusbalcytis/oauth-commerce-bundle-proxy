<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Manager;

use Maba\Bundle\OAuthCommerceCommonBundle\Entity\Application;
use Maba\OAuthCommerceInternalClient\Entity\ApplicationPassword;
use Maba\OAuthCommerceInternalClient\InternalClient;
use Doctrine\ORM\EntityManager;

class ApplicationManager
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
     * @param EntityManager               $entityManager
     * @param InternalClient $apiClient
     */
    public function __construct(EntityManager $entityManager, InternalClient $apiClient)
    {
        $this->entityManager = $entityManager;
        $this->apiClient = $apiClient;
    }

    /**
     * @param string $title
     *
     * @return ApplicationPassword
     */
    public function createApplication($title)
    {
        $app = new Application();
        $app->setTitle($title);
        $this->entityManager->beginTransaction();
        $this->entityManager->persist($app);
        $this->entityManager->flush();
        $credentials = $this->createCredentialsForApplication($app->getId());
        $this->entityManager->commit();

        return $credentials;
    }

    /**
     * @param int $applicationId
     *
     * @return ApplicationPassword
     */
    public function createCredentialsForApplication($applicationId)
    {
        return $this->apiClient->createApplicationPassword(
            ApplicationPassword::create()->setApplicationId($applicationId)
        )->getResult();
    }

    public function findCredentialsByApplicationId($applicationId)
    {
        return $this->apiClient->getCredentialsByApplicationId($applicationId)->getResult();
    }

    public function findCredentialsById($id)
    {
        return $this->apiClient->getApplicationPassword($id)->getResult();
    }

    public function removeCredentialsByApplicationId($applicationId)
    {
        return $this->apiClient->removeCredentialsByApplicationId($applicationId)->getResult();
    }

    public function removeCredentialsById($id)
    {
        return $this->apiClient->removeApplicationPassword($id)->getResult();
    }
}
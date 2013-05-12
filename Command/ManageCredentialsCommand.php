<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Command;

use Maba\Bundle\OAuthCommerceProxyBundle\Manager\ClientManager;
use Maba\OAuthCommerceProxyBundle\Entity\SignatureCredentials\AsymmetricCredentials;
use Maba\OAuthCommerceProxyBundle\Entity\SignatureCredentials\SymmetricCredentials;
use Maba\OAuthCommerceInternalClient\Entity\ClientCredentials;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManageCredentialsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oauth-commerce:manage-credentials')
            ->setDescription('Manage OAuth Commerce API client credentials')
            ->addArgument(
                'task',
                InputArgument::REQUIRED,
                'Choose task to apply: find/create/remove'
            )
            ->addOption(
                'clientId',
                null,
                InputOption::VALUE_OPTIONAL,
                'Client ID to search for credentials or remove them; use with get and remove'
            )
            ->addOption(
                'credentialsId',
                null,
                InputOption::VALUE_OPTIONAL,
                'Credentials ID to get or remove; use with get and remove'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');
        $task = $input->getArgument('task');
        $clientId = $input->getOption('clientId');
        $credentialsId = $input->getOption('credentialsId');

        if ($task === 'create') {
            if ($credentialsId) {
                $output->writeln('<error>clientId is optional, credentialsId not available for create task</error>');
            } else {
                if ($clientId) {
                    $credentials = $this->getManager()->createCredentialsForClient($clientId);

                } else {
                    $title = $dialog->ask($output, 'Please enter the title of the client: ');
                    $redirectUri = $dialog->ask($output, 'Please enter redirect uri for the client (domain): ');
                    $credentials = $this->getManager()->createClient($title, $redirectUri)->getGeneratedCredentials();
                }
                $this->printCredentials($credentials, $output);

            }
        } elseif ($clientId xor $credentialsId) {
            if ($task === 'find') {
                if ($clientId) {
                    $this->findCredentialsByClientId($clientId, $output);
                } else {
                    $this->findCredentialsById($credentialsId, $output);
                }
            } elseif ($task = 'remove') {
                if ($clientId) {
                    $this->findCredentialsByClientId($clientId, $output);
                    if ($dialog->askConfirmation($output, 'Do you confirm deletion? (y/N): ', false)) {
                        $this->removeCredentialsByClientId($clientId, $output);
                    }
                } else {
                    $this->findCredentialsById($credentialsId, $output);
                    if ($dialog->askConfirmation($output, 'Do you confirm deletion? (y/N): ', false)) {
                        $this->removeCredentialsById($credentialsId, $output);
                    }
                }
            } else {
                $output->writeln('<error>Unknown task</error>');
            }
        } else {
            $output->writeln('<error>Exactly one option must be specified for this task</error>');
        }
    }
    protected function findCredentialsByClientId($clientId, OutputInterface $output)
    {
        $credentialsList = $this->getManager()->findCredentialsByClientId($clientId);
        foreach ($credentialsList as $credentials) {
            $this->printCredentials($credentials, $output);
        }
    }
    protected function findCredentialsById($credentialsId, OutputInterface $output)
    {
        $credentials = $this->getManager()->findCredentialsById($credentialsId);
        $this->printCredentials($credentials, $output);
    }
    protected function removeCredentialsByClientId($clientId, OutputInterface $output)
    {
        $this->getManager()->removeCredentialsByClientId($clientId);
        $output->writeln('Credentials removed');
    }
    protected function removeCredentialsById($credentialsId, OutputInterface $output)
    {
        $this->getManager()->removeCredentialsById($credentialsId);
        $output->writeln('Credentials removed');
    }

    protected function printCredentials(ClientCredentials $credentials, OutputInterface $output)
    {
        $output->writeln('Client ID: ' . $credentials->getClientId());
        $output->writeln('Credentials ID: ' . $credentials->getId());
        $output->writeln('Permissions: ' . implode(', ', $credentials->getPermissions()));
        $signatureCredentials = $credentials->getSignatureCredentials();
        $output->writeln('Algorithm key: ' . $signatureCredentials->getAlgorithm());
        $output->writeln('Signing ID: ' . $signatureCredentials->getMacId());
        if ($signatureCredentials instanceof SymmetricCredentials) {
            $output->writeln('Signing key: ' . $signatureCredentials->getSharedKey());
        } elseif ($signatureCredentials instanceof AsymmetricCredentials) {
            $output->writeln('Public key: ' . $signatureCredentials->getPublicKey());
        }
    }

    /**
     * @return ClientManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('maba_oauth_commerce_proxy.client_manager');
    }
}
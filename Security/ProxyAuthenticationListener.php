<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class ProxyAuthenticationListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;
    protected $logger;
    protected $providerKey;
    protected $headerName;
    protected $type;


    public function __construct(
        SecurityContextInterface $securityContext,
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger
    ) {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
    }

    /**
     * @param string $providerKey
     */
    public function setProviderKey($providerKey)
    {
        $this->providerKey = $providerKey;
    }

    /**
     * @param string $headerName
     */
    public function setHeaderName($headerName)
    {
        $this->headerName = $headerName;
    }

    /**
     * This interface must be implemented by firewall listeners.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            $request->headers->has($this->headerName)
            && preg_match('/Auth="([^"]+)", Signature="([^"]+)"/', $request->headers->get($this->headerName), $matches)
        ) {
            $this->logger->debug('Found proxy auth header, parsing');

            $token = new ProxyHeaderToken();
            $token->setProviderKey($this->providerKey);
            $token->setAttribute('normalized', $matches[1]);
            $token->setAttribute('signature', $matches[2]);

            $auth = json_decode(base64_decode($matches[1]), true);

            $token->setUser($auth['type'] . ': ' . $auth['id']);
            $token->setAttribute('type', $auth['type']);
            $token->setAttribute('id', $auth['id']);
            $token->setAttribute('credentials_id', $auth['credentials_id']);
            $token->setAttribute('access_token', isset($auth['access_token']) ? $auth['access_token'] : null);

            $this->logger->debug('Parsed header', array($token));

            try {
                $authToken = $this->authenticationManager->authenticate($token);
                $this->logger->info('Authenticated, setting token to security context', array($authToken));
                $this->securityContext->setToken($authToken);
            } catch (AuthenticationException $failed) {
                $this->logger->debug('Failed to authenticate', array($failed));
                $response = new Response();
                $response->setStatusCode(403);
                $event->setResponse($response);
            }
        }
    }

}
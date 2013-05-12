<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Storage;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DefaultSessionStorage implements SessionStorageInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $prefix;


    /**
     * @param SessionInterface $session
     * @param string           $prefix
     */
    public function __construct(SessionInterface $session, $prefix)
    {
        $this->prefix = $prefix;
        $this->session = $session;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function save(array $data)
    {
        $key = '';
        for ($i = 0; $i < 8; $i++) {
            $key .= chr(mt_rand(97, 122));
        }
        $this->session->set($this->prefix . $key, $data);
        return $key;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function load($key)
    {
        return $this->session->get($this->prefix . $key);
    }

}
<?php


namespace Maba\Bundle\OAuthCommerceProxyBundle\Storage;


interface SessionStorageInterface
{
    /**
     * @param array $data
     *
     * @return string
     */
    public function save(array $data);

    /**
     * @param string $key
     *
     * @return array
     */
    public function load($key);
}
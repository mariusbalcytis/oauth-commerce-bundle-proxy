<?php

namespace Maba\Bundle\OAuthCommerceProxyBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class ProxySecurityFactory implements SecurityFactoryInterface
{

    public function create(ContainerBuilder $container, $providerKey, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.oauth_commerce_proxy.' . $providerKey;
        $listenerId = 'security.authentication.listener.oauth_commerce_proxy.' . $providerKey;

        $definition = $container
            ->setDefinition(
                $providerId,
                new DefinitionDecorator('maba_oauth_commerce_proxy.security.authentication.provider')
            )
            ->addMethodCall('setClientUserProvider', array(new Reference($userProvider)))
            ->addMethodCall('setProviderKey', array($providerKey))
            ->addMethodCall('setSecret', array($config['secret']))
            ->addMethodCall('setAlgorithm', array($config['algorithm']))
        ;
        if (!empty($config['application_user_provider'])) {
            $definition->addMethodCall(
                'setApplicationUserProvider',
                array(new Reference('security.user.provider.concrete.' . $config['application_user_provider']))
            );
        }

        $container
            ->setDefinition(
                $listenerId,
                new DefinitionDecorator('maba_oauth_commerce_proxy.security.authentication.listener')
            )
            ->addMethodCall('setProviderKey', array($providerKey))
            ->addMethodCall('setHeaderName', array($config['header']))
        ;

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'oauth_commerce_proxy';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        if (!$builder instanceof ArrayNodeDefinition) {
            throw new \InvalidArgumentException('Expected ArrayNodeDefinition');
        }
        $builder
            ->children()
            ->scalarNode('header')->defaultValue('x-oauth-commerce-proxy-authorization')->end()
            ->scalarNode('algorithm')->defaultValue('sha256')->end()
            ->scalarNode('application_user_provider')->end()
            ->scalarNode('secret')->isRequired()->end()
            ->end()
        ;
    }

}
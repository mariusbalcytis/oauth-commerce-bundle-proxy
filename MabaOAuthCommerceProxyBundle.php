<?php

namespace Maba\Bundle\OAuthCommerceProxyBundle;

use Maba\Bundle\OAuthCommerceProxyBundle\DependencyInjection\Security\Factory\ProxySecurityFactory;
use Maba\OAuthCommerceClient\DependencyInjection\BaseClientExtension;
use Maba\OAuthCommerceInternalClient\DependencyInjection\InternalClientExtension;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MabaOAuthCommerceProxyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = new BaseClientExtension();
        $container->registerExtension($extension);
        $extension->addCompilerPasses($container);
        $container->loadFromExtension($extension->getAlias());

        $extension = new InternalClientExtension();
        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias());

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ProxySecurityFactory());
    }
}

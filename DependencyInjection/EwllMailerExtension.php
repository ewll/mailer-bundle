<?php namespace Ewll\MailerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * {@inheritdoc}
 */
class EwllMailerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('ewll_mailer.host', $config['host']);
        $container->setParameter('ewll_mailer.port', $config['port']);
        $container->setParameter('ewll_mailer.secure', $config['secure']);
        $container->setParameter('ewll_mailer.user', $config['user']);
        $container->setParameter('ewll_mailer.pass', $config['pass']);
        $container->setParameter('ewll_mailer.smtp_auth', $config['smtp_auth']);
        $container->setParameter('ewll_mailer.sender_email', $config['sender_email']);
        $container->setParameter('ewll_mailer.sender_name', $config['sender_name']);
    }
}

<?php namespace Ewll\MailerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * {@inheritdoc}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ewll_mailer');

        $rootNode
            ->children()
            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('port')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('secure')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('pass')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('smtp_auth')->defaultFalse()->end()
            ->scalarNode('sender_email')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('sender_name')->isRequired()->cannotBeEmpty()->end()
        ;

        return $treeBuilder;
    }
}

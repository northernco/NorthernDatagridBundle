<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class APYDataGridExtension extends ConfigurableExtension
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('columns.xml');

        $ymlLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $ymlLoader->load('grid.yml');

        $container->setParameter('apy_data_grid.limits', $mergedConfig['limits']);
        $container->setParameter('apy_data_grid.theme', $mergedConfig['theme']);
        $container->setParameter('apy_data_grid.persistence', $mergedConfig['persistence']);
        $container->setParameter('apy_data_grid.no_data_message', $mergedConfig['no_data_message']);
        $container->setParameter('apy_data_grid.no_result_message', $mergedConfig['no_result_message']);
        $container->setParameter('apy_data_grid.actions_columns_size', $mergedConfig['actions_columns_size']);
        $container->setParameter('apy_data_grid.actions_columns_title', $mergedConfig['actions_columns_title']);
        $container->setParameter('apy_data_grid.pagerfanta', $mergedConfig['pagerfanta']);
        $container->setParameter('apy_data_grid.mass_actions_in_new_tab', $mergedConfig['mass_actions_in_new_tab']);

        $gridColumnClassDefinition = $container->getDefinition('grid.column.class');
        $gridColumnClassDefinition->setArgument('$defaultOperators', $mergedConfig['default_column_operators']);
    }
}

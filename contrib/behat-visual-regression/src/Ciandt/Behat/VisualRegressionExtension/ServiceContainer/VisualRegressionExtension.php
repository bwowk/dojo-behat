<?php
namespace Ciandt\Behat\VisualRegressionExtension\ServiceContainer;

use Ciandt\Behat\PlaceholdersExtension\Config\ConfigsRepository;
use Ciandt\Behat\PlaceholdersExtension\Tester\PerVariantScenarioTester;
use Ciandt\Behat\PlaceholdersExtension\Tester\PlaceholdersReplacer;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Behat\Behat\Tester\ServiceContainer\TesterExtension;
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Behat\Context\ServiceContainer\ContextExtension;

final class VisualRegressionExtension implements Extension
{

//    const SCENARIO_TESTER_ID = 'tester.scenario';

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'visual_regression';
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->addDefaultsIfNotSet()
            ->end();
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadRenderer($container);
        $this->loadContextInitializer($container);
        $this->loadVisualRegressionController($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadVisualRegressionController(ContainerBuilder $container)
    {
        $definition = new Definition('Ciandt\Behat\VisualRegressionExtension\Cli\VisualRegressionController', array(
            new Reference('visual_regression.context_initializer')
        ));
        $definition->addTag(CliExtension::CONTROLLER_TAG, array('priority' => 1));
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.visual_regression', $definition);
    }

    private function loadContextInitializer(ContainerBuilder $container)
    {
        $definition = new Definition(
            'Ciandt\Behat\VisualRegressionExtension\Initializer\MinkAwareInitializer',
            array(
                new Reference('visual_regression.report_renderer')
            )
        );
        $definition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 0));
        $container->setDefinition('visual_regression.context_initializer', $definition);
    }
        
    
    private function loadRenderer(ContainerBuilder $container)
    {
        $definition = new Definition('Ciandt\Behat\VisualRegressionExtension\Renderer\TwigRenderer');
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, array('priority' => 0));
        $container->setDefinition('visual_regression.report_renderer', $definition);
    }
}

<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Factory;

use Puli\Manager\Api\Config\Config;
use Puli\Manager\Api\Environment\ProjectEnvironment;
use Puli\Manager\Api\Event\GenerateFactoryEvent;
use Puli\Manager\Api\Event\PuliEvents;
use Puli\Manager\Api\Factory\FactoryManager;
use Puli\Manager\Api\Factory\Generator\GeneratorRegistry;
use Puli\Manager\Api\Php\Argument;
use Puli\Manager\Api\Php\Clazz;
use Puli\Manager\Api\Php\Import;
use Puli\Manager\Api\Php\Method;
use Puli\Manager\Api\Php\ReturnValue;
use Puli\Manager\Api\Server\ServerCollection;
use Puli\Manager\Assert\Assert;
use Puli\Manager\Php\ClassWriter;
use Webmozart\PathUtil\Path;

/**
 * The default {@link FactoryManager} implementation.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FactoryManagerImpl implements FactoryManager
{
    /**
     * The name of the resource repository variable.
     */
    const REPO_VAR_NAME = 'repo';

    /**
     * The name of the resource discovery variable.
     */
    const DISCOVERY_VAR_NAME = 'discovery';

    /**
     * @var ProjectEnvironment
     */
    private $environment;

    /**
     * @var string
     */
    private $factoryInFile;

    /**
     * @var string
     */
    private $factoryInClass;

    /**
     * @var string
     */
    private $factoryOutFile;

    /**
     * @var string
     */
    private $factoryOutClass;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var GeneratorRegistry
     */
    private $generatorRegistry;

    /**
     * @var ClassWriter
     */
    private $classWriter;

    /**
     * @var ServerCollection
     */
    private $servers;

    /**
     * Creates a new factory generator.
     *
     * @param ProjectEnvironment $environment       The project environment.
     * @param GeneratorRegistry  $generatorRegistry The registry providing the
     *                                              generators for the services
     *                                              returned by the factory.
     * @param ClassWriter        $classWriter       The writer that writes the
     *                                              class to a file.
     * @param ServerCollection   $servers           The configured servers.
     */
    public function __construct(ProjectEnvironment $environment, GeneratorRegistry $generatorRegistry, ClassWriter $classWriter, ServerCollection $servers = null)
    {
        $config = $environment->getConfig();

        $this->environment = $environment;
        $this->rootDir = $environment->getRootDirectory();
        $this->factoryInFile = Path::makeAbsolute($config->get(Config::FACTORY_IN_FILE), $this->rootDir);
        $this->factoryInClass = $config->get(Config::FACTORY_IN_CLASS);
        $this->factoryOutFile = Path::makeAbsolute($config->get(Config::FACTORY_OUT_FILE), $this->rootDir);
        $this->factoryOutClass = $config->get(Config::FACTORY_OUT_CLASS);
        $this->generatorRegistry = $generatorRegistry;
        $this->classWriter = $classWriter;
        $this->servers = $servers;
    }

    /**
     * Sets the servers included in the createUrlGenerator() method.
     *
     * @param ServerCollection $servers The configured servers.
     */
    public function setServers(ServerCollection $servers)
    {
        $this->servers = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function createFactory($path = null, $className = null)
    {
        Assert::nullOrStringNotEmpty($path, 'The path to the generated factory file must be a non-empty string or null. Got: %s');
        Assert::nullOrStringNotEmpty($className, 'The class name of the generated factory must be a non-empty string or null. Got: %s');

        $this->refreshFactoryClass($path, $className);

        $path = $path ? Path::makeAbsolute($path, $this->rootDir) : $this->factoryInFile;
        $className = $className ?: $this->factoryInClass;

        if (!class_exists($className, false)) {
            require_once $path;
        }

        return new $className;
    }

    /**
     * {@inheritdoc}
     */
    public function isFactoryClassAutoGenerated()
    {
        return $this->environment->getConfig()->get(Config::FACTORY_AUTO_GENERATE);
    }

    /**
     * {@inheritdoc}
     */
    public function generateFactoryClass($path = null, $className = null)
    {
        Assert::nullOrStringNotEmpty($path, 'The path to the generated factory file must be a non-empty string or null. Got: %s');
        Assert::nullOrStringNotEmpty($className, 'The class name of the generated factory must be a non-empty string or null. Got: %s');

        $path = $path ? Path::makeAbsolute($path, $this->rootDir) : $this->factoryOutFile;
        $className = $className ?: $this->factoryOutClass;
        $dispatcher = $this->environment->getEventDispatcher();

        $class = new Clazz($className);
        $class->setFilePath($path);
        $class->addImplementedInterface('PuliFactory');
        $class->addImport(new Import('Puli\Factory\PuliFactory'));
        $class->setDescription(
<<<EOF
Creates Puli's core services.

This class was auto-generated by Puli.

IMPORTANT: Before modifying the code below, set the "factory.auto-generate"
configuration key to false:

    $ puli config factory.auto-generate false

Otherwise any modifications will be overwritten!
EOF
        );

        $this->addCreateRepositoryMethod($class);
        $this->addCreateDiscoveryMethod($class);
        $this->addCreateUrlGeneratorMethod($class);

        if ($dispatcher->hasListeners(PuliEvents::GENERATE_FACTORY)) {
            $dispatcher->dispatch(PuliEvents::GENERATE_FACTORY, new GenerateFactoryEvent($class));
        }

        $this->classWriter->writeClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function autoGenerateFactoryClass($path = null, $className = null)
    {
        if (!$this->environment->getConfig()->get(Config::FACTORY_AUTO_GENERATE)) {
            return;
        }

        $this->generateFactoryClass($path, $className);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshFactoryClass($path = null, $className = null)
    {
        Assert::nullOrStringNotEmpty($path, 'The path to the generated factory file must be a non-empty string or null. Got: %s');
        Assert::nullOrStringNotEmpty($className, 'The class name of the generated factory must be a non-empty string or null. Got: %s');

        $path = $path ? Path::makeAbsolute($path, $this->rootDir) : $this->factoryOutFile;
        $className = $className ?: $this->factoryOutClass;

        if (!$this->environment->getConfig()->get(Config::FACTORY_AUTO_GENERATE)) {
            return;
        }

        if (!file_exists($path)) {
            $this->generateFactoryClass($path, $className);

            return;
        }

        $rootPackageFile = $this->environment->getRootPackageFile()->getPath();
        $configFile = $this->environment->getConfigFile()
            ? $this->environment->getConfigFile()->getPath()
            : '';

        // Regenerate file if the configuration has changed and
        // auto-generation is enabled
        clearstatcache(true, $rootPackageFile);
        $lastConfigChange = filemtime($rootPackageFile);

        if (file_exists($configFile)) {
            clearstatcache(true, $configFile);
            $lastConfigChange = max(filemtime($configFile), $lastConfigChange);
        }

        clearstatcache(true, $path);
        $lastFactoryUpdate = filemtime($path);

        if ($lastConfigChange > $lastFactoryUpdate) {
            $this->generateFactoryClass($path, $className);
        }
    }

    /**
     * Adds the createRepository() method.
     *
     * @param Clazz $class The factory class model.
     */
    private function addCreateRepositoryMethod(Clazz $class)
    {
        $method = new Method('createRepository');
        $method->setDescription('Creates the resource repository.');
        $method->setReturnValue(new ReturnValue(
            '$'.self::REPO_VAR_NAME,
            'ResourceRepository',
            'The created resource repository.'
        ));

        $class->addImport(new Import('Puli\Repository\Api\ResourceRepository'));
        $class->addMethod($method);

        // Add method body
        $config = $this->environment->getConfig();
        $type = $config->get(Config::REPOSITORY_TYPE);
        $options = $this->camelizeKeys($config->get(Config::REPOSITORY));
        $options['rootDir'] = $this->rootDir;

        $generator = $this->generatorRegistry->getServiceGenerator(GeneratorRegistry::REPOSITORY, $type);
        $generator->generateNewInstance(self::REPO_VAR_NAME, $method, $this->generatorRegistry, $options);
    }

    /**
     * Adds the createDiscovery() method.
     *
     * @param Clazz $class The factory class model.
     */
    private function addCreateDiscoveryMethod(Clazz $class)
    {
        $method = new Method('createDiscovery');
        $method->setDescription('Creates the resource discovery.');

        $arg = new Argument(self::REPO_VAR_NAME);
        $arg->setTypeHint('ResourceRepository');
        $arg->setType('ResourceRepository');
        $arg->setDescription('The resource repository to read from.');

        $method->addArgument($arg);

        $method->setReturnValue(new ReturnValue(
            '$'.self::DISCOVERY_VAR_NAME,
            'ResourceDiscovery',
            'The created resource discovery.'
        ));

        $class->addImport(new Import('Puli\Repository\Api\ResourceRepository'));
        $class->addImport(new Import('Puli\Discovery\Api\ResourceDiscovery'));
        $class->addMethod($method);

        // Add method body
        $config = $this->environment->getConfig();
        $type = $config->get(Config::DISCOVERY_TYPE);
        $options = $this->camelizeKeys($config->get(Config::DISCOVERY));
        $options['rootDir'] = $this->rootDir;

        $generator = $this->generatorRegistry->getServiceGenerator(GeneratorRegistry::DISCOVERY, $type);
        $generator->generateNewInstance(self::DISCOVERY_VAR_NAME, $method, $this->generatorRegistry, $options);
    }

    /**
     * Adds the createUrlGenerator() method.
     *
     * @param Clazz $class The factory class model.
     */
    public function addCreateUrlGeneratorMethod(Clazz $class)
    {
        $class->addImport(new Import('Puli\Discovery\Api\ResourceDiscovery'));
        $class->addImport(new Import('Puli\Manager\Api\Server\ServerCollection'));
        $class->addImport(new Import('Puli\UrlGenerator\Api\UrlGenerator'));
        $class->addImport(new Import('Puli\UrlGenerator\Api\UrlGeneratorFactory'));
        $class->addImport(new Import('Puli\UrlGenerator\DiscoveryUrlGenerator'));

        $class->addImplementedInterface('UrlGeneratorFactory');

        $method = new Method('createUrlGenerator');
        $method->setDescription('Creates the URL generator.');

        $arg = new Argument('discovery');
        $arg->setTypeHint('ResourceDiscovery');
        $arg->setType('ResourceDiscovery');
        $arg->setDescription('The resource discovery to read from.');
        $method->addArgument($arg);

        $method->setReturnValue(new ReturnValue('$generator', 'UrlGenerator', 'The created URL generator.'));

        $urlFormatsString = '';

        foreach ($this->servers as $server) {
            $urlFormatsString .= sprintf(
                "\n    %s => %s,",
                var_export($server->getName(), true),
                var_export($server->getUrlFormat(), true)
            );
        }

        if ($urlFormatsString) {
            $urlFormatsString .= "\n";
        }

        $method->addBody("\$generator = new DiscoveryUrlGenerator(\$discovery, array($urlFormatsString));");

        $class->addMethod($method);
    }

    /**
     * Recursively camelizes the keys of an array.
     *
     * @param array $array The array to process.
     *
     * @return array The input array with camelized keys.
     */
    private function camelizeKeys(array $array)
    {
        $camelized = array();

        foreach ($array as $key => $value) {
            $camelized[$this->camelize($key)] = is_array($value)
                ? $this->camelizeKeys($value)
                : $value;
        }

        return $camelized;
    }

    /**
     * Camelizes a string.
     *
     * @param string $string A string.
     *
     * @return string The camelized string.
     */
    private function camelize($string)
    {
        return preg_replace_callback('/\W+([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $string);
    }
}

<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Tests\Factory;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_MockObject_MockObject;
use Puli\RepositoryManager\Api\Config\Config;
use Puli\RepositoryManager\Api\Event\GenerateFactoryEvent;
use Puli\RepositoryManager\Api\Event\PuliEvents;
use Puli\RepositoryManager\Api\Php\Clazz;
use Puli\RepositoryManager\Api\Php\Method;
use Puli\RepositoryManager\Factory\FactoryManagerImpl;
use Puli\RepositoryManager\Factory\Generator\DefaultGeneratorRegistry;
use Puli\RepositoryManager\Php\ClassWriter;
use Puli\RepositoryManager\Tests\ManagerTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FactoryManagerImplTest extends ManagerTestCase
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var DefaultGeneratorRegistry
     */
    private $registry;

    /**
     * @var ClassWriter
     */
    private $realWriter;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ClassWriter
     */
    private $fakeWriter;

    /**
     * @var FactoryManagerImpl
     */
    private $manager;

    protected function setUp()
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/puli-repo-manager/FactoryManagerImplTest'.rand(10000, 99999), 0777, true)) {}

        @mkdir($this->tempDir.'/home');
        @mkdir($this->tempDir.'/root');

        $this->initEnvironment($this->tempDir.'/home', $this->tempDir.'/root');

        $this->environment->getConfig()->set(Config::FACTORY_FILE, 'MyFactory.php');
        $this->environment->getConfig()->set(Config::FACTORY_CLASS, 'Puli\MyFactory');

        $this->registry = new DefaultGeneratorRegistry();
        $this->realWriter = new ClassWriter();
        $this->fakeWriter = $this->getMockBuilder('Puli\RepositoryManager\Php\ClassWriter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new FactoryManagerImpl($this->environment, $this->registry, $this->realWriter);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    public function testGenerateFactoryClass()
    {
        $this->manager->generateFactoryClass();

        $this->assertFileExists($this->rootDir.'/MyFactory.php');
        $contents = file_get_contents($this->rootDir.'/MyFactory.php');
        $this->assertStringStartsWith('<?php', $contents);
        $this->assertContains('class MyFactory implements PuliFactory', $contents);
    }

    public function testGenerateFactoryClassAtCustomRelativePath()
    {
        $this->manager->generateFactoryClass('MyCustomFile.php');

        $this->assertFileExists($this->rootDir.'/MyCustomFile.php');
        $contents = file_get_contents($this->rootDir.'/MyCustomFile.php');
        $this->assertStringStartsWith('<?php', $contents);
        $this->assertContains('class MyFactory implements PuliFactory', $contents);
    }

    public function testGenerateFactoryClassAtCustomAbsolutePath()
    {
        $this->manager->generateFactoryClass($this->rootDir.'/path/MyCustomFile.php');

        $this->assertFileExists($this->rootDir.'/path/MyCustomFile.php');
        $contents = file_get_contents($this->rootDir.'/path/MyCustomFile.php');
        $this->assertStringStartsWith('<?php', $contents);
        $this->assertContains('class MyFactory implements PuliFactory', $contents);
    }

    public function testGenerateFactoryClassWithCustomClassName()
    {
        $this->manager->generateFactoryClass(null, 'MyCustomClass');

        $this->assertFileExists($this->rootDir.'/MyFactory.php');
        $contents = file_get_contents($this->rootDir.'/MyFactory.php');
        $this->assertStringStartsWith('<?php', $contents);
        $this->assertContains('class MyCustomClass implements PuliFactory', $contents);
    }

    public function testGenerateFactoryClassDispatchesEvent()
    {
        $this->dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(PuliEvents::GENERATE_FACTORY)
            ->willReturn(true);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PuliEvents::GENERATE_FACTORY)
            ->willReturnCallback(function ($eventName, GenerateFactoryEvent $event) {
                $class = $event->getFactoryClass();

                PHPUnit_Framework_Assert::assertTrue($class->hasMethod('createRepository'));
                PHPUnit_Framework_Assert::assertTrue($class->hasMethod('createDiscovery'));

                $class->addMethod(new Method('createCustom'));
            });

        $this->manager->generateFactoryClass();

        $this->assertFileExists($this->rootDir.'/MyFactory.php');
        $contents = file_get_contents($this->rootDir.'/MyFactory.php');
        $this->assertContains('public function createCustom()', $contents);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateFactoryClassFailsIfPathEmpty()
    {
        $this->manager->generateFactoryClass('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateFactoryClassFailsIfPathNoString()
    {
        $this->manager->generateFactoryClass(1234);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateFactoryClassFailsIfClassNameEmpty()
    {
        $this->manager->generateFactoryClass(null, '');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateFactoryClassFailsIfClassNameNoString()
    {
        $this->manager->generateFactoryClass(null, 1234);
    }

    public function testRefreshFactoryClassGeneratesClassIfFileNotFound()
    {
        $rootDir = $this->rootDir;
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        $this->fakeWriter->expects($this->once())
            ->method('writeClass')
            ->willReturnCallback(function (Clazz $class) use ($rootDir) {
                PHPUnit_Framework_Assert::assertSame('Puli\MyFactory', $class->getClassName());
                PHPUnit_Framework_Assert::assertSame('MyFactory.php', $class->getFileName());
                PHPUnit_Framework_Assert::assertSame($rootDir, $class->getDirectory());
            });

        $manager->refreshFactoryClass();
    }

    public function testRefreshFactoryClassGeneratesClassIfFileNotFoundAtCustomRelativePath()
    {
        $rootDir = $this->rootDir;
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        $this->fakeWriter->expects($this->once())
            ->method('writeClass')
            ->willReturnCallback(function (Clazz $class) use ($rootDir) {
                PHPUnit_Framework_Assert::assertSame('Puli\MyFactory', $class->getClassName());
                PHPUnit_Framework_Assert::assertSame('MyCustomFile.php', $class->getFileName());
                PHPUnit_Framework_Assert::assertSame($rootDir, $class->getDirectory());
            });

        $manager->refreshFactoryClass('MyCustomFile.php');
    }

    public function testRefreshFactoryClassGeneratesClassIfFileNotFoundAtCustomAbsolutePath()
    {
        $rootDir = $this->rootDir;
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        $this->fakeWriter->expects($this->once())
            ->method('writeClass')
            ->willReturnCallback(function (Clazz $class) use ($rootDir) {
                PHPUnit_Framework_Assert::assertSame('Puli\MyFactory', $class->getClassName());
                PHPUnit_Framework_Assert::assertSame('MyCustomFile.php', $class->getFileName());
                PHPUnit_Framework_Assert::assertSame($rootDir.'/path', $class->getDirectory());
            });

        $manager->refreshFactoryClass($this->rootDir.'/path/MyCustomFile.php');
    }

    public function testRefreshFactoryClassGeneratesClassIfFileNotFoundWithCustomClass()
    {
        $rootDir = $this->rootDir;
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        $this->fakeWriter->expects($this->once())
            ->method('writeClass')
            ->willReturnCallback(function (Clazz $class) use ($rootDir) {
                PHPUnit_Framework_Assert::assertSame('MyCustomClass', $class->getClassName());
                PHPUnit_Framework_Assert::assertSame('MyFactory.php', $class->getFileName());
                PHPUnit_Framework_Assert::assertSame($rootDir, $class->getDirectory());
            });

        $manager->refreshFactoryClass(null, 'MyCustomClass');
    }

    public function testRefreshFactoryClassDoesNotGenerateIfClassExists()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        $this->fakeWriter->expects($this->never())
            ->method('writeClass');

        $manager->refreshFactoryClass(null, __CLASS__);
    }

    public function testRefreshFactoryClassGeneratesIfOlderThanRootPackageFile()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        touch($this->rootDir.'/MyFactory.php');
        sleep(1);
        touch($this->rootPackageFile->getPath());

        $this->fakeWriter->expects($this->once())
            ->method('writeClass');

        $manager->refreshFactoryClass();
    }

    public function testRefreshFactoryClassGeneratesWithCustomParameters()
    {
        $rootDir = $this->rootDir;
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        touch($this->rootDir.'/MyCustomFile.php');
        sleep(1);
        touch($this->rootPackageFile->getPath());

        $this->fakeWriter->expects($this->once())
            ->method('writeClass')
            ->willReturnCallback(function (Clazz $class) use ($rootDir) {
                PHPUnit_Framework_Assert::assertSame('MyCustomClass', $class->getClassName());
                PHPUnit_Framework_Assert::assertSame('MyCustomFile.php', $class->getFileName());
                PHPUnit_Framework_Assert::assertSame($rootDir, $class->getDirectory());
            });

        $manager->refreshFactoryClass('MyCustomFile.php', 'MyCustomClass');
    }

    public function testRefreshFactoryClassDoesNotGenerateIfNewerThanRootPackageFile()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        touch($this->rootPackageFile->getPath());
        sleep(1);
        touch($this->rootDir.'/MyFactory.php');

        $this->fakeWriter->expects($this->never())
            ->method('writeClass');

        $manager->refreshFactoryClass();
    }

    public function testRefreshFactoryClassGeneratesIfOlderThanConfigFile()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        touch($this->rootPackageFile->getPath());
        touch($this->rootDir.'/MyFactory.php');
        sleep(1);
        touch($this->configFile->getPath());

        $this->fakeWriter->expects($this->once())
            ->method('writeClass');

        $manager->refreshFactoryClass();
    }

    public function testRefreshFactoryClassDoesNotGenerateIfNewerThanConfigFile()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        touch($this->rootPackageFile->getPath());
        touch($this->configFile->getPath());
        sleep(1);
        touch($this->rootDir.'/MyFactory.php');

        $this->fakeWriter->expects($this->never())
            ->method('writeClass');

        $manager->refreshFactoryClass();
    }

    public function testRefreshFactoryClassDoesNotGenerateIfAutoGenerateDisabled()
    {
        $manager = new FactoryManagerImpl($this->environment, $this->registry, $this->fakeWriter);

        // Older than config file -> would normally be generated
        touch($this->rootDir.'/MyFactory.php');
        sleep(1);
        touch($this->rootPackageFile->getPath());

        $this->fakeWriter->expects($this->never())
            ->method('writeClass');

        $this->environment->getConfig()->set(Config::FACTORY_AUTO_GENERATE, false);

        $manager->refreshFactoryClass();
    }

    public function testCreateFactory()
    {
        $this->assertFalse(class_exists('Puli\Repository\Tests\TestGeneratedFactory1', false));

        $this->environment->getConfig()->set(Config::FACTORY_CLASS, 'Puli\Repository\Tests\TestGeneratedFactory1');

        $factory = $this->manager->createFactory();

        $this->isInstanceOf('Puli\Repository\Tests\TestGeneratedFactory1', $factory);
        $this->isInstanceOf('Puli\Factory\PuliFactory', $factory);
    }

    public function testCreateFactoryWithCustomParameters()
    {
        $this->assertFalse(class_exists('Puli\Repository\Tests\TestGeneratedFactory2', false));

        $factory = $this->manager->createFactory('MyFactory.php', 'Puli\Repository\Tests\TestGeneratedFactory2');

        $this->isInstanceOf('Puli\Repository\Tests\TestGeneratedFactory2', $factory);
        $this->isInstanceOf('Puli\Factory\PuliFactory', $factory);
    }
}

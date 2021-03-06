<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Api\Repository;

use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageCollection;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Manager\Api\Repository\PathConflict;
use Puli\Manager\Api\Repository\PathMapping;
use Puli\Manager\Api\Repository\PathMappingState;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PathMappingTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $packageDir1;

    /**
     * @var string
     */
    private $packageDir2;

    /**
     * @var string
     */
    private $packageDir3;

    /**
     * @var Package
     */
    private $package1;

    /**
     * @var Package
     */
    private $package2;

    /**
     * @var Package
     */
    private $package3;

    /**
     * @var PackageCollection
     */
    private $packages;

    protected function setUp()
    {
        $this->packageDir1 = __DIR__.'/Fixtures/package1';
        $this->packageDir2 = __DIR__.'/Fixtures/package2';
        $this->packageDir3 = __DIR__.'/Fixtures/package3';
        $this->package1 = new Package(new PackageFile('vendor/package1'), $this->packageDir1);
        $this->package2 = new Package(new PackageFile('vendor/package2'), $this->packageDir2);
        $this->package3 = new Package(new PackageFile('vendor/package3'), $this->packageDir3);
        $this->packages = new PackageCollection(array(
            $this->package1,
            $this->package2,
            $this->package3,
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathNotString()
    {
        new PathMapping(12345, 'resources');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathEmpty()
    {
        new PathMapping('', 'resources');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfFilesystemPathsNotStringOrArray()
    {
        new PathMapping('/path', 12345);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfFilesystemPathsEmptyString()
    {
        new PathMapping('/path', '');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfFilesystemPathsNotStringArray()
    {
        new PathMapping('/path', array(12345));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfFilesystemPathsContainEmptyString()
    {
        new PathMapping('/path', array(''));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNoFilesystemPaths()
    {
        new PathMapping('/path', array());
    }

    public function testLoad()
    {
        $mapping = new PathMapping('/path', 'resources');

        $this->assertSame(array('resources'), $mapping->getPathReferences());
        $this->assertFalse($mapping->isLoaded());

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('resources'), $mapping->getPathReferences());
        $this->assertSame(array($this->packageDir1.'/resources'), $mapping->getFilesystemPaths());
        $this->assertSame(array(
            $this->packageDir1.'/resources' => '/path',
            $this->packageDir1.'/resources/config' => '/path/config',
            $this->packageDir1.'/resources/config/config.yml' => '/path/config/config.yml',
            $this->packageDir1.'/resources/css' => '/path/css',
            $this->packageDir1.'/resources/css/style.css' => '/path/css/style.css',
        ), $mapping->listPathMappings());
        $this->assertSame(array(
            '/path',
            '/path/config',
            '/path/config/config.yml',
            '/path/css',
            '/path/css/style.css',
        ), $mapping->listRepositoryPaths());
        $this->assertSame(array(), $mapping->getLoadErrors());
        $this->assertSame($this->package1, $mapping->getContainingPackage());
        $this->assertTrue($mapping->isLoaded());
    }

    public function testLoadMultiplePathReferences()
    {
        $mapping = new PathMapping('/path', array('resources', 'assets'));

        $this->assertSame(array('resources', 'assets'), $mapping->getPathReferences());
        $this->assertFalse($mapping->isLoaded());

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('resources', 'assets'), $mapping->getPathReferences());
        $this->assertSame(array(
            $this->packageDir1.'/resources',
            $this->packageDir1.'/assets',
        ), $mapping->getFilesystemPaths());
        $this->assertSame(array(
            $this->packageDir1.'/resources' => '/path',
            $this->packageDir1.'/resources/config' => '/path/config',
            $this->packageDir1.'/resources/config/config.yml' => '/path/config/config.yml',
            $this->packageDir1.'/resources/css' => '/path/css',
            $this->packageDir1.'/resources/css/style.css' => '/path/css/style.css',
            $this->packageDir1.'/assets' => '/path',
            $this->packageDir1.'/assets/css' => '/path/css',
            $this->packageDir1.'/assets/css/style.css' => '/path/css/style.css',
        ), $mapping->listPathMappings());
        $this->assertSame(array(
            '/path',
            '/path/config',
            '/path/config/config.yml',
            '/path/css',
            '/path/css/style.css',
        ), $mapping->listRepositoryPaths());
        $this->assertSame(array(), $mapping->getLoadErrors());
        $this->assertSame($this->package1, $mapping->getContainingPackage());
        $this->assertTrue($mapping->isLoaded());
    }

    public function testLoadMultiplePathReferences2()
    {
        $mapping = new PathMapping('/path', array('assets', 'resources'));

        $this->assertSame(array('assets', 'resources'), $mapping->getPathReferences());
        $this->assertFalse($mapping->isLoaded());

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('assets', 'resources'), $mapping->getPathReferences());
        $this->assertSame(array(
            $this->packageDir1.'/assets',
            $this->packageDir1.'/resources',
        ), $mapping->getFilesystemPaths());
        $this->assertSame(array(
            $this->packageDir1.'/assets' => '/path',
            $this->packageDir1.'/assets/css' => '/path/css',
            $this->packageDir1.'/assets/css/style.css' => '/path/css/style.css',
            $this->packageDir1.'/resources' => '/path',
            $this->packageDir1.'/resources/config' => '/path/config',
            $this->packageDir1.'/resources/config/config.yml' => '/path/config/config.yml',
            $this->packageDir1.'/resources/css' => '/path/css',
            $this->packageDir1.'/resources/css/style.css' => '/path/css/style.css',
        ), $mapping->listPathMappings());
        $this->assertSame(array(
            '/path',
            '/path/config',
            '/path/config/config.yml',
            '/path/css',
            '/path/css/style.css',
        ), $mapping->listRepositoryPaths());
        $this->assertSame(array(), $mapping->getLoadErrors());
        $this->assertSame($this->package1, $mapping->getContainingPackage());
        $this->assertTrue($mapping->isLoaded());
    }

    public function testLoadReferencesToOtherPackage()
    {
        $mapping = new PathMapping('/path', '@vendor/package2:resources');

        $this->assertSame(array('@vendor/package2:resources'), $mapping->getPathReferences());
        $this->assertFalse($mapping->isLoaded());

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('@vendor/package2:resources'), $mapping->getPathReferences());
        $this->assertSame(array($this->packageDir2.'/resources'), $mapping->getFilesystemPaths());
        $this->assertSame(array(
            $this->packageDir2.'/resources' => '/path',
            $this->packageDir2.'/resources/config' => '/path/config',
            $this->packageDir2.'/resources/config/config.yml' => '/path/config/config.yml',
            $this->packageDir2.'/resources/css' => '/path/css',
            $this->packageDir2.'/resources/css/style.css' => '/path/css/style.css',
        ), $mapping->listPathMappings());
        $this->assertSame(array(
            '/path',
            '/path/config',
            '/path/config/config.yml',
            '/path/css',
            '/path/css/style.css',
        ), $mapping->listRepositoryPaths());
        $this->assertSame(array(), $mapping->getLoadErrors());
        $this->assertSame($this->package1, $mapping->getContainingPackage());
        $this->assertTrue($mapping->isLoaded());
    }

    /**
     * @expectedException \Puli\Manager\Api\AlreadyLoadedException
     */
    public function testLoadFailsIfCalledTwice()
    {
        $mapping = new PathMapping('/path', 'resources');

        $mapping->load($this->package1, $this->packages);
        $mapping->load($this->package1, $this->packages);
    }

    public function testLoadStoresErrorIfPathNotFound()
    {
        $mapping = new PathMapping('/path', array('foo', 'assets'));

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('foo', 'assets'), $mapping->getPathReferences());
        $this->assertSame(array($this->packageDir1.'/assets'), $mapping->getFilesystemPaths());

        // there's at least one found path, so the mapping is still enabled
        $this->assertTrue($mapping->isEnabled());

        $loadErrors = $mapping->getLoadErrors();
        $this->assertCount(1, $loadErrors);
        $this->assertInstanceOf('Puli\Manager\Api\FileNotFoundException', $loadErrors[0]);
    }

    public function testLoadStoresErrorsIfNoPathFound()
    {
        $mapping = new PathMapping('/path', array('foo', 'bar'));

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('foo', 'bar'), $mapping->getPathReferences());
        $this->assertSame(array(), $mapping->getFilesystemPaths());

        // no found path, not enabled
        $this->assertFalse($mapping->isEnabled());
        $this->assertTrue($mapping->isNotFound());

        $loadErrors = $mapping->getLoadErrors();
        $this->assertCount(2, $loadErrors);
        $this->assertInstanceOf('Puli\Manager\Api\FileNotFoundException', $loadErrors[0]);
        $this->assertInstanceOf('Puli\Manager\Api\FileNotFoundException', $loadErrors[1]);
    }

    public function testLoadStoresErrorIfPackageNotFound()
    {
        $mapping = new PathMapping('/path', array('@foo:resources', 'assets'));

        $mapping->load($this->package1, $this->packages);

        $this->assertSame(array('@foo:resources', 'assets'), $mapping->getPathReferences());
        $this->assertSame(array($this->packageDir1.'/assets'), $mapping->getFilesystemPaths());

        // there's at least one found path, so the mapping is still enabled
        $this->assertTrue($mapping->isEnabled());

        $loadErrors = $mapping->getLoadErrors();
        $this->assertCount(1, $loadErrors);
        $this->assertInstanceOf('Puli\Manager\Api\Package\NoSuchPackageException', $loadErrors[0]);
    }

    public function testUnload()
    {
        $mapping = new PathMapping('/path', 'resources');

        $mapping->load($this->package1, $this->packages);
        $mapping->unload();

        $this->assertSame(array('resources'), $mapping->getPathReferences());
        $this->assertFalse($mapping->isLoaded());
    }

    public function testUnloadReleasesConflict()
    {
        $mapping = new PathMapping('/path', 'resources');

        $mapping->load($this->package1, $this->packages);
        $mapping->addConflict($conflict = new PathConflict('/path/conflict'));

        $this->assertCount(1, $mapping->getConflicts());
        $this->assertCount(1, $conflict->getMappings());

        $mapping->unload();

        $this->assertCount(0, $conflict->getMappings());
    }

    /**
     * @expectedException \Puli\Manager\Api\NotLoadedException
     */
    public function testUnloadFailsIfNotLoaded()
    {
        $mapping = new PathMapping('/path', 'resources');

        $mapping->unload();
    }

    public function testAddConflictWithAmePath()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path');

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());

        $mapping->addConflict($conflict);

        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertTrue($mapping->isConflicting());
    }

    public function testAddConflictWithNestedPath()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $mapping->addConflict($conflict);

        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertTrue($mapping->isConflicting());
    }

    public function testAddMultipleConflicts()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict1 = new PathConflict('/path/conflict1');
        $conflict2 = new PathConflict('/path/conflict2');

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict1->getMappings());

        $mapping->addConflict($conflict1);
        $mapping->addConflict($conflict2);

        $this->assertCount(2, $mapping->getConflicts());
        $this->assertContains($conflict1, $mapping->getConflicts());
        $this->assertContains($conflict2, $mapping->getConflicts());
        $this->assertCount(1, $conflict1->getMappings());
        $this->assertContains($mapping, $conflict1->getMappings());
        $this->assertCount(1, $conflict2->getMappings());
        $this->assertContains($mapping, $conflict2->getMappings());
        $this->assertTrue($mapping->isConflicting());
    }

    public function testAddConflictIgnoresDuplicates()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());

        $mapping->addConflict($conflict);
        $mapping->addConflict($conflict);

        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertTrue($mapping->isConflicting());
    }

    public function testAddConflictRemovesPreviousConflictWithSameRepositoryPath()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $previousConflict = new PathConflict('/path/conflict');
        $newConflict = new PathConflict('/path/conflict');

        $mapping->addConflict($previousConflict);
        $mapping->addConflict($newConflict);

        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($newConflict, $mapping->getConflicts());
        $this->assertCount(0, $previousConflict->getMappings());
        $this->assertCount(1, $newConflict->getMappings());
        $this->assertContains($mapping, $newConflict->getMappings());
        $this->assertTrue($mapping->isConflicting());
    }

    /**
     * @expectedException \Puli\Manager\Api\NotLoadedException
     */
    public function testAddConflictFailsIfNotLoaded()
    {
        $mapping = new PathMapping('/path', 'resources');
        $conflict = new PathConflict('/path/conflict');

        $mapping->addConflict($conflict);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddConflictFailsIfConflictWithDifferentRepositoryBasePath()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/other/path/conflict');

        $mapping->addConflict($conflict);
    }

    public function testRemoveConflict()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $mapping->addConflict($conflict);
        $mapping->removeConflict($conflict);

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());
        $this->assertFalse($mapping->isConflicting());
    }

    public function testRemoveConflictIgnoresUnknownConflicts()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $mapping->removeConflict($conflict);

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());
        $this->assertFalse($mapping->isConflicting());
    }

    /**
     * @expectedException \Puli\Manager\Api\NotLoadedException
     */
    public function testRemoveConflictFailsIfNotLoaded()
    {
        $mapping = new PathMapping('/path', 'resources');
        $conflict = new PathConflict('/path/conflict');

        $mapping->removeConflict($conflict);
    }

    public function testGetConflictingPackages()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);

        $this->assertInstanceOf('Puli\Manager\Api\Package\PackageCollection', $mapping1->getConflictingPackages());
        $this->assertCount(0, $mapping1->getConflictingPackages());

        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);

        $conflict = new PathConflict('/path/conflict');

        $mapping1->addConflict($conflict);
        $mapping2->addConflict($conflict);

        $this->assertCount(1, $mapping1->getConflictingPackages());
        $this->assertTrue($mapping1->getConflictingPackages()->contains('vendor/package2'));
        $this->assertCount(1, $mapping2->getConflictingPackages());
        $this->assertTrue($mapping2->getConflictingPackages()->contains('vendor/package1'));

        $mapping3 = new PathMapping('/path', 'resources');
        $mapping3->load($this->package3, $this->packages);
        $mapping3->addConflict($conflict);

        $this->assertCount(2, $mapping1->getConflictingPackages());
        $this->assertTrue($mapping1->getConflictingPackages()->contains('vendor/package2'));
        $this->assertTrue($mapping1->getConflictingPackages()->contains('vendor/package3'));
        $this->assertCount(2, $mapping2->getConflictingPackages());
        $this->assertTrue($mapping2->getConflictingPackages()->contains('vendor/package1'));
        $this->assertTrue($mapping2->getConflictingPackages()->contains('vendor/package3'));
        $this->assertCount(2, $mapping3->getConflictingPackages());
        $this->assertTrue($mapping3->getConflictingPackages()->contains('vendor/package1'));
        $this->assertTrue($mapping3->getConflictingPackages()->contains('vendor/package2'));
    }

    public function testGetConflictingMappings()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);

        $this->assertCount(0, $mapping1->getConflictingMappings());

        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);

        $conflict = new PathConflict('/path/conflict');

        $mapping1->addConflict($conflict);
        $mapping2->addConflict($conflict);

        $this->assertCount(1, $mapping1->getConflictingMappings());
        $this->assertContains($mapping2, $mapping1->getConflictingMappings());
        $this->assertCount(1, $mapping2->getConflictingMappings());
        $this->assertContains($mapping1, $mapping2->getConflictingMappings());

        $mapping3 = new PathMapping('/path', 'resources');
        $mapping3->load($this->package3, $this->packages);
        $mapping3->addConflict($conflict);

        $this->assertCount(2, $mapping1->getConflictingMappings());
        $this->assertContains($mapping2, $mapping1->getConflictingMappings());
        $this->assertContains($mapping3, $mapping1->getConflictingMappings());
        $this->assertCount(2, $mapping2->getConflictingMappings());
        $this->assertContains($mapping1, $mapping2->getConflictingMappings());
        $this->assertContains($mapping3, $mapping2->getConflictingMappings());
        $this->assertCount(2, $mapping3->getConflictingMappings());
        $this->assertContains($mapping1, $mapping3->getConflictingMappings());
        $this->assertContains($mapping2, $mapping3->getConflictingMappings());
    }

    public function testMatch()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);

        $this->assertFalse($mapping->match(Expr::same('foobar', PathMapping::CONTAINING_PACKAGE)));
        $this->assertTrue($mapping->match(Expr::same($this->package1->getName(), PathMapping::CONTAINING_PACKAGE)));

        $this->assertFalse($mapping->match(Expr::same(PathMappingState::CONFLICT, PathMapping::STATE)));
        $this->assertTrue($mapping->match(Expr::same(PathMappingState::ENABLED, PathMapping::STATE)));

        $this->assertFalse($mapping->match(Expr::startsWith('/foo', PathMapping::REPOSITORY_PATH)));
        $this->assertTrue($mapping->match(Expr::startsWith('/pa', PathMapping::REPOSITORY_PATH)));
    }
}

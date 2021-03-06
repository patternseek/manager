<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Repository;

use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageCollection;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Manager\Api\Repository\PathConflict;
use Puli\Manager\Api\Repository\PathMapping;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PathConflictTest extends PHPUnit_Framework_TestCase
{
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
        $this->package1 = new Package(new PackageFile('vendor/package1'), __DIR__.'/Fixtures/package1');
        $this->package2 = new Package(new PackageFile('vendor/package2'), __DIR__.'/Fixtures/package2');
        $this->package3 = new Package(new PackageFile('vendor/package3'), __DIR__.'/Fixtures/package3');
        $this->packages = new PackageCollection(array($this->package1, $this->package2, $this->package3));
    }

    public function testAddMapping()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());

        $conflict->addMapping($mapping);

        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertFalse($conflict->isResolved());
        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
    }

    public function testAddMultipleMappings()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);
        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping1);
        $conflict->addMapping($mapping2);

        $this->assertCount(2, $conflict->getMappings());
        $this->assertContains($mapping1, $conflict->getMappings());
        $this->assertContains($mapping2, $conflict->getMappings());
        $this->assertFalse($conflict->isResolved());
        $this->assertCount(1, $mapping1->getConflicts());
        $this->assertContains($conflict, $mapping1->getConflicts());
        $this->assertCount(1, $mapping2->getConflicts());
        $this->assertContains($conflict, $mapping2->getConflicts());
    }

    public function testAddMappingIgnoresDuplicates()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());

        $conflict->addMapping($mapping);
        $conflict->addMapping($mapping);

        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertFalse($conflict->isResolved());
        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
    }

    public function testAddMappingRemovesPreviousMappingFromSamePackage()
    {
        $previousMapping = new PathMapping('/path', 'resources');
        $previousMapping->load($this->package1, $this->packages);
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($previousMapping);
        $conflict->addMapping($mapping);

        $this->assertCount(1, $conflict->getMappings());
        $this->assertContains($mapping, $conflict->getMappings());
        $this->assertFalse($conflict->isResolved());
        $this->assertCount(1, $mapping->getConflicts());
        $this->assertContains($conflict, $mapping->getConflicts());
        $this->assertCount(0, $previousMapping->getConflicts());
    }

    /**
     * @expectedException \Puli\Manager\Api\NotLoadedException
     */
    public function testAddMappingFailsIfPackageNotLoaded()
    {
        $mapping = new PathMapping('/path', 'resources');
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping);
    }

    public function testRemoveMapping()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping);
        $conflict->removeMapping($mapping);

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());
        $this->assertTrue($conflict->isResolved());
    }

    public function testRemoveMappingIgnoresUnknownMappings()
    {
        $mapping = new PathMapping('/path', 'resources');
        $mapping->load($this->package1, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->removeMapping($mapping);

        $this->assertCount(0, $mapping->getConflicts());
        $this->assertCount(0, $conflict->getMappings());
        $this->assertTrue($conflict->isResolved());
    }

    public function testRemoveMappingResolvesConflictIfOnlyOneMappingLeft()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);
        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping1);
        $conflict->addMapping($mapping2);
        $conflict->removeMapping($mapping1);

        $this->assertCount(0, $conflict->getMappings());
        $this->assertCount(0, $mapping1->getConflicts());
        $this->assertCount(0, $mapping2->getConflicts());
        $this->assertTrue($conflict->isResolved());
    }

    public function testRemoveMappingDoesNotResolveConflictIfMoreThanOneMappingLeft()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);
        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);
        $mapping3 = new PathMapping('/path', 'resources');
        $mapping3->load($this->package3, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping1);
        $conflict->addMapping($mapping2);
        $conflict->addMapping($mapping3);
        $conflict->removeMapping($mapping1);

        $this->assertCount(2, $conflict->getMappings());
        $this->assertCount(0, $mapping1->getConflicts());
        $this->assertCount(1, $mapping2->getConflicts());
        $this->assertCount(1, $mapping3->getConflicts());
        $this->assertFalse($conflict->isResolved());
    }

    /**
     * @expectedException \Puli\Manager\Api\NotLoadedException
     */
    public function testRemoveMappingFailsIfPackageNotLoaded()
    {
        $mapping = new PathMapping('/path', 'resources');
        $conflict = new PathConflict('/path/conflict');

        $conflict->removeMapping($mapping);
    }

    public function testResolveRemovesAllMappings()
    {
        $mapping1 = new PathMapping('/path', 'resources');
        $mapping1->load($this->package1, $this->packages);
        $mapping2 = new PathMapping('/path', 'resources');
        $mapping2->load($this->package2, $this->packages);
        $mapping3 = new PathMapping('/path', 'resources');
        $mapping3->load($this->package3, $this->packages);
        $conflict = new PathConflict('/path/conflict');

        $conflict->addMapping($mapping1);
        $conflict->addMapping($mapping2);
        $conflict->addMapping($mapping3);
        $conflict->resolve();

        $this->assertCount(0, $conflict->getMappings());
        $this->assertCount(0, $mapping1->getConflicts());
        $this->assertCount(0, $mapping2->getConflicts());
        $this->assertCount(0, $mapping3->getConflicts());
        $this->assertTrue($conflict->isResolved());
    }
}

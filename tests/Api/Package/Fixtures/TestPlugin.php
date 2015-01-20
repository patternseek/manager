<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Tests\Api\Package\Fixtures;

use Puli\RepositoryManager\Api\Environment\ProjectEnvironment;
use Puli\RepositoryManager\Api\Plugin\ManagerPlugin;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestPlugin implements ManagerPlugin
{
    /**
     * @var ProjectEnvironment
     */
    private static $environment;

    /**
     * @return ProjectEnvironment
     */
    public static function getEnvironment()
    {
        return self::$environment;
    }

    public function activate(ProjectEnvironment $environment)
    {
        self::$environment = $environment;
    }
}
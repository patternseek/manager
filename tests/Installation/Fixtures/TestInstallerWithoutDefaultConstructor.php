<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Installation\Fixtures;

use Puli\Manager\Api\Installation\InstallationParams;
use Puli\Manager\Api\Installer\ResourceInstaller;
use Puli\Repository\Api\Resource\Resource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestInstallerWithoutDefaultConstructor implements ResourceInstaller
{
    public function __construct($param)
    {
    }

    public function validateParams(InstallationParams $params)
    {
    }

    public function installResource(Resource $resource, InstallationParams $params)
    {
    }
}

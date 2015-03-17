<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Api\Factory;

use Puli\Factory\PuliFactory;

/**
 * Generates the source code of the Puli factory.
 *
 * The Puli factory can later be used to easily instantiate the resource
 * repository and the resource discovery in both the user's web application and
 * the Puli CLI.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface FactoryManager
{
    /**
     * Creates a {@link PuliFactory} instance.
     *
     * The factory class is regenerated if necessary.
     *
     * By default, the class is stored in the file and with the class name
     * stored in the configuration.
     *
     * @param string|null $path      If not `null`, the file will be generated
     *                               at the given path.
     * @param string|null $className If not `null`, the file will be generated
     *                               with the given class name.
     *
     * @return PuliFactory The factory instance.
     */
    public function createFactory($path = null, $className = null);

    /**
     * Generates a {@link PuliFactory} class file.
     *
     * By default, the class is stored in the file and with the class name
     * stored in the configuration.
     *
     * @param string|null $path      If not `null`, the file will be generated
     *                               at the given path.
     * @param string|null $className If not `null`, the file will be generated
     *                               with the given class name.
     */
    public function generateFactoryClass($path = null, $className = null);

    /**
     * Regenerates a {@link PuliFactory} class file if necessary.
     *
     * The file is (re-)generated if:
     *
     *  * The file does not exist.
     *  * The puli.json file was modified.
     *  * The config.json file was modified.
     *
     * The file is not (re-)generated if {@link Config::FACTORY_AUTO_GENERATE}
     * is disabled.
     *
     * By default, the class is stored in the file and with the class name
     * stored in the configuration.
     *
     * @param string|null $path      If not `null`, the file will be generated
     *                               at the given path.
     * @param string|null $className If not `null`, the file will be generated
     *                               with the given class name.
     */
    public function refreshFactoryClass($path = null, $className = null);
}

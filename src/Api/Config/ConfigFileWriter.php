<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Api\Config;

/**
 * Writes configuration files.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ConfigFileWriter
{
    /**
     * Writes a configuration file.
     *
     * @param ConfigFile $configFile The configuration file to write.
     * @param string     $path       The file path to write to.
     */
    public function writeConfigFile(ConfigFile $configFile, $path);
}

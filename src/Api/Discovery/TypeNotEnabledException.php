<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Api\Discovery;

use Exception;
use RuntimeException;

/**
 * Thrown when a type was expected to be enabled but was not.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TypeNotEnabledException extends RuntimeException
{
    /**
     * Creates an exception for a type name.
     *
     * @param string    $typeName The name of the type that was not enabled.
     * @param int       $code     The exception code.
     * @param Exception $cause    The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forTypeName($typeName, $code = 0, Exception $cause = null)
    {
        return new static(sprintf(
            'The binding type "%s" is not enabled.',
            $typeName
        ), $code, $cause);
    }
}
<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Api\Server;

use Puli\Manager\Api\Installer\NoSuchParameterException;
use Puli\Manager\Assert\Assert;
use Webmozart\Expression\Expression;

/**
 * Represents a server that serves assets.
 *
 * A server has a name which identifies the server. The document root of the
 * server can be a directory name, a URL or any other string that can be
 * interpreted by a {@link ResourceInstaller}.
 *
 * Parameters can be set on the server to pass additional information to the
 * installer that can not be obtained from the document root string. Examples
 * are user names or passwords and other, similar connection settings.
 *
 * An asset server also has a URL format. This format defines the format of
 * the URLs generated for resources installed in that server. For example, if
 * assets are installed directly in the public directory, then you will set the
 * URL format to "/%s". If resources are installed in the sub-directory
 * "resources", the proper URL format is "/resources/%s". If the server has a
 * different domain than the domain that references the assets, you should
 * include the domain in the URL: "http://example.com/%s".
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Server
{
    /**
     * The name field in {@link Expression} instances.
     */
    const NAME = 'name';

    /**
     * The installer name field in {@link Expression} instances.
     */
    const INSTALLER_NAME = 'installerName';

    /**
     * The location field in {@link Expression} instances.
     */
    const DOCUMENT_ROOT = 'location';

    /**
     * The url format field in {@link Expression} instances.
     */
    const URL_FORMAT = 'urlFormat';

    /**
     * The parameter values field in {@link Expression} instances.
     */
    const PARAMETER_VALUES = 'parameterValues';

    /**
     * The alias for the default server.
     */
    const DEFAULT_SERVER = 'default';

    /**
     * The default URL format.
     */
    const DEFAULT_URL_FORMAT = '/%s';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $installerName;

    /**
     * @var string
     */
    private $documentRoot;

    /**
     * @var string
     */
    private $urlFormat;

    /**
     * @var string[]
     */
    private $parameterValues;

    /**
     * Creates a new server.
     *
     * @param string $name            The name of the server.
     * @param string $installerName   The name of the used installer.
     * @param string $documentRoot    The document root of the server.
     * @param string $urlFormat       The format of the generated resource URLs.
     *                                Include the placeholder "%s" for the
     *                                resource path relative to the document
     *                                root.
     * @param array  $parameterValues Values for the parameters defined by the
     *                                installer descriptor.
     */
    public function __construct($name, $installerName, $documentRoot, $urlFormat = self::DEFAULT_URL_FORMAT, array $parameterValues = array())
    {
        Assert::stringNotEmpty($name, 'The server name must be a non-empty string. Got: %s');
        Assert::notEq($name, self::DEFAULT_SERVER, 'The name name must not be "'.self::DEFAULT_SERVER.'".');
        Assert::stringNotEmpty($installerName, 'The installer name must be a non-empty string. Got: %s');
        Assert::stringNotEmpty($documentRoot, 'The server location must be a non-empty string. Got: %s');
        Assert::stringNotEmpty($urlFormat, 'The URL format must be a non-empty string. Got: %s');

        $this->name = $name;
        $this->installerName = $installerName;
        $this->documentRoot = $documentRoot;
        $this->urlFormat = $urlFormat;
        $this->parameterValues = $parameterValues;
    }

    /**
     * Returns the server name.
     *
     * @return string The server name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the name of the used installer.
     *
     * @return string The installer name.
     */
    public function getInstallerName()
    {
        return $this->installerName;
    }

    /**
     * Returns the document root of the server.
     *
     * The server location can be a directory name, a URL or any other string
     * that can be understood by a {@link ResourceInstaller}.
     *
     * @return string The document root.
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    /**
     * Returns the format of the generated resource URLs.
     *
     * The format contains the placeholder "%s" where the public path of the
     * resource is inserted.
     *
     * @return string The URL format.
     */
    public function getUrlFormat()
    {
        return $this->urlFormat;
    }

    /**
     * Returns the value of the given parameter.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value.
     *
     * @throws NoSuchParameterException If the parameter was not found.
     */
    public function getParameterValue($name)
    {
        if (!isset($this->parameterValues[$name])) {
            throw NoSuchParameterException::forParameterName($name, $this->installerName);
        }

        return $this->parameterValues[$name];
    }

    /**
     * Returns the values of all parameters.
     *
     * @return string[] The parameter values indexed by the parameter names.
     */
    public function getParameterValues()
    {
        return $this->parameterValues;
    }

    /**
     * Returns whether the server has a given parameter.
     *
     * @param string $name The parameter name.
     *
     * @return bool Returns `true` if the given parameter exists and `false`
     *              otherwise.
     */
    public function hasParameterValue($name)
    {
        return isset($this->parameterValues[$name]);
    }

    /**
     * Returns whether the server has any parameters.
     *
     * @return bool Returns `true` if any parameters are set for the server and
     *              `false` otherwise.
     */
    public function hasParameterValues()
    {
        return count($this->parameterValues) > 0;
    }

    /**
     * Returns whether the server matches the given expression.
     *
     * @param Expression $expr The search criteria. You can use the fields
     *                         {@link NAME}, {@link INSTALLER_NAME},
     *                         {@link DOCUMENT_ROOT}, {@link URL_FORMAT} and
     *                         {@link PARAMETER_VALUES} in the expression.
     *
     * @return bool Returns `true` if the server matches the expression and
     *              `false` otherwise.
     */
    public function match(Expression $expr)
    {
        return $expr->evaluate(array(
            self::NAME => $this->name,
            self::INSTALLER_NAME => $this->installerName,
            self::DOCUMENT_ROOT => $this->documentRoot,
            self::URL_FORMAT => $this->urlFormat,
            self::PARAMETER_VALUES => $this->parameterValues,
        ));
    }
}

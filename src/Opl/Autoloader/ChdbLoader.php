<?php
/*
 *  OPEN POWER LIBS <http://www.invenzzia.org>
 *
 * This file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE. It is also available through
 * WWW at this URL: <http://www.invenzzia.org/license/new-bsd>
 *
 * Copyright (c) Invenzzia Group <http://www.invenzzia.org>
 * and other contributors. See website for details.
 */
namespace Opl\Autoloader;
use chdb;
use DomainException;
use Exception;
use RuntimeException;

/**
 * A modification of the <tt>ClassMapLoader</tt> which allows to use
 * the <tt>chdb</tt> (http://pecl.php.net/package/chdb) caching extension
 * to store the class maps.
 * 
 * @author Tomasz Jędrzejewski
 * @copyright Invenzzia Group <http://www.invenzzia.org/> and contributors.
 * @license http://www.invenzzia.org/license/new-bsd New BSD License
 */
class ChdbLoader
{
	/**
	 * The default autoloader path.
	 * @var string
	 */
	private $defaultPath = '';

	/**
	 * The list of available top-level namespaces.
	 * @var array
	 */
	private $namespaces = array();
	/**
	 * The loaded class map.
	 * @var array
	 * @internal
	 */
	protected $classMap;
	/**
	 * The location where the class map is stored.
	 * @var string
	 * @internal
	 */
	protected $classMapLocation;

	/**
	 * Creates the class map loader and loads the map into the memory.
	 * The map must be constructed with the command line interface.
	 *
	 * @param string $defaultPath The default location path used for newly registered namespaces
	 * @param string $classMapLocation The chdb mapped memory file with the class map.
	 */
	public function __construct($defaultPath, $classMapLocation)
	{
		$this->setDefaultPath($defaultPath);
		$this->classMapLocation = $classMapLocation;
		try
		{
			$this->classMap = new chdb($this->classMapLocation);
		}
		catch(Exception $exception)
		{
			throw new RuntimeException('Cannot find a class map under the specified location.');
		}
	} // end __construct();

	/**
	 * Registers a new top-level namespace to match. If no path is specified, the current
	 * default path is taken.
	 *
	 * @throws RuntimeException
	 * @param string $namespace The namespace name to add.
	 * @param string $path The path to the namespace.
	 */
	public function addNamespace($namespace, $path = null)
	{
		if(isset($this->namespaces[(string)$namespace]))
		{
			throw new DomainException('The namespace '.$namespace.' is already added.');
		}
		if($path !== null)
		{
			$length = strlen($path);
			if($length == 0 || $path[$length - 1] != '/')
			{
				$path .= '/';
			}
			$this->namespaces[(string)$namespace] = $path;
		}
		else
		{
			$this->namespaces[(string)$namespace] = $this->defaultPath;
		}
	} // end addNamespace();

	/**
	 * Checks if the specified namespace is available.
	 *
	 * @param string $namespace The namespace name to check.
	 */
	public function hasNamespace($namespace)
	{
		return isset($this->namespaces[(string)$namespace]);
	} // end hasNamespace();

	/**
	 * Removes a registered top-level namespace.
	 *
	 * @throws RuntimeException
	 * @param string $namespace The namespace name to remove.
	 */
	public function removeNamespace($namespace)
	{
		if(!isset($this->namespaces[(string)$namespace]))
		{
			throw new DomainException('The namespace '.$namespace.' is not available.');
		}
		unset($this->namespaces[(string)$namespace]);
	} // end removeNamespace();

	/**
	 * Sets the default path used by the namespaces. Note that it does not affect
	 * the already added namespaces.
	 *
	 * @param string $defaultPath The new default path.
	 */
	public function setDefaultPath($defaultPath)
	{
		$length = strlen($defaultPath);
		if($length == 0 || $defaultPath[$length - 1] != '/')
		{
			$defaultPath .= '/';
		}
		$this->defaultPath = $defaultPath;
	} // end setDefaultPath();

	/**
	 * Returns the default path used by the namespaces.
	 *
	 * @return string The current default path.
	 */
	public function getDefaultPath()
	{
		return $this->defaultPath;
	} // end getDefaultPath();

	/**
	 * Returns the current class map location.
	 * 
	 * @return string
	 */
	public function getClassMapLocation()
	{
		return $this->classMapLocation;
	} // end getClassMapLocation();

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'loadClass'));
	} // end register();

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'loadClass'));
	} // end unregister();

	/**
	 * Attempts to load the specified class from a file.
	 *
	 * @param string $className The class name.
	 * @return boolean
	 */
	public function loadClass($className)
	{
		$class = $this->classMap->get($className);
		if(null !== $class)
		{
			$class = unserialize($class);
			require($this->namespaces[$class[0]].$class[1]);
		}
	} // end loadClass();
} // end ChdbLoader;
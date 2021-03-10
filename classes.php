<?php

namespace MangaReader;

interface IClasses {
	function index();
	function load();
	function show();
	function exit();
}

/**
 * Base Class
 */
abstract class Classes implements IClasses
{
	
	function __construct($args='')
	{
		# code...
	}

	function index($args='')
	{
		echo "index string: ".$args;
	}

	function load($args='')
	{
		echo "load string: ".$args;
	}

	function show($args='')
	{
		echo "show string: ".$args;
	}

	function exit($args='')
	{
		echo "exit string: ".$args;
	}
}

/**
 * Model Class
 */
class Model extends Classes
{
	
	function __construct($args='')
	{
		$this->load($args);
	}
}

/**
 * View Class
 */
class View extends Classes
{
	
	function __construct($args='')
	{
		$this->load($args);
	}
}

/**
 * Controller Class
 */
class Controller extends Classes
{
	
	function __construct($args='')
	{
		$this->load($args);
	}
}
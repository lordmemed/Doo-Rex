<?php

namespace MangaReader;

/**
 * CRUD Class
 */
class CRUD
{
	
	function __construct($args='')
	{
		# code...
	}
	
	function create($args='')
	{
		# code...
	}
	function read($args='')
	{
		# code...
	}
	function update($args='')
	{
		# code...
	}
	function delete($args='')
	{
		# code...
	}
	function save($id)
	{
		if ($id !== null) {
			$this->update($id);
		} else {
			$this->create();
		}
	}
	
}
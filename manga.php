<?php

namespace MangaReader;

/**
 * Manga Class
 */
class Manga
{
	
	function __construct(private Database $db)
	{
		# code...
	}
	
	function add_manga(array $data)
	{
		# code...
	}
	
	function add_chapter(array $data)
	{
		# code...
	}
	
	function get_manga($id) //if id=null then get all manga else get single manga based on id
	{
		if ($id !== null) {
		
		} else {
		
		}
	}
	
	function get_chapter($id, $mid) //if id=null then get all chapter in mid else get single chapter based on id
	{
		if ($id !== null) {
		
		} else {
		
		}
	}
	
	function edit_manga($id)
	{
		# code...
	}
	
	function edit_chapter($id)
	{
		# code...
	}
	
	function delete_manga($id)
	{
		# code...
	}
	
	function delete_chapter($id)
	{
		# code...
	}
	
}
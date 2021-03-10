<?php

namespace MangaReader;

use MangaReader\Database;

/**
 * Rest API Class
 */
class API
{
	//private $db;
	
	function __construct(private Database $db)
	{
		//$this->db = $db;
	}
	
	function getManga(int $mid)
	{
		$manga = $this->db->read('manga', ['where' => '`id` = '.$mid], 'one');
		
		if(isset($manga->id)) {
			if($manga->uploader_type==='member'){
				$member = $this->db->read('member', ['where' => "`id`=$manga->uploader_id"], 'one');
				$manga->uploader = $member->nickname;
			}else{
				$group = $this->db->read('group', ['where' => "`uid`=$manga->uploader_id"], 'one');
				$manga->uploader = $group->name;
			}
			
			$mstat = $this->db->read('manga_stat', ['where' => '`mid` = '.$manga->id], 'one');
			if (!$mstat) {
				$manga->rating = 0;
				$manga->view = 0;
			} else {
				$manga->rating = $mstat->rating;
				$manga->view = $mstat->view;
			}
			
			$data = [
				'id' => $manga->id,
				'title' => $manga->title,
				'artist' => $manga->artist,
				'author' => $manga->author,
				'format' => $manga->type,
				'rate' => ucfirst($manga->rate),
				'rating' => $manga->rating.'/10',
				'status' => $manga->status,
				'uploaded' => $manga->upload_date,
				'uploader' => $manga->uploader,
			];
			
			return json_encode($data);
		} else {
			return false;
		}
	}
	
	function getChapter(int $mid, int $cid)
	{
		$chapters = $this->db->read('chapter', [ 'where'=> "`mid`=$mid" ]);
		if(isset($chapters[$cid])) {
			$chapter = $chapters[($cid)-1];
			
			$data = [
				'manga' => $chapter->mid,
				'chapter' => $chapter->cid,
				'title' => $chapter->title,
				'url' => $chapter->url,
				'date' => $chapter->upload_date,
				'uploader' => $chapter->uploader_id
			];
			
			return json_encode($data);
		} else {
			return false;
		}
	}
}
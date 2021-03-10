<?php

namespace MangaReader;

/**
 * Error Class
 */

//presenter
//use Presenter;

class Error
{
	function __construct(private Presenter $pq)
	{
		#
	}
	
	function print_error($code, $referer, $redirect, $title, $desc, $message)
	{
		//load template to dom
		$this->pq->load_str_html(loadTemplate('error'));

		$this->pq->assign('title', $title.' - '.$desc);
		
		$this->pq->remove_class('.navbar > ul > li', 'active');

		$this->pq->assign('.page-title', $desc);

		$this->pq->assign('.error-list .e404', $this->pq->pnode('p')?->assign('p', 'Error URL: '.$referer));

		$this->pq->assign('.error-list .e404', 
			$this->pq->pnode('p')?->assign('p', $message), true)->assign('.error-list .e404', $this->pq->pnode('p'), true)->save()->assign('.error-list .e404 p', 
				'Go to: '.$this->pq->pnode('a', ['href'=>$redirect])->assign('a', $redirect),
			2, true);

		return; //echo $this->pq->html(true);
	}
}
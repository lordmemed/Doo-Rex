<?php

//$dte=microtime(true);

// set internal encoding
mb_internal_encoding("UTF-8");

// set timezone
setlocale(LC_TIME, "id_ID");
date_default_timezone_set("Asia/Jakarta");

session_start();	

require_once 'config.php'; //load all config variables first

if(APP_MODE==='debug'){
	error_reporting(E_ALL);
	ini_set('display_errors',1);
	ini_set('error_reporting', E_ALL);
	ini_set('display_startup_errors',1);
	error_reporting(-1);
}else{
	error_reporting(E_ALL);
	ini_set('display_errors',0);
	ini_set('error_reporting', E_NONE);
	ini_set('display_startup_errors',0);
	error_reporting(-1);
}

//dump_arr($_SERVER);

require_once 'autoloader.php'; //activate custom spl_auto_loader
require_once 'functions.php'; //load core functions

//url router
use Bramus\Router\Router;
//require_once 'database.php';
use MangaReader\Database;
//presenter
use MangaReader\Presenter;
//paginator
//use JasonGrimes\Paginator;
//manga class
use MangaReader\Manga;
//member class
use MangaReader\Member;
//error reporter
use MangaReader\Error;
//api rest
use MangaReader\API;

/* 
* connecting the dot 
* start the real work
* using Bramus\Router
*/

// In case one is using PHP 5.4's built-in server
$filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename))
{
	return false;
}

// Include the Router class
// @note: it's recommended to just use the composer autoloader when working with other packages too
//require_once __DIR__ . '/libs/Bramus/Router/Router.php';

// Create a Router
$router = new Router();

//load database
$db = new Database;
$db->connect('sqlite', 'mangareader');

//setup php dom query
$pq = new Presenter;
//load template to dom
//$pq->load_str_html(loadTemplate('main'));

// Custom 404 Handler
$router->set404(function () use ($pq)
{
	//

	http_response_code(404);
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

	/* usig error_bkp.php
		$err = new Error(404, ['url'=>$_SERVER['REQUEST_URI'], 'redirect'=>$_SESSION['rdr']]);
		echo $err->getMessages();
	} else {
		$err = new Error(404, ['url'=>$_SERVER['REQUEST_URI'], 'redirect'=>get_app_url()]);
		echo $err->getMessages();
	}

	*/

	$error = new Error($pq);
	if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
		$error->print_error(
			404, 
			$_SESSION['error']['ref'],
			$_SESSION['error']['rdr'],
			'Error',
			$_SESSION['error']['dsc'],
			$_SESSION['error']['msg']);
	} else {
		$error->print_error(
			404, 
			$_SERVER['REQUEST_URI'],
			get_app_url(),
			'Error',
			'Page Not Found',
			'Sorry, the page you requested is not found.');

		echo $pq->html(true);
	}

	unset($_SESSION['error']);
});

// Before Router Middleware
$router->before('GET', '/.*', function ()
{
	//header('X-Powered-By: Bram.US/Router');
	header('X-Powered-By: DooRex v1.0');
});


$router->get('/hash', function ()
{
	echo password_hash($_GET['pw'], PASSWORD_BCRYPT);
	exit();
});
$router->get('/svg', function ()
{
	$dir = 'assets/img/svg';
	$dirs = array_values(array_diff(scandir($dir), [".", "..", "...", ".nomedia"]));
	sort($dirs);

	foreach ($dirs as $key => $val) {
		echo "<img src='".$dir."/".$val."' height='64px' /> === ";
	}
});


// Static route: / (homepage)
$router->get('/', function () use ($pq, $db)
{
	//$dts=microtime(true);;
	//echo 'start : '.($dts-$dte).'<br>';

	//load template to dom
	$pq->load_str_html(loadTemplate('main'));
	//remove all section except for home section
	$pq->remove('.page-title');
	$pq->remove('.manga-list');
	$pq->remove('.manga-detail');
	$pq->remove('.manga-search');
	$pq->remove('.manga-reader');
	$pq->save();

	$_manga = [];
	foreach ($db->read('manga', ['limit' => 4]) as $idx => $manga)
	{
		$_manga_stat = $db->read('manga_stat', ['where' => '`mid` = '.$manga->id], 'one');
		if (!$_manga_stat)
		{
			$manga->rating = 0;
			$manga->view = 0;
		}
		$manga->rating = $_manga_stat->rating;
		$manga->view = $_manga_stat->view;
		$_manga[] = $manga;
	}

	//clone manga item
	$item = $pq->clone('.manga-item');
	//loop trough databases and assign value to manga item
	$manga_item = null; //declare variable first
	foreach ($_manga as $manga)
	{
		$item->assign_attr('a', 'title', $manga->title); //assign title tag for tooltip
		$item->assign_attr('a', 'href', get_app_url().'/manga/'.$manga->id); //assign manga link to the item
		//echo '<img src="'.$manga['img'].'" /><br>';
		//assign manga cover image source
		if ($manga->cover!=='-') {
			$item->assign_attr('.manga-cover', 'src', get_manga_location().$manga->url.'/'.$manga->cover); 
		} else {
			$item->assign_attr('.manga-cover', 'src', get_app_asset().'/img/no-title.jpg');
		}

		$item->assign('.manga-title', $manga->title); //assign manga title to item
		$item->assign('.manga-title-alt', $manga->alt_title); //assign manga alt. title to item
		$item->assign('.manga-author', 'Author: '.$manga->author); //assign manga author to item
		$item->assign('.manga-artist', 'Artist: '.$manga->artist); //assign manga artist to item
		$item->assign('.manga-status', 'Status: '.ucfirst($manga->status)); //assign manga status to item
		$item->assign('.manga-rating', 'Rating: '.$manga->rating.' / 10.0'); //assign manga rating to item
		$item->assign('.manga-view', $manga->view.' Views'); //assign manga rating to item

		$manga_item .= "\r\n".$item->html(); //add each item to the variable
	}
	$item->cleanup(); //clean up item pq dom

	//remove home section childs
	$pq->remove('.manga-home', true);
	//assign new manga list item to section
	$pq->assign('.manga-home', $manga_item, true);

	$pq->prepend('.manga-home', $pq->pnode('h3')->assign('h3', 'Latest Releases'));

	$pq->assign('.manga-home', $pq->pnode('article')->assign('article', $pq->pnode('h3')->assign('h3', 'Popular Now').$pq->pnode('p')->assign('p', 'Daftar populer')), true);

	$pq->assign('.manga-home', $pq->pnode('article')->assign('article', $pq->pnode('h3')->assign('h3', 'Recommended').$pq->pnode('p')->assign('p', 'Rekomendasi')), true);

	return; //echo $pq->html(true);
	//$dtl=microtime(true);;
	//echo 'end : '.($dtl-$dts).'<br>';
});

// Static route: /manga/list (all manga list)
$router->get('/manga/list', function () use ($pq, $db)
{
	//load template to dom
	$pq->load_str_html(loadTemplate('main'));
	//remove all section except for home section
	$pq->remove('.page-title');
	$pq->remove('.manga-home');
	$pq->remove('.manga-detail');
	$pq->remove('.manga-search');
	$pq->remove('.manga-reader');
	$pq->save();

	//set active nav-link
	$pq->remove_class('.navbar > ul > li', 'active');
	$pq->add_class('.navbar > ul > li', 'active', 1);

	$mangas = $db->read('manga', []); //get all manga stdClass object
	usort($mangas, fn($a, $b) => strcmp(ucfirst($a->title), ucfirst($b->title))); //sort by title
	$_mangas['*']=[];
	//regroudping with new key per first letter
	foreach ($mangas as $idx => $manga)
	{
		if(!is_letter($manga->title[0]))
		{
			$_mangas['*'][] = ['id'=>$manga->id, 'title'=>$manga->title, 'cover'=>$manga->cover, 'url'=>$manga->url];
		} else {
			$_mangas[ucfirst($manga->title[0])][] = ['id'=>$manga->id, 'title'=>$manga->title, 'cover'=>$manga->cover, 'url'=>$manga->url];
		}
	}

	/* Old List, title only
	//
	
	$pq->remove('.manga-list > article > ul');
	
	//loop list
	foreach ($_mangas as $key => $manga)
	{
		$pq->assign('.manga-list > article', $pq->pnode('ul',['class'=>(($key!=='*') ? $key : 'All'),'style'=>'padding-left:0;'])->assign('.'.(($key!=='*') ? $key : 'All'), $pq->pnode('b')->assign('b',$key)), true)->save();
		foreach ($manga as $idx => $val)
		{
			$pq->assign('.manga-list > article > .'.(($key!=='*') ? $key : 'All'), $pq->pnode('li')
					->assign('li',
						$pq->pnode('a', ['href'=>get_app_url().'/manga/'.$val['id']])
						->assign('a',$val['title'])
				)->assign_attr('li', 'style', 'margin-left: 19px; list-style: circle;'), true);
		}
	}
	//
	*/

	//* function list manga with image

	$pq->remove('.manga-list-small > ul.item');

    foreach ($_mangas as $key => $manga) {
        $class = '.'.(($key!=='*') ? $key : 'All');

        $pq->assign('.manga-list-small', 
            $pq->pnode('ul', ['class'=>'item '.substr($class, 1)])
            ->assign($class, $pq->pnode('div')
                ->assign('div', $pq->pnode('b')->assign('b',(($key!=='*') ? $key : '#ALL')))
                ->assign('div', ' '.$pq->pnode('span')->assign('span','('.count($manga).' Title)'), true))
            , true)->save();

        foreach ($manga as $idx => $val)
		{
			//print_r($val);
        	//assign manga cover image source
			if ($val['cover']!=='-') {
				$img = get_manga_location().$val['url'].'/'.$val['cover'];
			} else {
				$img = get_app_asset().'/img/no-title.jpg'; 
			}
			
			$pq->assign('.manga-list-small > '.$class, 
                $pq->pnode('li')->assign('li',
                    $pq->pnode('a', ['title' => $val['title'],
                    'href' => get_app_url().'/manga/'.$val['id']])
                    ->assign('a', 
                        $pq->pnode('img', ['src'=>'/imager/thumb?url='.rawurlencode($img)])
                    )
                    ->assign('a', 
                        $pq->pnode('div')->assign('div', $val['title'])
                        , true
                    )
                )
                , true);
		}

    }
	//

	return; //echo $pq->html(true);
});

//manga & chapter
// ([A-Za-z0-9-]+) => $url
$router->get('/manga/([1-9]\d*)', function ($mid) use ($pq, $db, $router)
{
	//$dts=microtime(true);;
	//echo 'start : '.($dts-$dte).'<br>';

	//load template to dom
	$pq->load_str_html(loadTemplate('main'));

	//remove all section except for manga details section
	$pq->remove('.manga-home');
	$pq->remove('.manga-list');
	$pq->remove('.manga-search');
	$pq->remove('.manga-reader');
	$pq->save();

	//set active nav-link
	$pq->remove_class('.navbar > ul > li', 'active');

	$manga = $db->read('manga', ['where' => '`id` = '.$mid], 'one');
	if(isset($manga->id))
	{
		$mstat = $db->read('manga_stat', ['where' => '`mid` = '.$manga->id], 'one');
		if (!$mstat)
		{
			$manga->rating = 0;
			$manga->view = 0;
		} else {
			$manga->rating = $mstat->rating;
			$manga->view = $mstat->view;
		}

		switch ($manga->rate) {
			case 'e':
				$manga->rate = 'Everyone';
				break;
			case 'r13':
				$manga->rate = 'R13+';
				break;
			case 'r15':
				$manga->rate = 'R15+';
				break;
			case 'r18':
				$manga->rate = 'R18+';
				break;
		}

		$pq->assign('title', ' - '.$manga->title, true);
		$pq->assign('.page-title', 
			$pq->pnode('ul', ['class' => 'breadcrumb'])
				->assign('ul', 
					$pq->pnode('li')->assign('li', $pq->pnode('a')->assign('a', 'Read'))
				)->assign('ul',
					$pq->pnode('li')->assign('li', $pq->pnode('span', ['title' => $manga->title])->assign('span', $manga->title)), true
				)
		);

		//assign manga cover image source
		if ($manga->cover!=='-') {
			$pq->assign_attr('.manga-detail img', 'src', get_manga_location().$manga->url.'/'.$manga->cover); 
		} else {
			$pq->assign_attr('.manga-detail img', 'src', get_app_asset().'/img/no-title.jpg'); 
		}

		$pq->assign('.manga-title', $manga->title); //assign manga title to item
		$pq->assign('.manga-title-alt', $manga->alt_title); //assign manga alt. title to item
		$pq->assign('.manga-author', $manga->author); //assign manga author to item
		$pq->assign('.manga-artist', $manga->artist); //assign manga artist to item
		$pq->assign('.manga-status', ucfirst($manga->status)); //assign manga status to item
		$pq->assign('.manga-rate', $manga->rate); //assign manga rate to item
		$pq->assign('.manga-rating', $manga->rating.' / 10.0'); //assign manga rating to item
		$pq->assign('.manga-view', $manga->view.' Views'); //assign manga views to item
		if($manga->uploader_type==='member')
		{
			$member = $db->read('member', ['where' => "`id`=$manga->uploader_id"], 'one');
			//assign link & link text
			$pq->assign_attr('.manga-uploader a', 'href', get_app_url().'/member/'.$member->id);
			$pq->assign('.manga-uploader a', $member->fullname);
		}else{
			//$group = $db->read('group', ['where' => "`id`=$manga->uploader_id"], 'one');
		}
		$pq->assign('.manga-format', ucfirst($manga->type)); //assign manga type/format to item

		$chapters = $db->read('chapter', [ 'where'=> "`mid`=$manga->id" ]);
		if(!empty($chapters)){
			//clone manga item
			$item = $pq->clone('.chapter-list div');
			//loop trough databases and assign value to chapter item
			$chapter_item = null; //declare variable first
			//echo htmlentities($item->html(true));
			//sort the right order first
			sort($chapters);
			//the repeat
			foreach ($chapters as $chapter)
			{
				$item->assign_attr('a','href',get_app_url().str_replace('-','/',$chapter->url));
				$item->assign('a',$chapter->title);
				$t=strtotime($chapter->upload_date);
				$dt=date('d-m-Y H:i', $t);
				//echo $dt.'<br>';
				$uploader = $db->read('member', ['where' => "`id`=$chapter->uploader_id"], 'one')->fullname;
				$item->assign('a', $item->pnode('span', ['style'=>'float: right; margin-top: 2px;'])->assign('span', $uploader.' // '.dayToID($dt).' '.$dt), true);
				$chapter_item.="\r\n".$item->html();
			}
			$item->cleanup(); //clean up item pq dom
					if(count($chapters)<10){
				$pq->remove('.chapters #showChapter');
				$pq->remove('.chapters script');
			}
					$pq->assign('.chapter-list',$chapter_item);
		} else {
			$pq->remove('.chapters #showChapter');
			$pq->remove('.chapters script');
			$pq->assign('.chapter-list','No chapter uploaded, yet.');
		}

		return; //echo $pq->html(true);
	} else {
		$_SESSION['error'] = [
			'ref' => $_SERVER['REQUEST_URI'],
			'rdr' => '/manga/list',
			'dsc' => 'Manga Not Found',
			'msg' => 'No Manga with ID: '.$mid
		];
		$router->trigger404();
		return; //echo $pq->html(true);
	}
	//$dtl=microtime(true);;
	//echo 'end : '.($dtl-$dts).'<br>';
});

$router->get('/manga/([1-9]\d*)/chapter/([1-9]\d*)', function ($mid, $cid) use ($pq, $db, $router)
{
	//$dts=microtime(true);
	//echo 'start : '.($dts-$dte).'<br>';
	//load template to dom
	$pq->load_str_html(loadTemplate('main'));
	$pq->remove('.manga-home');
	$pq->remove('.manga-list');
	$pq->remove('.manga-search');
	$pq->remove('.manga-detail');
	$pq->save();

	//set active nav-link
	$pq->remove_class('.navbar > ul > li', 'active');

	$manga = $db->read('manga', ['where' => '`id` = '.$mid], 'one');
	if(!(isset($_SESSION['ss_auth']) && $_SESSION['ss_auth'] == true)){
		if(isset($manga->id))
		{
			$pq->assign('title',' - Chapter Not Available', true);
			$pq->assign('.page-title', 'Chapter Not Available!');
			//
			//$item = $pq->clone('.non-member');
			$pq->assign('.manga-reader', $pq->clone('.non-member')->html(true)); $pq->save();

			//login button + referer
			$_SESSION['ref'] = $_SERVER['REQUEST_URI'];
			//$pq->assign_attr('.back-button a', 'href', '?ref='.$_SESSION['ref'], true);

			return; //echo $pq->html(true);
		} else {
			$_SESSION['error'] = [
				'ref' => $_SERVER['REQUEST_URI'],
				'rdr' => '/manga/list',
				'dsc' => 'Manga Not Found',
				'msg' => 'No Manga with ID: '.$mid
			];
			$router->trigger404();
			return; //echo $pq->html(true);
		}
	} else {
		$pq->remove('.non-member');$pq->save();
			if(isset($manga->id))
		{
			$chapters = $db->read('chapter', [ 'where'=> "`mid`=$manga->id" ]);

			if (!empty($chapters[($cid-1)])){
				//sort for the right order first
				sort($chapters);
				//then set current reading chapter
				$chapter = $chapters[($cid-1)];
				$pq->assign('title',' - '.$chapter->title, true);
				
				$dir = 'assets/content'.$chapter->url;

				$pq->assign('.page-title', 
				$pq->pnode('ul', ['class' => 'breadcrumb'])
					->assign('ul', 
						$pq->pnode('li')->assign('li', $pq->pnode('a')->assign('a', 'Read'))
					)->assign('ul',
						$pq->pnode('li')->assign('li', 
							$pq->pnode('a', ['href' => '/manga/'.$manga->id, 'title' => $manga->title])->assign('a', 
								$pq->pnode('span')->assign('span', $manga->title))
						), true
					)->assign('ul', 
						$pq->pnode('li')->assign('li', $pq->pnode('span')->assign('span', $chapter->title)), true
					)
			);

				$dir = array_values(array_diff(scandir($dir), [".", "..", "...", ".nomedia", "Thumb.db"]));
				if(empty($dir)) $dir[]='no-img'; 
				if($manga->read_mode==='hr') $dir = array_reverse($dir);
				$pq->add_class('.manga-reader article', $manga->read_mode);
				$item = $pq->clone('.manga-reader article .images img');

				$imgs=null;
				foreach($dir as $img){
					$cimg = get_app_url().'/image?url='.urlencode(get_manga_location().$chapter->url.'/'.$img);
					//$cimg = get_manga_location().$chapter->url.'/'.$img;
					$item->assign_attr('img', 'src', $cimg);
					$imgs.="\r\n".$item->html();
				}
				$item->cleanup();
				$pq->assign('.manga-reader article .images', $imgs); $pq->save();
							if(count($dir)>1){
					if($manga->read_mode==='hl'){
						$pq->add_class('.images .img', 'show');
						//images control button
						$pq->assign('#prevPage', '&laquo; Prev. Page');
						$pq->assign('#nextPage', 'Next Page &raquo;');
					}elseif($manga->read_mode==='hr'){
						$pq->add_class('.images .img', 'show', -1);
						//images control button
						$pq->assign('#prevPage', '&laquo; Next Page');
						$pq->assign('#nextPage', 'Prev. Page &raquo;');
					}else{
						$pq->remove('.images-control');
					}
				}else{
					$pq->remove_class('.images .img', 'img');
					$pq->assign('.images-control', $pq->get_element('#pageNumber'));
				}
				if (count($chapters) > 1)
				{
					$item = $pq->clone('.paginator'); //clone paginator
					$pq->remove('.paginator'); //remove from the layout
					$item_o = $item->clone('select option');
					$item->remove('option');
					$prev_url = ($cid !== '1') ? get_app_url().'/manga/'.$mid.'/chapter/'.($cid-1) : null;
					if ($prev_url)
					{  
						$item->assign_attr('.prev-button a', 'href', $prev_url);
					} else {
						$item->assign_attr('.prev-button a', 'href', null);
						$item->assign('.prev-button a', 'First Chapter');
					}
					$next_url = ($cid < count($chapters)) ? get_app_url().'/manga/'.$mid.'/chapter/'.($cid+1) : null;
					if ($next_url)
					{
						$item->assign_attr('.next-button a', 'href', $next_url);
						$item->remove('.back-button');
					} else {
						$item->assign_attr('.next-button a', 'href', null);
						$item->assign('.next-button a', 'Last Chapter');
						$item->assign_attr('.back-button a', 'href', get_app_url().'/manga/'.$mid);
					}
					foreach($chapters as $ichapter){
						if (($ichapter->cid) == $cid)
						{
							$item_o->assign_attr('option', 'selected', '');
						} else {
							$item_o->assign_attr('option', 'selected', null);
						}
						
						$item_o->assign('option', $ichapter->title);
						$item_o->assign_attr('option', 'value', get_app_url().str_replace('-','/',$ichapter->url));
						$item->assign('select', $item_o->html(), true);
					}

					$pagination=$item->html();
					$item->cleanup();
					$item_o->cleanup();
					$pq->assign('.manga-reader', $pagination, true);
				} else {
					//if the manga just have 1 chapter then clean paginator
					$pq->assign('.paginator', 'No More Chapter'); //set the paginator with 'no more/next chapter'
				}
			} else {
				$pq->assign('title',' - No Chapter',true);
				$pq->assign('.page-title','No Chapter. Yet?');
				$pq->assign('.manga-reader', $pq->pnode('p')->assign('p', 'It seems Chapter '.$cid.' is not available.'));
				$pq->assign('.manga-reader', $pq->pnode('img',['style'=>'width: 100%;', 'src'=>get_app_asset().'/img/no-chapter.jpg']), true);
			}

			return; //echo $pq->html(true);
		} else {
			$_SESSION['error'] = [
				'ref' => $_SERVER['REQUEST_URI'],
				'rdr' => '/manga/list',
				'dsc' => 'Manga Not Found',
				'msg' => 'No Manga with ID: '.$mid
			];
			$router->trigger404();
			return; //echo $pq->html(true);
		}
	}
	//$dtl=microtime(true);;
	//echo 'end : '.($dtl-$dts).'<br>';
});

$router->get('/member/([1-9]\d*)', function ($uid) use ($pq, $db, $router)
{
	//load template to dom
	$pq->load_str_html(loadTemplate('member'));
	//set active nav-link
	$pq->remove_class('.navbar > ul > li', 'active');

	$member = $db->read('member', ['where' => '`id` = '.$uid], 'one');
	if (isset($member->id))
	{
		$data = 'Member : '.$member->nickname;
		$data .= '<br>';
		$data .= 'Name : '.$member->fullname;
		$data .= '<br>';
		$data .= 'Member since : '.date('d M Y', strtotime($member->join_date));
		$data .= '<br>';
		$data .= $member->description;
			$pq->assign('.container', $data);
			return; //echo $pq->html(true);
	} else {
		$_SESSION['error'] = [
			'ref' => $_SERVER['REQUEST_URI'],
			'rdr' => '/members',
			'dsc' => 'Member Not Found',
			'msg' => 'No Member with ID: '.$uid
		];
		$router->trigger404();
        return; //echo $pq->html(true);
	}
});

$router->get('/search', function () use ($pq)
{

	//load template to dom
	$pq->load_str_html(loadTemplate('main'));

	//remove all section except for home section
	$pq->remove('.manga-home');
	$pq->remove('.page-title');
	$pq->remove('.manga-list');
	$pq->remove('.manga-detail');
	$pq->remove('.manga-reader');
	$pq->save();

	if (isset($_GET['q']) && !empty($_GET['q']))
	{
		$pq->assign('.manga-search', 'Search for : '.urldecode($_GET['q']));
	} else {
		$pq->assign('.manga-search', '<form action="search">
				<input type="text" name="q" style="width: 100%; margin-bottom: 4px;">
				<button type="submit" style="width: 100%;">Search</button>
			</form>');
	}
});

$router->get('/add-manga', function () use ($pq)
{

	//load and setup template layout
	$tpl = file_get_contents('manga_database/manga.html'); //load the layout
	$tpl = replace_all_var_config($tpl); //replace all app variable <%var_key%> with value from config
	$tpl = set_page_var($tpl, [ /* 'page_title' => $_SERVER['REQUEST_URI'], */ 'time' => $_SERVER['REQUEST_TIME']]); //replace all variable with key=>var
	$tpl = remove_unused_var($tpl);

	//unset($tpl);

	$pq->load_str_html($tpl);
});

$router->get('/add-chapter', function () use ($pq)
{
	$tpl = file_get_contents('manga_database/chapter.html');
	$pq->load_str_html($tpl);
});

$router->post('/add-manga', function () use ($db)
{
	if (isset($_POST))
	{
		//try to predict next manga[id]
		$mid = ($db->next_id('manga') < 1) ? 1 : $db->next_id('manga')+1;
		//set data array
		$manga = [
			/* 'id' => 1, */
			'url' => '/manga-'.$mid,
			'lang' => $_POST['lang'],
			'cover' => $_POST['cover'],
			'title' => $_POST['title'],
			'alt_title' => $_POST['atitle'],
			'artist' => $_POST['artist'],
			'author' => $_POST['author'],
			'type' => $_POST['type'],
			'rate' => $_POST['rate'],
			'status' => $_POST['status'],
			'read_mode' => $_POST['rmode'],
			'uploader_type' => $_POST['utype'],
			'uploader_id' => $_POST['uid'],
			'upload_date' => date("Y-m-d H:i:s T", time())
		];
		$manga_stat = [
			'mid' => $mid,
			'rating' => 0,
			'view' => 0
		];
			//echo dump_arr($_POST, "'");
		$db->save('manga', $manga);
		$db->save('manga_stat', $manga_stat);
			$cm = $db->row_count('manga', $mid);
		$cms = $db->row_count('manga_stat', $mid, 'mid');

		if($cm && $cms)
		{
			die('Saved succesfully.');
		} else {
			die('Save failed.');
		}
	}
});

$router->post('/add-chapter', function () use ($db)
{
	if (isset($_POST))
	{
		//set data array
		$chapter = [
			'mid' => $_POST['mid'],
			'cid' => $_POST['cid'],
			'url' => '/manga-'.$_POST['mid'].'/chapter-'.$_POST['cid'],
			'title' => $_POST['title'],
			'upload_date' => date("Y-m-d H:i:s T", time())
		];
		//try to predict next chapter_stat->id
		$csid = ($db->max_id('chapter_stat') < 1) ? 1 : $db->max_id('chapter_stat')+1;
		$chapter_stat = [
			'id' => $csid, /* this table not using auto_incremenet, hence why */
			'mid' => $_POST['mid'],
			'cid' => $_POST['cid'],
			'view' => 0
		];
			//echo dump_arr($_POST, "'");
		$db->save('chapter', $chapter);
		$db->save('chapter_stat', $chapter_stat);
			$cm = $db->row_count('chapter', $chapter['url'], 'url'); //search using unique key
		$cms = $db->row_count('chapter_stat', $csid, 'id');

		if($cm && $cms)
		{
			die('Saved succesfully.');
		} else {
			die('Save failed.');
		}
	}
});

//maestro pages
$router->mount('/maestro', function() use ($router, $pq, $db) 
{
	//maestro home
	$router->get('/', function () use ($pq, $db)
	{
		//$_SESSION['ref'] = $_SERVER['REQUEST_URI'];
		$member = new Member($db);
		$member->auth(1);
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Dashboard', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			//header('Location: /member');
			//$pq->remove('.page-header > nav', true);
			//$pq->remove('.page-content > .search-and-user');
			$pq->assign('.search-and-user > form', '');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->assign('.page-content > .grid', $pq->clone('.page-content > .grid > .non-member')->html(true));
			//$pq->remove('.page-content > .grid > article');
			$pq->assign('.page-header > nav', $pq->clone('.page-header > nav > .logo')->html(true));
			//$pq->assign('.page-header > nav', 'Not Available!');
			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->assign('.admin-profile > span.greeting', 'not in grup '.$member->get_user($_SESSION['ss_user'], 'username')->full_name);

		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

		}

		return; //echo $pq->html(true);
	});
	//maestro project
	$router->get('/projects', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Projects', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .dashboard');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			$pq->remove('.addit-manga');
			$pq->remove('.addit-chapter');
			//$pq->assign('.projects h1', 'Your Project');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Your Project');

			$_manga = [];
			foreach ($db->read('manga', ['where' => '`uploader_id` = '.$member->get_user($_SESSION['ss_user'], 'username')->user_id]) as $idx => $manga)
			{
				$_manga_stat = $db->read('manga_stat', ['where' => '`mid` = '.$manga->id], 'one');
				if (!$_manga_stat)
				{
					$manga->rating = 0;
					$manga->view = 0;
				}
				$manga->rating = $_manga_stat->rating;
				$manga->view = $_manga_stat->view;
				$_manga[] = $manga;
			}

			//clone manga item
			$item = $pq->clone('.project-list .p-item');
			//loop trough databases and assign value to manga item
			$manga_item = null; //declare variable first
			foreach ($_manga as $manga)
			{
				//assign manga cover image source
				if ($manga->cover!=='-') {
					$item->assign_attr('.p-cover img', 'src', get_manga_location().$manga->url.'/'.$manga->cover); 
				} else {
					$item->assign_attr('.p-cover img', 'src', get_app_asset().'/img/no-title.jpg'); 
				}

				$uri = trim(strtok($_SERVER['REQUEST_URI'], '?'));
				$uri = substr($uri, strlen($uri)-1)!=='/' ? $uri : substr($uri, 0, strlen($uri)-1);

				$item->assign_attr('a', 'href', $uri.'/'.$manga->id); //assign manga title to item
				$item->assign('.p-title', $manga->title); //assign manga title to item
				$item->assign('.p-status span', ucfirst($manga->status)); //assign manga status to item

				$manga_item .= "\r\n".$item->html(); //add each item to the variable
			}

			//$item = $pq->clone('.project-list .p-item');

			//assign manga cover image source
			$item->assign_attr('.p-cover img', 'src', get_app_asset().'/img/no-title.jpg');
			$item->assign_attr('.p-cover img', 'height', '90%');

			$item->assign_attr('a','href', '/maestro/projects/add-manga');  //assign link to item
			$item->assign('.p-title', '+ ADD MANGA'); //assign title to item
			$item->remove('.p-status');


			//$item->cleanup(); //clean up item pq dom

			//remove home section childs
			$pq->remove('.project-list .pt-item', true);
			//assign new manga list item to section
			$pq->assign('.project-list .pt-item', $manga_item, true);
			//assign add manga button
			$pq->assign('.project-list .pt-item', $item->html(true), true);
		}

		return; //echo $pq->html(true);
	});

	$router->get('/projects/([1-9]\d*)', function ($mid) use ($pq, $db)
	{
		$member = new Member($db);
		//$member->auth(1);
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro ID:'.$mid, true);

		$pq->remove('.search-and-user > form > input');
		$pq->remove('.search-and-user > form > button');
		$pq->remove('.search-and-user .notifications .badge');

		$pq->remove('.page-content > .dashboard');
		$pq->remove('.page-content > .members');
		$pq->remove('.page-content > .statistics');
		$pq->remove('.page-content > .group-chats');
		$pq->remove('.page-content > .notifications');
		$pq->remove('.page-content > .settings');

		$pq->remove('.page-header > nav > div.logo');
		$pq->remove('.page-content > .grid > .non-member');

		$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

		$pq->remove('.addit-manga');
		$pq->remove('.addit-chapter');
		//$pq->assign('.projects h1', 'Your Project');
		$pq->remove('.projects h1');
		$pq->assign('.search-and-user h1', 'Your Project');

		$chapters = $db->read('chapter', [ 'where'=> "`mid`=$mid" ]);
		if(!empty($chapters)){
			//clone ch item
			$item = $pq->clone('.project-list .p-item');
			//loop trough databases and assign value to chapter item
			$chapter_item = null; //declare variable first
			//echo htmlentities($item->html(true));
			//sort the right order first
			sort($chapters);
			//the repeat
			foreach ($chapters as $chapter)
			{
				$t=strtotime($chapter->upload_date);
				$dt=date('d-m-Y', $t);
				
				//assign manga cover image source
				$item->assign_attr('.p-cover img', 'src', get_app_asset().'/img/no-title.jpg');
				$item->assign_attr('.p-cover img', 'height', '50%');

				$uploader = $db->read('member', ['where' => "`id`=$chapter->uploader_id"], 'one')->nickname;

				$item->assign_attr('a','href',get_app_url().str_replace('-','/',$chapter->url));  //assign manga title to item
				$item->assign('.p-title', $chapter->title); //assign manga title to item
				$item->assign('.p-status', $item->pnode('span', ['style'=>'margin-top: 2px;'])->assign('span', 'By: '.$uploader.' <br/> Date: '.$dt)); //assign manga status to item
				
				$chapter_item.="\r\n".$item->html();
			}

			//assign manga cover image source
			$item->assign_attr('.p-cover img', 'src', get_app_asset().'/img/no-title.jpg');
			$item->assign_attr('.p-cover img', 'height', '90%');

			$uploader = $db->read('member', ['where' => "`id`=$chapter->uploader_id"], 'one')->nickname;

			$item->assign_attr('a','href', '/maestro/projects/'.$mid.'/add-chapter');  //assign link to item
			$item->assign('.p-title', '+ ADD CHAPTER'); //assign title to item
			$item->remove('.p-status');

			$chapter_item.="\r\n".$item->html();
			
			$item->cleanup(); //clean up item pq dom
			
			$pq->assign('.project-list .pt-item', $chapter_item);
		} else {
			$item = $pq->clone('.project-list .p-item');

			$pq->assign('.project-list .p-item','No chapter uploaded, yet.');

			//assign manga cover image source
			$item->assign_attr('.p-cover img', 'src', get_app_asset().'/img/no-title.jpg');
			$item->assign_attr('.p-cover img', 'height', '90%');

			$item->assign_attr('a','href', '/maestro/projects/'.$mid.'/add-chapter');  //assign link to item
			$item->assign('.p-title', '+ ADD CHAPTER'); //assign title to item
			$item->remove('.p-status');

			$pq->assign('.project-list .pt-item', $item->html(true), true);
		}

		return;
	});

	$router->get('/projects/([1-9]\d*)/add-chapter', function ($mid) use ($pq, $db)
	{
		$member = new Member($db);
		//$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Projects', true);
		
		$pq->remove('.search-and-user > form > input');
		$pq->remove('.search-and-user > form > button');
		$pq->remove('.search-and-user .notifications .badge');

		$pq->remove('.page-content > .dashboard');
		$pq->remove('.page-content > .members');
		$pq->remove('.page-content > .statistics');
		$pq->remove('.page-content > .group-chats');
		$pq->remove('.page-content > .notifications');
		$pq->remove('.page-content > .settings');

		$pq->remove('.page-header > nav > div.logo');
		$pq->remove('.page-content > .grid > .non-member');

		$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

		$pq->remove('.project-list');
		$pq->remove('.addit-manga');
		//$pq->assign('.projects h1', 'Add Chapter');
		$pq->remove('.projects h1');
		$pq->assign('.search-and-user h1', 'Add Chapter');

		$pq->assign_attr('#mid', 'value', $mid);

		//try to predict next manga[id]
		$cid = ($db->row_count('chapter', $mid, 'mid') < 1) ? 1 : $db->row_count('chapter', $mid, 'mid')+1;
		$pq->assign_attr('#cid', 'value', $cid);

		return; //echo $pq->html(true);
	});

	//maestro add chapter post
	$router->post('/projects/add-chapter', function () use ($pq, $db)
	{
		$member = new Member($db);
		$uid = $member->get_user($_SESSION['ss_user'], 'username')->user_id;

		if (isset($_POST))
		{
			//print_r($_POST);
			//set data array
			$chapter = [
				'mid' => $_POST['mid'],
				'cid' => $_POST['cid'],
				'url' => '/manga-'.$_POST['mid'].'/chapter-'.$_POST['cid'],
				'title' => $_POST['title'],
				'uploader_id' => $uid,
				'upload_date' => date("Y-m-d H:i:s T", time())
			];
			//try to predict next chapter_stat->id
			$csid = ($db->max_id('chapter_stat') < 1) ? 1 : $db->max_id('chapter_stat')+1;
			$chapter_stat = [
				'id' => $csid, /* this table not using auto_incremenet, hence why */
				'mid' => $_POST['mid'],
				'cid' => $_POST['cid'],
				'view' => 0
			];
				//echo dump_arr($_POST, "'");
			$db->save('chapter', $chapter);
			$db->save('chapter_stat', $chapter_stat);
			$cm = $db->row_count('chapter', $chapter['url'], 'url'); //search using unique key
			$cms = $db->row_count('chapter_stat', $csid, 'id');

			if($cm && $cms)
			{
				die('Saved succesfully.');
			} else {
				die('Save failed.');
			}
		}
	});

	$router->post('/upload', function () use ($db)
	{
		//check if there's a GET[url] parameter
		if (!empty($_GET) || isset($_GET['url']))
		{
		    // Create directory if it does not exist
		    if(!is_dir("assets/content/". $_GET['url'] ."/")) {
		        mkdir("assets/content/". $_GET['url'] ."/", 0777);
		    }
		    $udir = 'assets/content/'.$_GET['url'].'/';
		} else {
			$udir = 'assets/upload/';
		}

		$ph = new Plupload\PluploadHandler(array(
			'target_dir' => $udir,
			'allow_extensions' => 'jpg,jpeg,png,gif'
		));

		$ph->sendNoCacheHeaders();
		$ph->sendCORSHeaders();

		if ($result = $ph->handleUpload()) {
			die(json_encode(array(
				'OK' => 1,
				'info' => $result
			)));
		} else {
			die(json_encode(array(
				'OK' => 0,
				'error' => array(
					'code' => $ph->getErrorCode(),
					'message' => $ph->getErrorMessage()
				)
			)));
		}
	});

	$router->get('/projects/([1-9]\d*)/edit', function ($mid) use ($pq, $db)
	{
		//
	});

	$router->get('/projects/([1-9]\d*)/del-chapter', function ($mid) use ($pq, $db)
	{
		//
	});

	$router->get('/projects/([1-9]\d*)/del-project', function ($mid) use ($pq, $db)
	{
		//
	});

	$router->get('/projects/add-manga', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Projects', true);
		
		$pq->remove('.search-and-user > form > input');
		$pq->remove('.search-and-user > form > button');
		$pq->remove('.search-and-user .notifications .badge');

		$pq->remove('.page-content > .dashboard');
		$pq->remove('.page-content > .members');
		$pq->remove('.page-content > .statistics');
		$pq->remove('.page-content > .group-chats');
		$pq->remove('.page-content > .notifications');
		$pq->remove('.page-content > .settings');

		$pq->remove('.page-header > nav > div.logo');
		$pq->remove('.page-content > .grid > .non-member');

		$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

		$pq->remove('.project-list');
		$pq->remove('.addit-chapter');
		//$pq->assign('.projects h1', 'Add Project');
		$pq->remove('.projects h1');
		$pq->assign('.search-and-user h1', 'Add Project');

		return; //echo $pq->html(true);
	});
	
	//maestro members
	$router->get('/members', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Members', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .dashboard');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			//$pq->assign('.projects h1', 'Members');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Members');
		}

		return; //echo $pq->html(true);
	});
	//maestro statistics
	$router->get('/statistics', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Statistics', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .dashboard');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			//$pq->assign('.projects h1', 'Statistics');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Statistics');
		}

		return; //echo $pq->html(true);
	});
	//maestro group-chats
	$router->get('/chats', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Group Chats', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .dashboard');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			//$pq->assign('.projects h1', 'Group Chats');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Group Chats');
		}

		return; //echo $pq->html(true);
	});
	//maestro notifications
	$router->get('/notifications', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Notifications', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .dashboard');
			$pq->remove('.page-content > .settings');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			//$pq->assign('.projects h1', 'Notifications');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Notifications');
		}

		return; //echo $pq->html(true);
	});
	//maestro settings
	$router->get('/settings', function () use ($pq, $db)
	{
		$member = new Member($db);
		$member->auth();
		//load template to dom
		$pq->load_str_html(loadTemplate('admin'));
		$pq->assign('title',' - Maestro Settings', true);
		if (!$member->is_in_group($member->get_user($_SESSION['ss_user'], 'username')->user_id))
		{
			header('Location: /member');
			exit();
		} else {

			$pq->remove('.search-and-user > form > input');
			$pq->remove('.search-and-user > form > button');
			$pq->remove('.search-and-user .notifications .badge');

			$pq->remove('.page-content > .projects');
			$pq->remove('.page-content > .members');
			$pq->remove('.page-content > .statistics');
			$pq->remove('.page-content > .group-chats');
			$pq->remove('.page-content > .notifications');
			$pq->remove('.page-content > .dashboard');

			$pq->remove('.page-header > nav > div.logo');
			$pq->remove('.page-content > .grid > .non-member');

			$pq->assign('.admin-profile > span.greeting', $member->get_user($_SESSION['ss_user'], 'username')->full_name);

			//$pq->assign('.projects h1', 'Group Settings');
			$pq->remove('.projects h1');
			$pq->assign('.search-and-user h1', 'Group Settings');
		}

		return; //echo $pq->html(true);
	});

	//maestro add manga post
	$router->post('/projects/add-manga', function () use ($pq, $db)
	{
		$member = new Member($db);

		//try to predict next manga[id]
		$mid = ($db->next_id('manga') < 1) ? 1 : $db->next_id('manga')+1;
		
		// Create directory if it does not exist
		if(!is_dir('assets/content/manga-'.$mid.'/')) {
			mkdir('assets/content/manga-'.$mid.'/', 0777);
		}

		$cover = true;
		//upload cover file
		if (empty($_FILES) || $_FILES["cover"]["error"]) {
    		//die('{"OK": 0}');
    		$cover = false;
		}
		if(isset($_FILES['cover'])) 
		{
			$errors = [];
			$maxsize = 2097152;
			$acceptable = [
				'image/jpeg',
				'image/jpg',
				'image/gif',
				'image/png'
			];
			$ext = null;

			if(($_FILES['cover']['size'] >= $maxsize) || ($_FILES["cover"]["size"] == 0)) 
			{
				$errors[] = 'File too large. File must be less than 2 megabytes.';
			}
				if((!in_array($_FILES['cover']['type'], $acceptable)) && (!empty($_FILES["cover"]["type"]))) {
					$errors[] = 'Invalid file type. Only JPG, GIF and PNG types are accepted.';
			}

			if(count($errors) === 0) {
				move_uploaded_file($_FILES['cover']['tmp_name'], 'assets/content/manga-'.$mid.'/cover.jpg');
			} else {
				foreach($errors as $error) {
				//echo '<script>alert("'.$error.'");</script>';
				}
				//die('{"OK":"1"}'); //Ensure no more processing is done
			}
		}

		if (isset($_POST))
		{
			//set data array
			$manga = [
				/* 'id' => 1, */
				'url' => '/manga-'.$mid,
				'lang' => $_POST['lang'],
				'cover' => (!$cover) ? '-' : 'cover.jpg',
				'title' => $_POST['title'],
				'alt_title' => $_POST['atitle'],
				'artist' => $_POST['artist'],
				'author' => $_POST['author'],
				'type' => trim($_POST['type']),
				'rate' => $_POST['rate'],
				'status' => $_POST['status'],
				'read_mode' => $_POST['rmode'],
				'uploader_type' => 'member',
				'uploader_id' => $member->get_user($_SESSION['ss_user'], 'username')->user_id,
				'upload_date' => date("Y-m-d H:i:s T", time())
			];
			$manga_stat = [
				'mid' => $mid,
				'rating' => 0,
				'view' => 0
			];
			
			//echo dump_arr($manga, "'");
			//echo $db->get_message();
			$db->save('manga', $manga);
			$db->save('manga_stat', $manga_stat);
			$cm = $db->row_count('manga', $mid);
			$cms = $db->row_count('manga_stat', $mid, 'mid');

			if($cm && $cms)
			{
				die('Saved succesfully.');
			} else {
				die('Save failed.');
			}
		}
	});

});

//member pages
$router->mount('/member', function () use ($router, $pq, $db)
{
	//member home
	$router->get('/', function () use ($pq, $db)
	{

		$member = new Member($db);
		$member->auth();

		//load template to dom
		$pq->load_str_html(loadTemplate('member'));

		$pq->remove('.login');
		$pq->remove('.registration');
		$pq->remove('.forgot');
		$pq->remove('.logout');

		$pq->assign('title',' - Your Dashboard', true);
		$pq->assign('.page-title', 'Manage Your Account');

		$pq->assign('.dashboard > div.form > p', 'Hey, <b>'.$member->get_user($_SESSION['ss_user'], 'username')->full_name.'</b> !');

		return; //echo $pq->html(true);
	});
	$router->get('/login', function () use ($pq, $db)
	{
		$member = new Member;
		$member->auth();

		//load template to dom
		$pq->load_str_html(loadTemplate('member'));

		$pq->remove('.dashboard');
		$pq->remove('.registration');
		$pq->remove('.forgot');
		$pq->remove('.logout');

		$pq->remove('.page-title');

		$pq->assign('title',' - Login', true);

		return; //echo $pq->html(true);
	});
	$router->get('/register', function () use ($pq, $db)
	{
		//$member = new MangaReader\Member;
		//$member->auth();
		$member = new Member;
		$member->auth();

		//load template to dom
		$pq->load_str_html(loadTemplate('member'));

		$pq->remove('.dashboard');
		$pq->remove('.login');
		$pq->remove('.forgot');
		$pq->remove('.logout');

		$pq->remove('.page-title');

		$pq->assign('title',' - Register', true);

		return; //echo $pq->html(true);
	});
	$router->get('/forgot', function () use ($pq, $db)
	{
		//load template to dom
		$pq->load_str_html(loadTemplate('member'));
		$pq->remove('.dashboard');
		$pq->remove('.registration');
		$pq->remove('.login');
		$pq->remove('.logout');

		$pq->assign('title',' - Remember the forgotten', true);
		$pq->assign('.page-title', 'Remember your password');

		return; //echo $pq->html(true);
	});
	$router->get('/logout', function () use ($pq, $db)
	{
		//load template to dom
		$pq->load_str_html(loadTemplate('member'));
		$pq->remove('.dashboard');
		$pq->remove('.registration');
		$pq->remove('.forgot');
		$pq->remove('.login');

		$pq->assign('title',' - Logout', true);
		$pq->assign('.page-title', 'Logout from system');

		$member = new MangaReader\Member($db);

		if($member->logout()){
			return; //echo $pq->html(true);
		} else {
			$member->auth();
		}
	});
	$router->post('/register', function () use ($pq, $db)
	{
		//session start
		require_once 'libs/securimage/securimage.php';
		$securimage = new Securimage();

		if ($securimage->check($_POST['captcha_code']) == false) {

		//load template to dom
			$pq->load_str_html(loadTemplate('member'));
	
			$pq->remove('.dashboard');
			$pq->remove('.registration');
			$pq->remove('.forgot');
			$pq->remove('.logout');

			$pq->remove('.page-title');

			$pq->assign('title',' - Login', true);
	
			$pq->assign('.form > .error', 'The security code entered was incorrect. Please try again.');
			
			return; //echo $pq->html(true);
		}
		
		if(isset($_POST['user_name'])){
			$member = new Member($db);
			$status = $member->register($_POST);
			if(!isset($status['error']))
			{
				header('location: /member/login');
				exit();
			}else{
				//load template to dom
				$pq->load_str_html(loadTemplate('member'));

				$pq->remove('.dashboard');
				$pq->remove('.registration');
				$pq->remove('.forgot');
				$pq->remove('.logout');

				$pq->remove('.page-title');

				$pq->assign('title',' - Registration', true);

				$pq->assign('.form > .error', $status['error']);

				return; //echo $pq->html(true);
			}
		}
	});
	$router->post('/login', function () use ($pq, $db)
	{
		//session start
		require_once 'libs/securimage/securimage.php';
		$securimage = new Securimage();

		//if ($securimage->check($_POST['captcha_code']) !== false) {
			// the code was incorrect
			// you should handle the error so that the form processor doesn't continue

			// or you can use the following code if there is no validation or you do not know how
			//echo "The security code entered was incorrect.<br /><br />";
			//echo "Please go <a href='javascript:history.go(-1)'>back</a> and try again.";
			//exit;
		//}

		if ($securimage->check($_POST['captcha_code']) == false) {

		//load template to dom
			$pq->load_str_html(loadTemplate('member'));
	
			$pq->remove('.dashboard');
			$pq->remove('.registration');
			$pq->remove('.forgot');
			$pq->remove('.logout');

			$pq->remove('.page-title');

			$pq->assign('title',' - Login', true);
	
			$pq->assign('.form > .error', 'The security code entered was incorrect. Please try again.');
			
			return; //echo $pq->html(true);
		}

		if(isset($_POST['password'])){
			$member = new Member($db);
			$status = $member->login($_POST);
			if(!isset($status['error']))
			{
				$_SESSION["ss_auth"]=true;
				$_SESSION["ss_user"]=$_POST['user_name'];

				if(isset($_SESSION['ref'])) {
					header('Location: '.$_SESSION['ref']);
					$_SESSION['ref']=null;
					exit();
				}else{
					$member->auth(); //goto dashboard
				}
				//$pq->load_str_html(loadTemplate('member'));
			}else{
				//load template to dom
				$pq->load_str_html(loadTemplate('member'));

				$pq->remove('.dashboard');
				$pq->remove('.registration');
				$pq->remove('.forgot');
				$pq->remove('.logout');

				$pq->remove('.page-title');

				$pq->assign('title',' - Login', true);

				$pq->assign('.form > .error', $status['error']);

				return; //echo $pq->html(true);
			}
		}
	});
	$router->post('/forgot', function () use ($pq, $db)
	{
		//
	});
	$router->post('/username', function () use ($db)
	{
		if(isset($_POST['user_name'])){
			$member = new Member($db);
			echo $member->check_username($_POST['user_name']);
		}
	});
	$router->post('/email', function () use ($db)
	{
		if(isset($_POST['e_mail'])){
			$member = new Member($db);
			echo $member->check_email($_POST['e_mail']);
		}
	});
});

$router->get('/pages/([A-Za-z0-9-]+)', function ($url) use ($pq, $db)
{
	$pq->load_str_html(loadTemplate('main'));
	$pq->remove('.manga-list');
	$pq->remove('.manga-search');
	$pq->remove('.manga-reader');
	$pq->remove('.manga-detail');
	$pq->save();

	//set active nav-link
	//remove active status from current state
	$pq->remove_class('.navbar > ul > li', 'active');
	//change navbar active state based on index
	if ($url==='contact') {
		$pq->add_class('.navbar > ul > li', 'active', 2);
	}elseif ($url==='about') {
		$pq->add_class('.navbar > ul > li', 'active', 3);
	}

	$pq->remove('.page-title');

	$pq->assign('.manga-home', $pq->pnode('h3')->assign('h3', ucfirst($url).' Page'));
	$pq->assign('.manga-home', $pq->pnode('article')->assign('article', 'Opening page : '.$url), true);
});

function hantu ($el, $attr, $with)
{
	$fn = func_get_args();
	isset($fn[0]) ? ($idx = (is_int($fn[0])) ? $fn[0] : 0) : $idx = 0 ;
	isset($fn[1]) ? ($child = (is_bool($fn[1])) ? $fn[1] : false) : $child = false ;
	$fn = ['index'=>$idx, 'child'=>$child];
	$fn[]=[$el,$attr,$with];
	return $fn;
}
//END

$router->get('/api/manga/([1-9]\d*)', function ($mid) use ($db)
{
	$api = new API($db);
	//make sure return as json and allow access
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//print it
	echo $api->getManga($mid);
	exit();
});
$router->get('/api/manga/([1-9]\d*)/chapter/([1-9]\d*)', function ($mid, $cid) use ($db)
{
	$api = new API($db);
	//make sure return as json and allow access
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//print it
	echo $api->getChapter($mid, $cid);
	exit();
});
$router->get('/api/member/([1-9]\d*)', function ($uid) use ($db)
{
	$api = new API($db);
	$member = new Member($db);
	//make sure return as json and allow access
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo json_encode($member->get_user($uid));
	exit();
});

$router->get('/pages', function ()
{
	/*
	$widgets = [
	"navigation" => ["type" => "link"],
	"pages" => ["type" => "html"],
	"sidebar" => ["type" => "html"]
	];
	$widgets = json_encode($widgets);
	*/
	$widgets = file_get_contents('widgets/widgets.json');
	$widgets = json_decode($widgets, TRUE);
	echo "<pre>";
	print_r($widgets);
	echo "</pre>";

	exit();
});
$router->get('/image', function ()
{
	if(!isset($_GET['url']) || empty($_GET['url']))
	{
		header('Content-type: image/jpeg');
		imagejpeg(make_wrapped_txt("URL is empty"));
		return;
	} else {
		$url = str_replace('https', 'http', urldecode($_GET['url']));
		$url = str_replace('http://'.$_SERVER['HTTP_HOST'].'/', '', $url);
	}

	if(!isset($_GET['qc']) || empty(['qc'])) $quality=75;
	else $quality = $_GET['qc'];

	$context = stream_context_create(array(
    	'http' => array('ignore_errors' => true),
	));
	//file_get_contents ( string $filename , bool $use_include_path = false , resource $context = ? , int $offset = 0 , int $maxlen = ? ) : string|false
	if (!@file_get_contents($url)){
		header('Content-type: image/jpeg');
		imagejpeg(make_wrapped_txt("Images Failed to Load"));
		return;
	}

	$info = new stdClass;
	foreach (getimagesize($url) as $key=>$val)
	{
		if($key=='0') $info->width = $val;
		if($key=='1') $info->height = $val;
		if($key=='2') $info->ch = $val;
		if($key=='3') $info->wh = $val;
		if($key=='bits') $info->bits = $val;
		if($key=='channels') $info->channels = $val;
		if($key=='mime') $info->mime = $val;
	}

	$width=$info->width;
	$height=$info->height;
	$resize=false;
	if($width > 1450){ $width=$width/4; $height=$height/4; $resize=true; }
	elseif($width > 1200 && $width < 1450){ $width=$width/2; $height=$height/2; $resize=true;}

	/* */
	header('Content-Type: '.$info->mime);

	if ($info->mime == 'image/jpeg')
	{
		$image = imagecreatefromjpeg($url);
		if($resize===true){
			$tmp = imagecreatetruecolor($width, $height);
		    imagecopyresampled($tmp, $image, 0, 0, 0, 0, $width, $height, $info->width, $info->height);

			imagejpeg($tmp, null, $quality);
			imagedestroy($tmp);
			imagedestroy($image);
		}else{
			imagejpeg($image, null, $quality);
			imagedestroy($image);
		}
	} elseif ($info->mime == 'image/gif') {
		/*
		$image = imagecreatefromgif($url);
		if($resize===true){
		$tmp = imagecreatetruecolor($width, $height);
	    imagecopyresampled($tmp, $image, 0, 0, 0, 0, $width, $height, $info->width, $info->height);

		imagegif($tmp, null, $quality);
		imagedestroy($tmp);
		imagedestroy($image);
		}else{
		imagegif($image, null, $quality);
		imagedestroy($image);
		}
		*/
		echo file_get_contents($url);
	} elseif ($info->mime == 'image/png') {
		$image = imagecreatefrompng($url);
		if($resize===true){
			$tmp = imagecreatetruecolor($width, $height);
		    imagecopyresampled($tmp, $image, 0, 0, 0, 0, $width, $height, $info->width, $info->height);

		    imagealphablending($tmp, false);
			imagesavealpha($tmp, true);
			imagepng($tmp, null, ($quality/10));
			imagedestroy($tmp);
			imagedestroy($image);
		}else{
			imagealphablending($image, false);
			imagesavealpha($image, true);
			imagepng($image, null, ($quality/10));
			imagedestroy($image);
		}
	} else {
		echo file_get_contents($url);
	}

	exit();
});

$router->get('/secureimage_show', function ()
{
	include_once 'libs/securimage/securimage_show.php';

	exit();
});

// Run the web!
//$router->run();
$router->run(function() use ($pq) {
    $pq->print_html();
});

?>

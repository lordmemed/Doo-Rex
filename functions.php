<?php

function is_connected()
{
    $connected = @fsockopen("www.example.com", 80); //website, port  (try 80 or 443)
    if ($connected){
		fclose($connected);
		return true; //action when connected
    }else{
		return false; //action in connection failure
	}

}

function is_letter($str)
{
	if (!is_numeric($str[0])
		 && $str[0]!=='\''
		 && $str[0]!=='"'
		 && $str[0]!==';'
		 && $str[0]!==':'
		 && $str[0]!=='/'
		 && $str[0]!=='?'
		 && $str[0]!=='.'
		 && $str[0]!=='>'
		 && $str[0]!==','
		 && $str[0]!=='<'
		 && $str[0]!=='\\'
		 && $str[0]!=='['
		 && $str[0]!=='{'
		 && $str[0]!==']'
		 && $str[0]!=='}'
		 && $str[0]!=='`'
		 && $str[0]!=='~'
		 && $str[0]!=='!'
		 && $str[0]!=='@'
		 && $str[0]!=='#'
		 && $str[0]!=='$'
		 && $str[0]!=='%'
		 && $str[0]!=='^'
		 && $str[0]!=='&'
		 && $str[0]!=='*'
		 && $str[0]!=='('
		 && $str[0]!==')'
		 && $str[0]!=='-'
		 && $str[0]!=='_'
		 && $str[0]!=='='
		 && $str[0]!=='+'
		) 
	{
		return true;
	}
	return false;
}

function dump_arr(array $var_arr, $ecpr=null){
	$data=null;
	foreach ($var_arr as $key => $value) {
		if ($key=="argv") {$data.=dump_arr($value);}

		$data.=$ecpr.$key.$ecpr." => ".$ecpr.$value.$ecpr.',';
		$data.="\r\n<br>";
	}
	return $data;
}

function replace_all_var_config(string $tpl)
{
	$html = trim($tpl);
	$html = str_replace('<%app_title%>', get_app_title(), $html);
	$html = str_replace('<%app_slogan%>', get_app_slogan(), $html);
	$html = str_replace('<%app_logo%>', get_app_logo(), $html);
	$html = str_replace('<%app_icon%>', get_app_icon(), $html);
	$html = str_replace('<%app_asset%>', get_app_asset(), $html);
	$html = str_replace('<%app_home%>', get_app_url(), $html);
	$html = str_replace('<%theme_asset%>', get_theme_asset(), $html);

	return $html;
}

function set_page_var(string $tpl, array $vars)
{
	$html = $tpl;
	foreach ($vars as $var => $val) {
		$html = str_replace("<%$var%>", $val, $html);
	}

	//$html = remove_unused_var($html);

	return $html;
}

function remove_unused_var(string $tpl)
{
	$html = $tpl;
	$vars=explode("\r\n", file_get_contents('list_variable.inc'));
	//var_dump($vars);
	foreach ($vars as $var) {
		$html = str_replace("<%$var%>", "", $html);
	}

	return $html;
}

function loadTemplate($tpl_name)
{
	//load and setup template layout
	$tpl = file_get_contents(get_theme_asset().'/'.$tpl_name.'.html'); //load the layout
	$tpl = replace_all_var_config($tpl); //replace all app variable <%var_key%> with value from config
	$tpl = set_page_var($tpl, [ /* 'page_title' => $_SERVER['REQUEST_URI'], */ 'time' => $_SERVER['REQUEST_TIME']]); //replace all variable with key=>var
	$tpl = remove_unused_var($tpl);

	return $tpl;
	//unset and clear loaded layout
	//unset($tpl);
}

function get_app_url(string $request_scheme=''): String
{
	if (empty($request_scheme)) { $request_scheme = $_SERVER['REQUEST_SCHEME']; }
	
	if (!empty(APP_BASE)) {
		return $request_scheme.APP_HOST.'/'.APP_BASE;
	}else{
		return $request_scheme.APP_HOST;
	}
}

function get_app_title(): String
{
	return APP_TITLE;
}

function get_app_slogan(): String
{
	return APP_SLOGAN;
}

function get_app_logo(): String
{
	return get_app_asset().'/'.APP_LOGO;
}

function get_app_icon(): String
{
	return get_app_asset().'/'.APP_ICON;
}

function get_app_asset(): String
{
	return get_app_url().'/'.APP_ASSET;
}

function get_theme_asset($w_url=true): String
{
	if ($w_url!==false) return get_app_url().'/themes/'.APP_THEMES;
	//return '/themes/'.APP_THEMES;
}

function get_manga_location(): String
{
	return get_app_asset().'/content';
}

//others
function compress_image($source_url, $destination_url, $quality)
{
	$info = getimagesize($source_url);
	if ($info['mime'] == 'image/jpeg') {
		$image = imagecreatefromjpeg($source_url); 
		imagejpeg($image, $destination_url, $quality);
	} elseif ($info['mime'] == 'image/gif') {
		$image = imagecreatefromgif($source_url); 
		imagegif($image, $destination_url, $quality);
	} elseif ($info['mime'] == 'image/png') {
		$image = imagecreatefrompng($source_url); 
		imagepng($image, $destination_url, $quality);
	} else {
		return; //'Image type not supported';
	}
}

//image text
function whitespaces_imagestring($image, $font, $x, $y, $string, $color) {
    $font_height = imagefontheight($font);
    $font_width = imagefontwidth($font);
    $image_height = imagesy($image);
    $image_width = imagesx($image);
    $max_characters = (int) ($image_width - $x) / $font_width ;
    $next_offset_y = $y;

    for($i = 0, $exploded_string = explode("\n", $string), $i_count = count($exploded_string); $i < $i_count; $i++) {
        $exploded_wrapped_string = explode("\n", wordwrap(str_replace("\t", "    ", $exploded_string[$i]), $max_characters, "\n"));
        $j_count = count($exploded_wrapped_string);
        for($j = 0; $j < $j_count; $j++) {
            imagestring($image, $font, $x, $next_offset_y, $exploded_wrapped_string[$j], $color);
            $next_offset_y += $font_height;

            if($next_offset_y >= $image_height - $y) {
                return;
            }
        }
    }
}
/* function for wrapping text to fit width
* (auto-adjusts height as needed) 
* since it doesn't only do 1 word per line.
*/
function make_wrapped_txt($txt, $color=000000, $space=4, $font=4, $w=300) {
  if (strlen($color) != 6) $color = 000000;
  $int = hexdec($color);
  $h = imagefontheight($font);
  $fw = imagefontwidth($font);
  $txt = explode("\r\n", wordwrap($txt, ($w / $fw), "\r\n"));
  $lines = count($txt);
  $im = imagecreate($w, (($h * $lines) + ($lines * $space)));
  $bg = imagecolorallocate($im, 255, 255, 255);
  $color = imagecolorallocate($im, 0xFF & ($int >> 0x10), 0xFF & ($int >> 0x8), 0xFF & $int);
  $y = 0;
  foreach ($txt as $text) {
    $x = (($w - ($fw * strlen($text))) / 2);
    whitespaces_imagestring($im, $font, $x, $y, $text, $color);
    $y += ($h + $space);
  }
  //header('Content-type: image/jpeg');
  //die(imagejpeg($im));
  return $im;
}

function dayToID($date) : String //return day in week e.g. Sunday => Minggu
{
	$array_hari = array(1=>"Senin","Selasa","Rabu","Kamis","Jum'at", "Sabtu","Minggu");
	return $array_hari[date('N', strtotime($date))];
}

function monthToID($date) : String //return month in year e.g. January => Januari
{
	$array_bulan = array(1=>"Januari","Februari","Maret", "April", "Mei", "Juni","Juli","Agustus","September","Oktober", "November","Desember");
	return $array_bulan[date('n', strtotime($date))];
}

?>
<?php

namespace MangaReader;

/**
 * Widget Class
 */
class Widget
{
	private $wn, $wj;
	private $error;

	function __construct(string $widget_name)
	{
		$f = file_get_contents('widgets/widgets.json');
		$fj = json_decode($f, true);
		if (isset($fj[$widget_name])) {
			$this->wn = $fj[$widget_name]['name'];
			$w = file_get_contents('widgets/'.$fj[$widget_name]['name'].'/'.$widget_name.'.wt');
			$this->wj = json_decode($w, true);
		} else {
			$this->error = 'Widget not found';
		}
	}

	function WidgetContent()
	{
		return htmlentities(file_get_contents('widgets/'.$this->wn.'/'.$this->wj['data'][2]['content']));
	}
}

$ww = new Widget('sidebar');
print_r($ww->WidgetContent());

/*
$widget_name = 'sidebar';
$f = file_get_contents('widgets/widgets.json');
$fj = json_decode($f, true);
if (isset($fj[$widget_name])) {
	$w = file_get_contents('widgets/'.$widget_name.'/'.$widget_name.'.wt');
	$wj = json_decode($w, true);
} else {
	$error = 'Widget not found';
}
echo file_get_contents('widgets/'.$widget_name.'/'.$wj['data'][0]['content']);
//file_put_contents(filename, data)

*/
<?php

namespace MangaReader;

use HTMLDOM\html_dom;

/**
 * Presentation Class
 */
class Presenter
{
	private $r;
	private $_html;
	
	function __construct()
	{
		$this->r = new html_dom;
	}

	function __toString()
	{
		return $this->html(true);
	}
	
	function load_str_html(string $str) 
	{
		$this->_html = $this->r->str_get_html($str);

		return $this;
	}
	
	function save() 
	{
		$this->load_str_html($this->html(true));

		return $this;
	}
	
	function html($clean=false) 
	{
		$r = $this->_html->save();
		
		if ($clean!==false) { $this->cleanup(); }
		
		return $r;
	}

	function text($el, $idx=0)
	{
		return $this->_html->find($el, $idx)->innertext();
	}
	
	function cleanup() 
	{
		$this->_html->clear(); 
		unset($this->_html);
	}

	function print_html() 
	{
		echo $this->html(true);
	}
	
	function clone($el, $idx=0) {
		$str = $this->_html->find($el, $idx);
		$new_clone = new self;
		$new_clone->load_str_html($str);
		
		return $new_clone;
	}
	function get_element($el, $idx=0) {
		return $this->_html->find($el,$idx);
	}
	//remove element
	//e.g ;
	//->remove('element_name') = delete all found element with the name
	//->remove('element_name', index) = delete element with selected index number if the element is more than one
	//->remove('element_name', true/false) = if true delete only the child element, else delete element
	//can be combined ->remove('element_name', index, true/false)
	//default is ->remove('element_name') = ->remove('element_name', 0, false)
	function remove($el) {
		$fn = func_get_args();
		if (isset($fn[2])) {
			if (is_bool($fn[2])) {
				$child = $fn[2];
			} else {
				$child = false;
			}
		} 
		if (isset($fn[1])) {
			if (is_int($fn[1])) {
				$idx = $fn[1];
			} else {
				$idx = 0;
			}
			if (is_bool($fn[1])) {
				$child = $fn[1];
			} else {
				$child = false;
			}
		} else {
			$idx = 0;
			$child = false;
		}

		if ($child!==true) {
			$this->_html->find($el, $idx)->outertext = '';
		} else {
			$this->_html->find($el, $idx)->innertext = '';
		}

		return $this;
	}

	//prepend an element
	//if ->prepend('element_name', content, index)
	//then it will prepend the content of the selected index
	function prepend($el, $with) {
		$fn = func_get_args();
		if (isset($fn[2])) {
			if (is_numeric($fn[2])) {
				$idx = $fn[2];
			} else {
				$idx = 0;
			}
		} else {
			$idx = 0;
		}

		$this->_html->find($el, $idx)->innertext = $with.$this->_html->find($el, $idx)->innertext;

		return $this;
	}
	//replace an element content
	//->assign('element_name', content) = will replace the content of selected element
	//if ->assign('element_name', content, index)
	//then it will replace only the selected index
	//elseif ->assign('element_name', content, true)
	//then append the element content
	//elseif ->assign('element_name', content, false)
	//then append the element content
	//
	//can be combined ->assign('element_name', content, index, true/false)
	function assign($el, $with) {
		$fn = func_get_args();
		if (isset($fn[3])) {
			if (is_bool($fn[3])) {
				$append = $fn[3];
			} else {
				$append = false;
			}
		} 
		if (isset($fn[2])) {
			if (is_numeric($fn[2])) {
				$idx = $fn[2];
			} else {
				$idx = 0;
			}
			if (is_bool($fn[2])) {
				$append = $fn[2];
			} else {
				$append = false;
			}
		} else {
			$idx = 0;
			$append = false;
		}
		
		if ($append!==true) {
			$this->_html->find($el, $idx)->innertext = $with;
		} else {
			$this->_html->find($el, $idx)->innertext .= $with;
		}

		return $this;
	}
	//replace the content in all of element
	function assign_all($el, $in_el, $with) {
		foreach ($this->_html->find($in_el) as $xel) {
			foreach ($xel->find($el) as $cel) {
				//$this->assign($el, $with);
				$cel->innertext = $with;
			}
		}

		return $this;
	}
	//assign attribute to element
	function assign_attr($el, $attr, $with) {
		$fn = func_get_args();
		if (isset($fn[4])) {
			if (is_bool($fn[4])) {
				$append = $fn[4];
			} else {
				$append = false;
			}
		} 
		if (isset($fn[3])) {
			if (is_numeric($fn[3])) {
				$idx = $fn[3];
			} else {
				$idx = 0;
			}
			if (is_bool($fn[3])) {
				$append = $fn[3];
			} else {
				$append = false;
			}
		} else {
			$idx = 0;
			$append = false;
		}

		if ($append!==true) {
			$this->_html->find($el, $idx)->{$attr} = $with;
		} else {
			$this->_html->find($el, $idx)->{$attr} .= $with;
		}

		return $this;
	}
	function get_attr($el, $attr) {
		$fn = func_get_args();
		if (isset($fn[2])) {
			if (is_numeric($fn[2])) {
				$idx = $fn[2];
			} else {
				$idx = 0;
			}
		}

		return $this->_html->find($el, $idx)->{$attr};
	}

	
	function add_class($el, $class, $idx=0) {
		//add new class
		$this->_html->find($el, $idx)->class .= ' '.$class;
		//trim the class string
		//tell me if there is better way
		$this->_html->find($el, $idx)->class = trim($this->_html->find($el, $idx)->class);

		return $this;
	}
	function remove_class($el, $class, $idx=0) {
		//remove class
		$class = trim(str_replace($class,'',$this->_html->find($el, $idx)->class));
		//cleaning double space '  '
		$class = trim(str_replace('  ',' ',$class));
		
		$this->_html->find($el, $idx)->class = $class;

		return $this;
	}
	
	function repeat_assign($el, $in_el, $with) {
		foreach ($this->_html->find($in_el) as $xel) {
			foreach ($xel->find($el) as $cel) {
				//$this->assign($el, $with);
				$cel->innertext = $with;
			}
		}

		return $this;
	}
	
	function repeat_add_or_remove_class(string $action, string $el, string $class) {
		foreach ($this->_html->find($el) as $idx=>$cel) {
			if($action==='add') $this->add_class($el,$class, $idx);
			if($action==='remove') $this->remove_class($el,$class, $idx);
		}

		return $this;
	}
	
	private function replace() {
		//

		return $this;
	}

	//create new element inside dom
	//->pnode('element_name') = will only create the selected element/tags
	//->pnode('element_name', content) = will only create the selected element/tags that support content (not single line closed tags) e.g div,a,p, etc.
	//->pnode('element_name', attribute in array) = will create the selected element/tags with attribute;
	//e.g, ->pnode('a', array('href'=>'#', 'title'=>'alt_title', 'style'=>'css_style'))
	//can be combined ->pnode('element_name', content, array('attribute'=>'value'))
	function pnode($el_name) {
		$fn=func_get_args();
		if (isset($fn[2])) {
			if (is_array($fn[2])) {
				$attrs = $fn[2];
			} else {
				$attrs = false;
			}
		} 
		if (isset($fn[1])) {
			if (is_string($fn[1])) {
				$content = $fn[1];
			} else {
				$content = '';
			}
			if (is_array($fn[1])) {
				$attrs = $fn[1];
			} else {
				$attrs = false;
			}
		} else {
			$content = '';
			$attrs = false;
		}

		$attr=null; $el=null;

		if ($attrs!==false) {
			foreach ($attrs as $key => $val) {
				if (empty($val)) { $attr .= " ".$key; }
				else { $attr .= " $key='$val'"; }
			}
		}

		switch ($el_name) {
			case 'area':
				$el = "<$el_name$attr />";
				break;
			case 'base':
				$el = "<$el_name$attr />";
				break;
			case 'br':
				$el = "<$el_name$attr />";
				break;
			case 'col':
				$el = "<$el_name$attr />";
				break;
			case 'embed':
				$el = "<$el_name$attr />";
				break;
			case 'hr':
				$el = "<$el_name$attr />";
				break;
			case 'img':
				$el = "<$el_name$attr />";
				break;
			case 'input':
				$el = "<$el_name$attr />";
				break;
			case 'link':
				$el = "<$el_name$attr />";
				break;
			case 'meta':
				$el = "<$el_name$attr />";
				break;
			case 'param':
				$el = "<$el_name$attr />";
				break;
			case 'source':
				$el = "<$el_name$attr />";
				break;
			case 'track':
				$el = "<$el_name$attr />";
				break;
			case 'wbr':
				$el = "<$el_name$attr />";
				break;
			case 'command':
				$el = "<$el_name$attr />"; //obsolete
				break;
			case 'keygen':
				$el = "<$el_name$attr />"; //obsolete
				break;
			case 'menuitem':
				$el = "<$el_name$attr />"; //obsolete
				break;
			default:
				$el = "<$el_name$attr>$content</$el_name>";
				break;
		}

		$ret = new self;
		$ret->load_str_html($el);
		$ret->save();

		return $ret;
	}

	function tnod()
	{
		return new PNode();
	}
}

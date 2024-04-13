<?php
namespace App;
use DiDom\Document;

class Scrapper
{
	protected $url = '';
	protected $first_url = '';
	protected $folder = '';
	// protected $parent = null;
	// protected $next_page_selector = '';
	protected $selector = [];	
	protected $cards = [];	
	protected $func = [];	
	protected $next_page = -1;
	protected $download_dir = '';
	protected $page_num = 1;
	protected $pdf_done = null;
	protected $img_done = null;
	protected $icon_msg = null;

	#-------------------------------------------------------------------------------------------#
	function __construct($url, $path, $folder='') {
		$this->first_url = urldecode($url);
		$this->url = urldecode($url);
		$this->folder = $folder;
		$url_parts = parse_url($url);
		$this->download_dir = $path.'/downloads/' . $url_parts['host'];
		$this->icon_msg = new Helper\IconMsg();

		Helper\create_folder($this->download_dir);
		if ($folder) {
			$this->download_dir = $this->download_dir .'/'. $folder;
			Helper\create_folder($this->download_dir);
		}
	}
	#-------------------------------------------------------------------------------------------#
	function set_selector($key, $value, $func='') {
		$this->selector[$key] = $value;
		if ($func) $this->func[$key] = $func;
	}
	#-------------------------------------------------------------------------------------------#
	function get_selector($key) {
		$result = isset($this->selector[$key]) ? $this->selector[$key] : null;
		if ($result) {
			return $result;
		} 
		else {
			throw new Exception('Selector "'. $key .'" is not exist!');
		}
		return null;
	}
	#-------------------------------------------------------------------------------------------#
	private function get_func($key) {
		$result = isset($this->func[$key]) ? $this->func[$key] : 'first';
		if ($result) {
			return $result;
		} 
		// else {
		// 	// throw new Exception('Selector function "'. $key .'" is not exist!');
		// }
		return null;
	}
	#-------------------------------------------------------------------------------------------#
	function get_node($parent, $func, $key) {
		if (method_exists($parent, $func)) {
			return $parent->$func($key);
		}
		throw new Exception("Class '{$parent}' dosn't content method '{$func}'!");
	}
	#-------------------------------------------------------------------------------------------#
	function get_element($url, $selector_key, $error_msg=Null) {
		$document = new Document($url, true);
		$selector = $this->get_selector($selector_key);
		$func = $this->get_func($selector_key);
		if ($document->has($selector)){
			return $document->$func($selector);
		}
		if (!$error_msg) { $error_msg = "Couldn't find selector '{$selector_key}->{$selector}' in '{$url}'"; }
		throw new Exception($error_msg);
	}
	#-------------------------------------------------------------------------------------------#
	function get_elements($url, $selector, $error_msg=Null) {
		$document = new Document($url, true);
		if ($document->has($selector)){
			return $document->find($selector);
		}
		if (!$error_msg) { $error_msg = "Couldn't find selector '{$selector}' in '{$url}'"; }
		throw new Exception($error_msg);
	}
	#-------------------------------------------------------------------------------------------#
	function get_download_path($title, $ext) {
		$path = trim($title) .'.'.$ext;
		if ($this->download_dir) {
			$path = $this->download_dir .'/'.$title.'.'.$ext;
		}
		return $path;
	}
	#-------------------------------------------------------------------------------------------#
	function get_next_page_url($document, $selector) {
		$next_page = $document->has($selector);

		if($next_page) {
			return $document->first($selector);
		}
		return null;
	}
	#-------------------------------------------------------------------------------------------#
	function download_pdf($link, $save_to) {		
		if ($link) {
			$content = file_get_contents($link);
			if ($content){
				return file_put_contents($save_to, $content);
			}
			throw new Exception("Couldn't download pdf file '{$link}'");
		}
		// throw new Exception("Couldn't find pdf link '{$link}'");
	}
	#-------------------------------------------------------------------------------------------#
	function download_thumb($link, $save_to) {
		if ($link) {
			$content = file_get_contents($link);
			if( $content ) {
				return file_put_contents($save_to, $content);
			}
			throw new Exception("Couldn't download thumb file '{$link}'");
		}
	}
	#-------------------------------------------------------------------------------------------#
	function set_current_page($page_num, $page_link) {
		file_put_contents($this->download_dir . '/current_page.txt', $page_num .'->'. $page_link);
	}
	#-------------------------------------------------------------------------------------------#
	function get_current_page() {
		if (is_file($this->download_dir .'/current_page.txt')) {
			$current = file_get_contents($this->download_dir .'/current_page.txt');
			if($current){
				$contents = explode('->', $current);
				$page_num = $contents[0];
				$current_page_url = $contents[1];
				
				return ['number'=>$page_num, 'url'=>$current_page_url];
			}
		}
		return false;
	}
	#-------------------------------------------------------------------------------------------#
	// function set_next_page($regix) {
	// 	$this->next_page_selector = $regix;
	// }	
	#-------------------------------------------------------------------------------------------#
	function get_host($suffix='') {
		$url_parts = parse_url($this->first_url);
		return $url_parts['scheme'].'://'.$url_parts['host'].$suffix;
	}
	#-------------------------------------------------------------------------------------------#
}
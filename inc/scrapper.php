<?php
namespace App;
use DiDom\Document;

// TODO:
// use direct file link to check files doublicate


class Scrapper
{
	protected $name = 'PDF SCRAPPER';
	protected $url = '';
	protected $first_url = '';
	protected $document = null;
	protected $page_title = '';
	protected $folder = '';
	protected $local_file = '';
	protected $download_type = '.pdf';
	protected $download_file_types = '';
	// protected $next_page_selector = '';
	protected $selector = [];
	protected $cards = [];	
	protected $func = [];	
	protected $host = [];	
	protected $next_page = -1;
	protected $download_dir = '';
	protected $page_num = 1;
	protected $pdf_done = null;
	protected $img_done = null;
	protected $icon_msg = null;

	#-------------------------------------------------------------------------------------------#
	function __construct($url, $folder='') {
		$url = trim($url, ' ');
		$this->first_url = urldecode($url);
		$this->url = urldecode($url);
		$this->document = new Document($url, true);
		$this->folder = trim($folder, ' ');
		$this->page_title = $this->folder;
		$url_parts = parse_url($url);
		$this->download_dir = DOWNLOADS_DIR . DS. $url_parts['host']. DS. ($folder ? $folder.DS : '');
		$this->icon_msg = new Helper\IconMsg();

		$mkdir = Helper\create_folder($this->download_dir);
		// Helper\pre($this->download_dir);
		// Helper\pre('construct', $mkdir);
	}
	#-------------------------------------------------------------------------------------------#
	function init($title_type='selector', $custom_title='', $func='first') {
		
		switch($title_type) {
			case 'selector':
				$this->set_download_dir_from_selector($this->document, 'page-title', $func);
			break;
			case 'html_title':
				$this->page_title = $this->init_page_title($this->document, $from_page_header); 
			break;
			case 'custom':
				$this->page_title = $custom_title;
			break;
		}
	}
	#-------------------------------------------------------------------------------------------#
	function auto_scroll() {
		echo "<script>auto_scroll();</script>";
	}
	#-------------------------------------------------------------------------------------------#
	function header($name='') {
		
		$name = empty($name) ? $this->name : $name;

		echo "<h1 class='hero'>{$name}</h1>";
		echo "
		<script>
			function auto_scroll(){
				window.scrollTo(0, document.body.scrollHeight);
			}
		</script>
		";
	}
	#-------------------------------------------------------------------------------------------#
	function init_page_title($document, $from_page_header=false) {
		if ($from_page_header) {
			$page_title = $document->first('head')->firstInDocument('title');
		} 
		else {
			$page_title = $this->get_selector_node($document, 'page-title');
		}
		if ($page_title && $from_page_header) {
			$this->page_title = $page_title;
		}
		else if ($page_title) {
			$this->page_title = $page_title->text();
		}
		return ['result'=>'false', 'msg'=> "Couldn't find page-title", 'code'=>1];
	}	
	#-------------------------------------------------------------------------------------------#
	function set_local_file_path($path) {
		$this->local_file = $path;
	}
	#-------------------------------------------------------------------------------------------#
	function set_download_file_types($types='pdf|doc|docx|ppt|txt') {
		$this->download_file_types = "/\.($types)/is";
	}
	#-------------------------------------------------------------------------------------------#
	function get_download_path($title, $ext) {
		$path = trim($title, ' ') .'.'.$ext;
		if ($this->download_dir) {
			$path = $this->download_dir .DS.trim($title, ' ').'.'.trim($ext, ' ');
		}
		return $path;
	}
	#-------------------------------------------------------------------------------------------#
	// function set_auto_download_dir($selector_value) {
	// 	$document = new Document($this->first_url, true);
	// 	$result = $document->first($selector_value);
	// 	if ($result) {
	// 		// $folder = $this->folder ? $this->folder.DS :'';
	// 		// $this->download_dir =  DOWNLOADS_DIR .DS. $folder. $result->text();
	// 		$this->download_dir =  $this->download_dir .DS. $result->text();
			
	// 		// 
	// 		try {
	// 			// mkdir($this->download_dir, 0777, true);
	// 			$r = Helper\create_folder($this->download_dir);
	// 			Helper\pre($r);
		
	// 		}
	// 		catch(Exception $e) {
	// 			Helper\pre($e);
	// 		}
	// 	}
	// }
	#-------------------------------------------------------------------------------------------#
	function set_download_dir_from_selector($node, $selector_key, $func='first') {
		$page_title = $this->get_selector('page-title');

		if (! $this->get_selector($selector_key)) {
			return false;
		}
		
		$result = $this->get_selector_node($node, $selector_key);
		if ($result) {
			$this->page_title = trim($result->text(), ' ');
			$this->download_dir = $this->download_dir . $this->page_title;
			
			$mkdir = Helper\create_folder($this->download_dir);
			// Helper\pre($this->download_dir);
			// Helper\pre(__METHOD__, $mkdir);
			if ($mkdir['result'] == 'success') {
				return true;
			}
		}
		return false;
	}
	#-------------------------------------------------------------------------------------------#
	function set_selector($key, $value, $func='', $host_suffix='') {
		$this->selector[$key] = $value;
		if ($func) $this->func[$key] = $func;
		if ($host_suffix) {
			$suffix = ($host_suffix === true) ? '' : $host_suffix;
			$this->host[$key] = $this->get_host($suffix);
		}
	}
	#-------------------------------------------------------------------------------------------#
	// function set_selectors($key, $array, $func='', $host_suffix='') {
	// 	$this->selector[$key] = $array;
	// 	if ($func) $this->func[$key] = $func;
	// 	if ($host_suffix) {
	// 		$suffix = ($host_suffix === true) ? '' : $host_suffix;
	// 		$this->host[$key] = $this->get_host($suffix);
	// 	}
	// }
	// #-------------------------------------------------------------------------------------------#
	// function set_selector_regex($key, $regex, $func='', $host_suffix='') {
	// 	$this->selector[$key] = $regex;
	// 	if ($func) $this->func[$key] = $func;
	// 	if ($host_suffix) $this->host[$key] = $this->get_host($host_suffix);
	// }
	#-------------------------------------------------------------------------------------------#
	function get_selector($key) {
		$result = isset($this->selector[$key]) ? $this->selector[$key] : null;
		if ($result) {
			return $result;
		} 
		// else {
		// 	throw new Exception('Selector "'. $key .'" is not exist!');
		// }
		return null;
	}
	#-------------------------------------------------------------------------------------------#
	private function get_func($key) {
		return isset($this->func[$key]) ? $this->func[$key] : 'first';
	}
	#-------------------------------------------------------------------------------------------#
	private function get_selector_host($key) {
		return isset($this->host[$key]) ? $this->host[$key] : '';
	}
	#-------------------------------------------------------------------------------------------#
	function get_selector_node($node, $selector_key, $func='first') {
		$result = null;
		$selector = $this->get_selector($selector_key);
		if ($selector && $node->has($selector)) {
			$result = $node->$func($selector);
		}
		return $result;
	}
	#-------------------------------------------------------------------------------------------#
	// function get_node($node, $key, $func='first') {
	// 	$node_name = get_class($node);
	// 	if (method_exists($node, $func)) {
	// 		return $node->$func($key);
	// 	}
	// 	throw new Exception("Class '{$node_name}' dosn't content method '{$func}'!");
	// }
	#-------------------------------------------------------------------------------------------#
	// todo: rename method to: 
	// get_page_element() || get_element_from_url()
	function get_element($url, $selector_key, $error_msg=Null) {

		if (Helper\is_404($url)) {
			return ['error'=> 'Page not found!','code'=> 1];
		}
		$document = new Document($url, true);
		$selector = $this->get_selector($selector_key);
		// $func = find or first
		if ($selector) {
			if ($document->has($selector)){
				$func = $this->get_func($selector_key);
				$host = $this->get_selector_host($selector_key);
				return $host.$document->$func($selector);
			}
		}
		else {
			return ['error'=> 'Couldn\'t find selector "{$selector_key}->{$selector}" in "{$url}"', 'code'=> 2];
		}
	}
	#-------------------------------------------------------------------------------------------#
	function get_elements_ary($url, $selector_key) {
		if (! Helper\is_404($url)) {
			$document = new Document($url, true);
			$selectors = $this->get_selector($selector_key);
			// $func = find or first
			$res = '';
			if (is_array($selectors)) {
				foreach ($selectors as $selector) {
					if ($document->has($selector)){
						$func = $this->get_func($selector_key);
						$host = $this->get_selector_host($selector_key);
						// $res[] = $host.$document->$func($selector);
						$res = $host.$document->$func($selector);
						if ($res) {
							break;
						}
					}
				}
			}
		}
		return $res;
	}
	#-------------------------------------------------------------------------------------------#
	function get_elements($url, $selector) {
		if (! Helper\is_404($url)) {
			$document = new Document($url, true);
			if ($document->has($selector)){
				return $document->find($selector);
			}
		}
	}
	#-------------------------------------------------------------------------------------------#
	function get_next_page_url($document, $selector_key) {
		$selector = $this->get_selector($selector_key);
		if ($selector) {
			$next_page = $document->has($selector);
	
			if($next_page) {
				$func = $this->get_func($selector_key);
				$host = $this->get_selector_host($selector_key);
				// Note:
				// $document->$func($selector)
				// Must return one string result not array
				return $host.$document->$func($selector);
			}
			else {
				// return ['result'=> 'fail','msg'=> 'couldn\'t find next page!', 'code'=>1];
				return false;
			}
		}
		else {
			// return ['result'=> 'fail','msg'=> 'couldn\'t find next page!', 'code'=>1];
			return false;
		}
		return null;
	}

	#-------------------------------------------------------------------------------------------#
	function download_pdf($link, $save_to) {
		try{
			if ($this->download_file_types) {
				@preg_match($this->download_file_types, $link, $matches);
				// Helper\pre('matches', [$matches, $link, $this->download_file_types]); 
				if ( count($matches) > 1 ) {
					$content = @file_get_contents($link);
					if ($content){
						return @file_put_contents($save_to, $content);
					}
					// throw new Exception("Couldn't download pdf file '{$link}'");
				}
			}
			else {
				$content = @file_get_contents($link);
				if ($content){
					return @file_put_contents($save_to, $content);
				}
			}
		} catch (Exception $e) {
			return false;
		}
		// throw new Exception("Couldn't find pdf link '{$link}'");
	}
	#-------------------------------------------------------------------------------------------#
	function download_thumb($link, $save_to) {
		if ($link) {
			$content = @file_get_contents($link);
			if( $content ) {
				return @file_put_contents($save_to, $content);
			}
			// throw new Exception("Couldn't download thumb file '{$link}'");
		}
	}
	#-------------------------------------------------------------------------------------------#
	function set_current_page($page_num, $page_link) {
		file_put_contents($this->download_dir . DS.'current_page.txt', $page_num .'->'. $page_link);
	}
	#-------------------------------------------------------------------------------------------#
	function get_current_page() {
		if (is_file($this->download_dir . DS.'current_page.txt')) {
			$current = file_get_contents($this->download_dir . DS.'current_page.txt');
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
	// function set_next_page($regex) {
	// 	$this->next_page_selector = $regex;
	// }	
	#-------------------------------------------------------------------------------------------#
	function get_host($suffix='') {
		$url_parts = parse_url($this->first_url);
		return $url_parts['scheme'].'://'.$url_parts['host'].$suffix;
	}
	#-------------------------------------------------------------------------------------------#
	function stop(){
		echo ("<p class='proccess-finished txt-light bg-success'>Finished</p>");
		$this->auto_scroll();
	}
	#-------------------------------------------------------------------------------------------#
}
<?php
namespace App;
set_time_limit(0);
#ini_set('max_execution_time', 10); //300 seconds = 5 minutes
require_once('vendor/autoload.php');
require_once('inc/helper.php');
require_once('inc/scrapper.php');

use DiDom\Document;

class WebDownloader extends Scrapper
{

	#-------------------------------------------------------------------------------------------#
	function __construct($url, $folder='') {
		parent::__construct($url, $folder);
	}
	#-------------------------------------------------------------------------------------------#
	function start($url='') {

		$url = $url ? $url : $this->url;
		
		$current = $this->get_current_page();

		if($current){
			$page_num = $current['number'];
			$current_page_url = $current['url'];
			
			if ($current_page_url!='') {
				$url = $current_page_url;
			}
			if ($page_num) {
				$this->page_num = $page_num;
			}
		}
		
		// https://github.com/Imangazaliev/DiDOM
		## Alternatives:
		// https://www.php.net/manual/en/class.domdocument.php
		// https://simplehtmldom.sourceforge.io/docs/1.9/quick-start/

		
		$cards = $this->document->find($this->get_selector('posts'));
		$count = count($cards);
		

		echo '<div class="page-details">';
		echo "<h2 class='site-name'>{$this->get_host()} <span class='page-number'>Page ({$this->page_num})</span></h2>";
		echo "<h3 class='site-url'>{$this->url} <span class='icon-folder-open-empty download-folder'>{$this->page_title}</span></h3>";
		echo '<h4>Page items count: <span class="count-number">' . $count .'</span></h4>';	
		echo '</div>';

		flush();
		ob_flush();

		echo '<ol class="container">';
		echo "<li class='head txt-dark bg-info'>#<span>Title</span><span>Thumbnail</span><span>File</span></li>";
		
		$i = 1;
		foreach($cards as $card) {

			echo "<li class='row'>";
			try {
				$this->pdf_done = null;
				$this->img_done = null;

				$post_link = $card->first($this->get_selector('post-link'));
				$post_title = $card->first($this->get_selector('post-title'))->text();
				
				$post_link = $this->get_host() . $post_link;
				$post_title = Helper\slugify($post_title);
				$post_title = preg_replace('/^كتاب\s/' ,'', $post_title);

				// Helper\pre($post_title);
				// Helper\pre($post_link);
				// return;

				echo '<ul class="line">';
				if (! $post_link ){
					echo $this->icon_msg->msg("Couldn't find '{$post_title}' link", 'txt-danger')->icons('icon-cancel', 'icon-unlink');
					echo '</ul>';
					$i++;
					continue;
				}
		
				$save_to_pdf_path = $this->get_download_path($post_title, 'pdf');
				$save_to_img_path = $this->get_download_path($post_title, 'jpg');

				// Helper\pre($save_to_img_path, $save_to_pdf_path);
				// return;
				
				if (file_exists($save_to_pdf_path) && file_exists($save_to_img_path)) {

					echo $this->icon_msg->msg($post_title, 'txt-info', $post_link)->icons('icon-doc')->counter($i, $count);
					echo $this->icon_msg->msg('Already exist')->icons('icon-picture', 'icon-download');
					echo $this->icon_msg->msg('Already exist')->icons('icon-file-pdf', 'icon-download');
					echo '</ul>';
					$i++;
					continue;
				}
				else{
					echo $this->icon_msg->msg($post_title, 'txt-primary', $post_link)->icons('icon-download', 'icon-cog-alt')->counter($i, $count);
				}
				
				flush();
				ob_flush();

				#----- DOWNLOAD THUMBNAIL -----#
				if( ! file_exists($save_to_img_path) ) {
					
					try {

						// $thumb_link = $card->first($this->get_selector('post-thumb'));
						$thumb_link = $this->get_selector_node($card, 'post-thumb');

						$thumb_link = $this->get_host() . $thumb_link;
						// $thumb_link = $this->get_bg_url($thumb_link);

						$this->img_done = $this->download_thumb($thumb_link, $save_to_img_path);

						if ($this->img_done) {
							echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-ok');
						}
						else {
							echo $this->icon_msg->msg('Couldn\'t download', 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-cancel');
						}
					}
					catch (Exception $e) {
						echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
					}	
				}
				else {
					// Helper\icon_msg("Already exist!", 'txt-info', $post_link, 'icon-picture', 'icon-download');
					echo $this->icon_msg->msg('Already exist!', 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
				}
				
				flush();
				ob_flush();

				#----- DOWNLOAD PDF FILE -----#
				if( ! file_exists($save_to_pdf_path)) {
					
					
					try {
						$details_link = $this->get_host().$this->get_element($post_link, 'details-link');
						// Helper\pre($details_link); return;

						if (!$details_link) {
							echo $this->icon_msg->msg("Couldn't find details link!", 'txt-danger', $details_link)->icons('icon-file-pdf', 'icon-unlink');
							echo '</ul>';
							$i++;
							continue;
						}
						$direct_pdf_link = $this->get_host().$this->get_element($details_link, 'pdf-link');

						if (! $direct_pdf_link ){
							echo $this->icon_msg->msg("Couldn't find details link!", 'txt-danger', $details_link)->icons('icon-file-pdf', 'icon-unlink');
							echo '</ul>';
							$i++;
							continue;
						}
						
						$this->pdf_done = $this->download_pdf($direct_pdf_link, $save_to_pdf_path);
						
						if($this->pdf_done) {
							echo $this->icon_msg->msg("Downloaded", 'txt-success', $direct_pdf_link)->icons('icon-file-pdf', 'icon-ok');

						}
						else {
							echo $this->icon_msg->msg("Couldn't download!", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-cancel');
						}
					}
					catch (Exception $e) {
						echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $post_link)->icons('icon-file-pdf', 'icon-cancel');
						// echo '</ul>';
					}
				}
				else {
					echo $this->icon_msg->msg("Already exist!", 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
				}			

				flush();
				ob_flush();
				echo '</ul>';
				
			} catch (Exception $e) {
				echo $this->icon_msg->msg("Unknown error: {$post_title}", 'txt-danger', $post_link)->icons('icon-info-circled', 'icon-cancel');
				// if (!$this->img_done) ...
				// if (!$this->pdf_done) ...
				Helper\pre($e);
				$i++;
				echo '</ul>';
				continue;
			}	
				
			flush();
			ob_flush();		

			// if ($i>=2){
			// 	exit('====');
			// }
			echo '</ul>';
			$i++;
		}
		
		echo '</ol>';
		
		
		if( !$this->local_file && $this->get_selector('next-page') ){
			
			$this->document = new Document($url, true);
			
			$next_page_url = $this->get_next_page_url($this->document, 'next-page');

			// Helper\pre($next_page_url);
			// return;

			flush();
			ob_flush();	

			if ($next_page_url) {
				
				$this->page_num++;

				$this->set_current_page($this->page_num, $next_page_url);
				
				// if ($this->page_num > 3) {
				// 	$this->stop();
				// 	return;
				// }

				$this->start($next_page_url);
			}
		}

		$this->stop();
	}
	#-------------------------------------------------------------------------------------------#
	function get_bg_url($str) {
		preg_match('/url\((.*)\)\n/s', $str, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		}
		throw new Exception("Couldn't find image url");
	}
	#-------------------------------------------------------------------------------------------#
}
$base_url = Helper\base_url($_SERVER);
?>
<link rel="stylesheet" href="<?=$base_url?>/assets/style.css" />

<?php
require 'config.php';

$url = 'https://www.alarabimag.com/193/%D9%83%D8%AA%D8%A8-%D8%A5%D8%B3%D9%84%D8%A7%D9%85%D9%8A%D8%A9/';
$sub_folder = 'كتب إسلامية';

$s = new WebDownloader($url, $sub_folder);
// $s->set_selector('page-title', '#dcontent>.cats>h1');
$s->set_selector('posts', '.hotbooks');
$s->set_selector('post-title', 'h2>a');
$s->set_selector('post-link', 'a::attr(href)');
$s->set_selector('post-thumb', 'img::attr(src)', '/');
$s->set_selector('details-link', '#download>a::attr(href)');
$s->set_selector('pdf-link', '#download>a::attr(href)');
$s->set_selector('next-page', '.paginat a[title="الصفحة التالية"]::attr(href)', 'first', true);

$s->init();
$s->header(null, true);
$s->start();

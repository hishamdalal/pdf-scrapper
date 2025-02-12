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
		$url = trim($url, ' ');

		$current = $this->get_current_page();
		
		// $document = new Document('https://mktbtypdf.com/categories/%D8%A7%D9%84%D8%AF%D9%8A%D8%A7%D9%86%D8%A9-%D8%A7%D9%84%D8%A5%D8%B3%D9%84%D8%A7%D9%85%D9%8A%D8%A9/page/122/');
		// $next = $document->first($this->get_selector('next-page'));
		
		// $this->document = new Document($url, true);
		// $next_page_selector = $this->get_selector('next-page');
		// $next_page_url = $document->first($next_page_selector);

		// $next_page_url = $this->get_next_page_url($this->document, 'next-page');

		// Helper\pre('NEXT', [$url, $next_page_url]);
		// return;

		if(is_array($current)){
			
			// if ( true || str_ends_with($current['url'], 'Array') ) {
				
			// 	Helper\pre('current', $current);
			// 	return;
			// }

			$page_num = $current['number'];
			$current_page_url = $current['url'];
			
			if ($current_page_url!='') {
				$url = $current_page_url;
			}
			if ($page_num) {
				$this->page_num = $page_num;
			}
		}

		
		// $next_page_url = $this->get_next_page_url($this->document, 'next-page');
		// $next_page_number = $this->get_next_page_number($next_page_url, '/\/page\/(\d+)/i', 1);
		// 		helper\pre($next_page_number); return;
		
		// https://github.com/Imangazaliev/DiDOM
		## Alternatives:
		// https://www.php.net/manual/en/class.domdocument.php
		// https://simplehtmldom.sourceforge.io/docs/1.9/quick-start/

		$cards = $this->document->find($this->get_selector('posts'));
		$count = count($cards);
		
		$page_title = $this->folder ? $this->folder.' / '.$this->page_title : $this->page_title;

		echo '<div class="page-details">';
		echo "<h2 class='site-name'>{$this->get_host()} <span class='page-number'>Page ({$this->page_num})</span></h2>";
		echo "<h3 class='site-url'>{$this->url} <span class='icon-folder-open-empty download-folder'>{$page_title}</span></h3>";
		echo '<h4>Page items count: <span class="count-number">' . $count .'</span></h4>';	
		echo '</div>';

		ob_flush();
		flush();

		echo '<ol class="container">';
		echo "<li class='head txt-dark bg-info'>#<span>Title</span><span>Thumbnail</span><span>File</span></li>";
		
		$i = 1;
		foreach($cards as $card) {

			echo "<ul class='row'>";
			try {
				$this->pdf_done = null;
				$this->img_done = null;

				$post_link = $card->first($this->get_selector('post-link'));
				$post_title = $card->first($this->get_selector('post-title'))->text();
				
				// Helper\pre('post-title', $post_title);
				// return;

				$post_title = Helper\slugify($post_title, true);
				// $post_title = preg_replace('/^كتاب/' ,'', $post_title);

				// Helper\pre($post_title);
				// Helper\pre($post_link);
				// continue;

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
				
				$this->auto_scroll();
				ob_flush();
				flush();

				#----- DOWNLOAD THUMBNAIL -----#
				if( ! file_exists($save_to_img_path) ) {
					
					try {

						$thumb_link = $this->get_selector_node($card, 'post-thumb');
						$thumb_link = $this->get_bg_url($thumb_link);
						// Helper\pre('thumb_link', $thumb_link);
						// Helper\pre('save_to_img_path', $save_to_img_path);
						// return;

						if ($thumb_link) {
							$this->img_done = $this->download_thumb($thumb_link, $save_to_img_path);
							if ($this->img_done) {
								echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-ok');
							}
							else {
								echo $this->icon_msg->msg('Couldn\'t download', 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-cancel');
							}
						}
						else {
							echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
						}
					}
					catch (Exception $e) {
						echo $this->icon_msg->msg("Unknown error!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
					}	
				}
				else {
					echo $this->icon_msg->msg('Already exist!', 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
				}

				ob_flush();
				flush();

				#----- DOWNLOAD PDF FILE -----#
				if( ! file_exists($save_to_pdf_path)) {
					
					
					try {

						$direct_pdf_link = $this->get_element($post_link, 'pdf-link');

						if (! $direct_pdf_link ){
							echo $this->icon_msg->msg("Couldn't find PDF link!", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-unlink');
							echo '</ul>';
							$i++;
							continue;
						}
						// Helper\pre('pdf link', $direct_pdf_link);
						// return;
						
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

				ob_flush();
				flush();
				
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
				
			ob_flush();		
			flush();

			// if ($i>=2){
			// 	exit('====');
			// }
			echo '</ul>';
			$i++;
		}
		
		echo '</ol>';
		
		if ( $this->get_selector('next-page-end') ){
			$this->stop();
			return;
		}

		if( !$this->local_file && $this->get_selector('next-page') ){
			
			$this->document = new Document($url, true);

			$next_page_url = $this->get_next_page_url($this->document, 'next-page');

			// Helper\pre('url', $next_page_url); return;

			ob_flush();	
			flush();

			if ($next_page_url) {
				
				$this->page_num++;

				$next_page_number = $this->get_next_page_number($next_page_url, '/\/page\/(\d+)/i', 1);
				// helper\pre($next_page_number); return;

				if(intval($next_page_number) > 0) {
					$this->page_num = intval($next_page_number);
				}

				$this->set_current_page($this->page_num, $next_page_url);

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

// $url = 'https://kolalkotob.com/cat78.html';
// $folder = 'الفقه الإسلامي';
// $url = 'https://kolalkotob.com/cat30.html';
// $folder = 'المكتبة الإسلامية';
// $url = 'https://kolalkotob.com/cat75.html';
// $folder = 'علوم القرآن';
// $url = 'https://kolalkotob.com/cat76.html';
// $folder = 'التفسير والحديث';

// $url = 'https://kolalkotob.com/cat77.html';
// $folder = 'السيرة النبوية';

#####  TOW STEPS #####

$url = 'https://mktbtypdf.com/categories/%d8%a7%d9%84%d8%af%d9%8a%d8%a7%d9%86%d8%a9-%d8%a7%d9%84%d8%a5%d8%b3%d9%84%d8%a7%d9%85%d9%8a%d8%a9/';
// $folder = 'الديانة الإسلامية';


// $s = new WebDownloader($url, $folder);
$s = new WebDownloader($url);
// $s->set_download_file_types();
$s->set_selector('page-title', 'h2.section-heading');
$s->set_selector('posts', '.library-book');
$s->set_selector('post-title', '.book-title>a');
$s->set_selector('post-author', '.book-author>a');
$s->set_selector('post-link', '.book-title>a::attr(href)');
$s->set_selector('post-thumb', 'div.book-img::attr(style)');
$s->set_selector('pdf-link', '.modal-option>.book-links a::attr(href)');
$s->set_selector('next-page', '.mktbty-pagination>a.next.page-numbers::attr(href)', 'first', false);
// $s->set_selector('next-page-end', '.pagination>li:nth(2).disabled');

$s->init();
$s->header();
$s->start();

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
		
		// https://github.com/Imangazaliev/DiDOM
		## Alternatives:
		// https://www.php.net/manual/en/class.domdocument.php
		// https://simplehtmldom.sourceforge.io/docs/1.9/quick-start/

		// $document = new Document($url, true);
		$cards = $this->document->find($this->get_selector('posts'));
		$count = count($cards);
		
		
		echo '<div class="page-details">';
		echo "<h2 class='site-name'>{$this->get_host()} <span class='download-folder'>{$this->folder}</span></h2>";
		echo "<h3 class='site-url'>{$this->url} <span class='icon-folder-open-empty download-folder'>{$this->page_title}</span></h3>";
		echo '<h4>Page items count: <span class="count-number">' . $count .'</span></h4>';	
		echo '</div>';
		
		
		echo '<ol class="container">';
		echo "<li class='head txt-dark bg-info'>#<span>Title</span><span>Thumbnail</span><span>File</span></li>";
		
		flush();
		ob_flush();
		
		$i = 1;
		foreach($cards as $card) {

			echo "<li class='row'>";
			try {
				$this->pdf_done = null;
				$this->img_done = null;
				
				$post_link = $card->first($this->get_selector('post-link'));
				$post_title = $card->first($this->get_selector('post-title'))->text();
				// $post_title = $cards->first($this->get_selector('post-title'));
				
				
				$post_link = $this->get_host('/') . $post_link;
				$post_title = Helper\slugify($post_title);
				// $post_title = preg_replace('/^كتاب\s/' ,'', $post_title);

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

						$thumb_link = $this->get_selector_node($card, 'post-thumb');
						// $thumb_link = $card->first($this->get_selector('post-thumb'));
						// $thumb_link = $this->get_host() . $thumb_link;
						// Helper\pre('Thumb-1', $thumb_link);
						$thumb_link2 = strtolower($thumb_link);
						if (strpos($thumb_link2,'.jpg') == false && strpos($thumb_link2, '.png') == false) {
							$thumb_link = $this->get_selector_node($card, 'post-thumb2');
						}

						
						if ($thumb_link) {
							$thumb_link = $this->fix_thumb_link($thumb_link);
							// $thumb_link = $this->get_bg_url($thumb_link);
							
							// Helper\pre('Thumb-2', $thumb_link); return;

							$this->img_done = $this->download_thumb($thumb_link, $save_to_img_path);

							// Helper\pre($this->img_done);
							// return;

							if ($this->img_done) {
								// Helper\icon_msg('Downloaded', 'txt-success', $thumb_link, 'icon-picture', 'icon-ok');
								echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-ok');
							}
							else {
								// Helper\icon_msg("Downloaded", 'txt-success', $thumb_link, 'icon-picture', 'icon-cancel');
								echo $this->icon_msg->msg('Couldn\'t Download', 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-cancel');
							}
						}
						else {
							echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
						}
					}
					catch (Exception $e) {
						// Helper\icon_msg("Couldn't find!", 'txt-danger', $thumb_link, 'icon-picture', 'icon-download');
						echo $this->icon_msg->msg("Unknown error!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
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
						// $details_link = $this->get_host('/').$this->get_element($post_link, 'details-link');
						// Helper\pre($details_link);
						// return;

						// if (!$details_link) {
						// 	// Helper\icon_msg("Couldn't find details link!", 'txt-danger', $details_link, 'icon-file-pdf', 'icon-unlink');
						// 	echo $this->icon_msg->msg("Couldn't find details link!", 'txt-danger', $details_link)->icons('icon-file-pdf', 'icon-unlink');
						// 	echo '</ul>';
						// 	$i++;
						// 	continue;
						// }
						// $direct_pdf_link = $this->get_host('/').$this->get_element($details_link, 'pdf-link');
						// $direct_pdf_link = $this->get_elements_ary($post_link, 'pdf-link');
						$direct_pdf_link = $this->get_element($post_link, 'pdf-link');
						// Helper\pre('post_link', $post_link); 
						// Helper\pre('direct_pdf_link', $direct_pdf_link); 
						// return;

						if ( is_array($direct_pdf_link) ){
							echo $this->icon_msg->msg($direct_pdf_link['error'], 'txt-danger', $post_link)->icons('icon-file-pdf', 'icon-unlink');
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
						echo $this->icon_msg->msg("Unknown error!", 'txt-danger', $post_link)->icons('icon-file-pdf', 'icon-cancel');
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
			$next_page_url = $this->get_host('/').$this->get_next_page_url($document, $this->get_selector('next-page'));
			
			flush();
			ob_flush();	

			if ($next_page_url) {
				
				$this->page_num++;

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
	function fix_thumb_link($thumb_link, $post_link='') {
		// Helper\pre($thumb_link);
		if (strpos($thumb_link,"noimg.gif") !== false) {
			//file:///D:/laragon6/www/pdf-scrapper/books/ALHDITH-ALSHRIF_files/noimg.gif
			$thumb_link = __DIR__.'/books/'. str_replace('.html', '', $this->local_file) . '/noimg.gif';
		}
		else if (str_starts_with($thumb_link, '.') || str_starts_with($thumb_link, 'files')) {
			// $url_parts = parse_url($post_link);
			// $thumb_link = $url_parts['scheme'] .'://'. $url_parts['host'] .'/'. str_replace(['./', '100_files'], ['/', 'files'], $thumb_link);
			$thumb_link = __DIR__.'/books/'. str_replace('.html', '', $this->local_file) . '_' . str_replace(['./', '100_files'], ['/', 'files'], $thumb_link);
		// }
		}
		$lower_thumb_link = strtolower($thumb_link);
		if (strpos($lower_thumb_link,".jpg") !== false || strpos($lower_thumb_link,".jpg") !== false || strpos($lower_thumb_link,".gif") !== false) {
			return $thumb_link;
		}
		// return $thumb_link;
	}	
	#-------------------------------------------------------------------------------------------#
}
$base_url = Helper\base_url($_SERVER);
?>
<link rel="stylesheet" href="<?=$base_url?>/assets/style.css" />

<?php
require 'config.php';

// $url = 'https://www.alarabimag.com/193/%D9%83%D8%AA%D8%A8-%D8%A5%D8%B3%D9%84%D8%A7%D9%85%D9%8A%D8%A9/';
// $folder = 'المكتبة الإسلامية';

// $url = 'https://books-library.net/c-ALHDITH-ALSHRIF-download/';
// $local_path = 'ALHDITH-ALSHRIF.html';
// $folder = 'الحديث';

// $url = 'https://books-library.net/c-Islamic-Jurisprudence-(Fiqh)-best-download';
// $local_path = '101.html';
// $folder = 'أشهر كتب الفقه الإسلامي';

$url = 'https://books-library.net/c-ALHDITH-ALSHRIF-download';
$local_path = 'ALHDITH-ALSHRIF.html';
$folder = 'أكثر الكتب تحميلاً في الحديث الشريف';

$s = new WebDownloader($url, $path, $folder, true);
$s->set_local_file_path($local_path);
$s->set_selector('posts', 'div.smlbooks');
$s->set_selector('post-title', 'h3', 'first'); //text
$s->set_selector('post-link', 'a.oneBook::attr(href)', 'first');
$s->set_selector('post-thumb', 'a.oneBook>img::attr(src3)', 'first'); //fix_thumb_link
$s->set_selector('post-thumb2', 'a.oneBook>img::attr(src)', 'first'); //fix_thumb_link
$s->set_selector('details-link', null);
// $s->set_selector('pdf-link', "a[href^='https://books-library.com/files/']::attr(href)", 'first');
// $s->set_selector('pdf-link', "a[href$='.pdf']::attr(href)", 'first');
$s->set_selector('pdf-link', "a[href*='/files/download']::attr(href)", 'first');
// $s->set_selector('pdf-link', "img[src='img/download.png']", 'first');
// $s->set_selectors('pdf-link', [
// 		"a[href$='.pdf']::attr(href)",
// 		"a[href$='.docx']::attr(href)",
// 		"a[href$='.doc']::attr(href)",
// 	], 'first');
$s->set_selector('next-page', null);

$s->init();
$s->header(null, false);
$s->start();

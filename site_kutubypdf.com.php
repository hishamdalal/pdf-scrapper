<?php
namespace App;

// https://stackoverflow.com/a/39350617/2269902
@ini_set('output_buffering','Off');
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);
ob_start();



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
		if (ob_get_level() == 0) ob_start();
		

		$current = $this->get_current_page();

		if(is_array($current)){

			$page_num = $current['number'];
			$current_page_url = $current['url'];
			
			if ($current_page_url!='') {
				$url = $current_page_url;
			}
			if ($page_num) {
				$this->page_num = $page_num;
			}
		}

		$url = $url ? $url : $this->url;
		$url = trim($url, ' ');

		$this->document = new Document($url, true);
		
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
				
				Helper\pre('post-title', $post_title);
				Helper\pre('post-link', $post_link);
				return;

				$post_title = Helper\slugify($post_title, true);
				// $post_title = preg_replace('/^كتاب/' ,'', $post_title);

				// Helper\pre($post_title);
				// Helper\pre($post_link);
				// continue;

				echo '<ul class="line">';
				if ( $post_link ){
			
					$save_to_pdf_path = $this->get_download_path($post_title, 'pdf');
					$save_to_img_path = $this->get_download_path($post_title, 'jpg');

					// Helper\pre($save_to_img_path, $save_to_pdf_path);
					// return;
					
					if ( file_exists($save_to_pdf_path) &&  file_exists($save_to_img_path) )  {
						echo $this->icon_msg->msg($post_title, 'txt-info', $post_link)->icons('icon-doc')->counter($i, $count);
						echo $this->icon_msg->msg('Already exist')->icons('icon-picture', 'icon-download');
						echo $this->icon_msg->msg('Already exist')->icons('icon-file-pdf', 'icon-download');
						echo '</ul>';
						// $i++;
					}else{

						$details_link = $this->get_element($post_link, 'details-link');

						$file_data = $this->get_element($post_link, 'file-size');

						$file_size = str_replace(['\n', '\r'], '', explode("\n", $file_data)[5]);
						$file_size = explode(":", $file_size);
						$file_size = end($file_size);

						echo $this->icon_msg->msg("{$post_title} ({$file_size})", 'txt-primary', $post_link)->icons('icon-download', 'icon-cog-alt')->counter($i, $count);
						
						
						$this->auto_scroll();
						ob_flush();
						flush();

						#----- DOWNLOAD THUMBNAIL -----#
						if( ! file_exists($save_to_img_path) ) {
							
							try {

								$thumb_link = $this->get_selector_node($card, 'post-thumb');
								// $thumb_link = $this->get_bg_url($thumb_link);
								// Helper\pre('thumb_link', $thumb_link);
								// Helper\pre('save_to_img_path', $save_to_img_path);
								// return;

								if ($thumb_link) {

									$save_to_img_path = $this->get_thumb_save_path($thumb_link, $post_title);

									$this->img_done = $this->download_thumb($thumb_link, $save_to_img_path);
									if ($this->img_done) {
										echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-ok');
									}
									else {
										echo $this->icon_msg->msg('Couldn\'t download', 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-cancel');
									}
								} else {
									echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
								}
							}
							catch (Exception $e) {
								echo $this->icon_msg->msg("Unknown error!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
							}	
						} else {
							echo $this->icon_msg->msg('Already exist!', 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
						}

						ob_flush();
						flush();

						#----- DOWNLOAD PDF FILE -----#
						if( ! file_exists($save_to_pdf_path) ) {
							
							
							try {

								
								// Helper\pre([$post_link, $details_link, $file_size]); 
								// die;
								// return;

								if ( str_contains($file_size, "جيجا")) {
									echo $this->icon_msg->msg("Large file size {$file_size}", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-info-circled');
									echo '</ul>';
								}
								else if ($details_link) {
										
										$direct_pdf_link = $this->get_element($details_link, 'file-link');

										// Helper\pre('direct_pdf_link',  [$direct_pdf_link, $file_size]); 
										// return;
										// die;
										
										if (! $direct_pdf_link ){
											echo $this->icon_msg->msg("Couldn't find `font` link!", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-unlink');
											echo '</ul>';
											// $i++;
										}
										else {
											
											$save_to_pdf_path = $this->get_pdf_save_path( $direct_pdf_link, $post_title );
											// Helper\pre('save_to_pdf_path', $save_to_pdf_path); return;
											
											$this->pdf_done = $this->download_pdf($direct_pdf_link, $save_to_pdf_path);

											if($this->pdf_done) {
												echo $this->icon_msg->msg("Downloaded", 'txt-success', $direct_pdf_link)->icons('icon-file-pdf', 'icon-ok');
											}
											else {
												echo $this->icon_msg->msg("Couldn't download!", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-cancel');
											}
										}
									
								}
								else {
									echo $this->icon_msg->msg("Couldn't find details 2 link", 'txt-danger', $$post_link)->icons('icon-file-pdf', 'icon-cancel');
								}
							}	
							catch (Exception $e) {
								echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $post_link)->icons('icon-file-pdf', 'icon-cancel');
							}
						}
						else {
							echo $this->icon_msg->msg("Already exist!", 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
						}			

						ob_flush();
						flush();
						
						echo '</ul>';
					}

				} else {
					echo $this->icon_msg->msg("Couldn't find '{$post_title}' link", 'txt-danger')->icons('icon-cancel', 'icon-unlink');
					echo '</ul>';
					// $i++;
				}

			} catch (Exception $e) {
				echo $this->icon_msg->msg("Unknown error: {$post_title}", 'txt-danger', $post_link)->icons('icon-info-circled', 'icon-cancel');
				// if (!$this->img_done) ...
				// if (!$this->pdf_done) ...
				Helper\pre($e);
				// $i++;
				echo '</ul>';
				// continue;
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
			
			// $this->document = new Document($url, true);

			$next_page_url = $this->get_next_page_url($this->document, 'next-page');

			// Helper\pre('url', $next_page_url); return;

			ob_flush();	
			flush();

			if ($next_page_url) {
				
				$this->page_num++;

				$next_page_number = $this->get_next_page_number($next_page_url, '/\/page\/(\d+)/i', 1);
				// helper\pre($next_page_number); return;

				if(intval($next_page_number) > 0 && intval($next_page_number) > $current) {
					$this->page_num = intval($next_page_number);
				
					$this->set_current_page($this->page_num, $next_page_url);
	
					ob_end_flush();
	
					$this->start($next_page_url);
				
				}

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
	function get_pdf_save_path($direct_pdf_link, $post_title) {
		$file_save_path = '';
		if ($direct_pdf_link) {
			$list = explode('.', $direct_pdf_link);
			$type = array_pop($list);
			$file_save_path = $this->get_download_path($post_title, $type);
		}
		if (! $file_save_path) {
			die(" Coudn't find file type");
		}
		return $file_save_path;
	}
	#-------------------------------------------------------------------------------------------#
	function get_thumb_save_path($direct_thumb_link, $post_title) {
		$thumb_save_path = '';
		if ($direct_thumb_link) {
			$list = explode('.', $direct_thumb_link);
			$type = array_pop($list);
			$thumb_save_path = $this->get_download_path($post_title, $type);
		}
		if (! $thumb_save_path) {
			die(" Coudn't find thumb type");
		}
		return $thumb_save_path;
	}
	#-------------------------------------------------------------------------------------------#
}
$base_url = Helper\base_url($_SERVER);
?>
<link rel="stylesheet" href="<?=$base_url?>/assets/style.css" />

<?php
require 'config.php';

$urls['كتب إسلامية'] = 'https://www.kutubypdf.com/islamic-books/';
$urls['كتب طب'] = 'https://www.kutubypdf.com/medicine-book/';
$urls['كتب طب بديل'] = 'https://www.kutubypdf.com/alternative-medicine/';
//$urls['كتب طب بيطرى'] = 'https://www.kutubypdf.com/veterinary/';
$urls['علوم لغة'] = 'https://www.kutubypdf.com/science-language/';
$urls['فكر وثقافة'] = 'https://www.kutubypdf.com/thought-and-culture/';
// $urls[''] = '';

// $url = 'https://kutubypdf.com/';
// $folder = 'الديانة الإسلامية';

foreach ($urls as $folder => $url) {
	// $s = new WebDownloader($url, $folder);
	$s = new WebDownloader($url);
	$s->set_page_title($folder);
	$s->set_download_file_types('pdf|docx|doc|pptx|ppt|zip|rar|7z');
	//$s->set_selector('page-title', 'section>.page-title');
	$s->set_selector('posts', '#main .card');
	$s->set_selector('post-title', '.card-body>.card-title', 'first');
	// $s->set_selector('post-author', '.book-author>a');
	$s->set_selector('post-link', '.card-body> a.btn::attr(href)');
	$s->set_selector('post-thumb', '.card>.card-img-top::attr(src)');
	//$s->set_selector('file-link', '.page-content .justify-content-right div:nth-child(3) > a.btn::attr(href)', 'first');
	$s->set_selector('next-page', '.next.page-numbers::attr(href)', 'first', false);
	$s->set_selector('details-link', '.entry-content div>a::attr(href)', 'first', true);
	$s->set_selector('details2-link', 'article.post .text-decoration-none::attr(href)', 'first');
	// $s->set_selector('file-link', '.card-body>a::attr(href)', 'first');
	$s->set_selector('file-link', 'article.post .text-decoration-none::attr(href)', 'first');
	$s->set_selector('file-size', 'article .display-7');
	// $s->set_selector('next-page-end', '.pagination>li:nth(2).disabled');

	$s->init();
	$s->header();
	//$s->start();
}


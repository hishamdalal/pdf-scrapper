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
	function __construct($url, $path, $folder='') {
		parent::__construct($url, $path, $folder);
		echo "<h1 class='hero'>PDF Scrapper</h1>";
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

		$document = new Document($url, true);
		$cards = $document->find($this->get_selector('posts'));
		$count = count($cards);
		
		flush();
		ob_flush();
		
		echo "<h2 class='site-name'>{$this->get_host()} -> <span>Page ({$this->page_num})</span></h2><hr>";
		echo "<h3 class='site-url'>{$this->url} -> {$this->folder}<span></h3>";
		echo('<h4>Page items count: ' . $count .'</h4>');

		echo '<ol class="container">';
		echo("<li class='head txt-dark bg-info'>#: <span>Title</span><span>Thumbnail</span><span>File</span></li>");
		
		$i = 1;
		foreach($cards as $card) {
			// Helper\pre(\get_class_methods($card)); exit();

			echo "<ul class='row'>";
			try {
				$this->pdf_done = null;
				$this->img_done = null;

				$post_link = $card->first($this->get_selector('post-link'));
				$post_title = $card->first($this->get_selector('post-title'));
				// $post_title = $cards->first($this->get_selector('post-title'));
				// Helper\pre('post-title', $post_title);
				// return;

				$post_link = $post_link;
				$post_title = Helper\slugify($post_title, true);
				// $post_title = preg_replace('/^كتاب/' ,'', $post_title);

				// Helper\pre($post_title);
				// Helper\pre($post_link);

				// return;
				echo '<ul class="line">';
				if (! $post_link ){
					// echo("<li class='txt-danger'><i class='icon-unlink'></i> <span class='details'>Couldn't find '{$post_title}' link.</span></li>");
					// Helper\icon_msg("Couldn't find '{$post_title}' link", 'txt-danger', '', 'icon-unlink');
					echo $this->icon_msg->msg("Couldn't find '{$post_title}' link", 'txt-danger')->icons('icon-cancel', 'icon-unlink');
					echo '</ul>';
					$i++;
					continue;
				}
		
				$save_to_pdf_path = $this->get_download_path($post_title, 'pdf');
				$save_to_img_path = $this->get_download_path($post_title, 'jpg');

				if (file_exists($save_to_pdf_path) && file_exists($save_to_img_path)) {
					// Helper\icon_msg('Thumbnail', 'txt-success', $thumb_link, 'icon-picture', 'icon-ok');

					// echo("<li class='msg txt-info'>{$i}/{$count}: <a href='{$post_link}'><i class='icon-doc'></i> {$post_title}</a>");
					// Helper\icon_msg('Already exist', 'txt-info', '', 'icon-picture', 'icon-download');
					// Helper\icon_msg('Already exist', 'txt-info', '', 'icon-file-pdf', 'icon-download');
					// echo "</li>";
					echo $this->icon_msg->msg($post_title, 'txt-info', $post_link)->icons('icon-doc')->counter($i, $count);
					echo $this->icon_msg->msg('Already exist')->icons('icon-picture', 'icon-download');
					echo $this->icon_msg->msg('Already exist')->icons('icon-file-pdf', 'icon-download');
					echo '</ul>';
					$i++;
					continue;
				}
				else{
					// echo("<li class='txt-primary'>{$i}/{$count}: <a href='{$post_link}'><i class='icon-download'></i>{$post_title}</a>");
					// Helper\icon_msg("{$i}/{$count}: {$post_title}", 'txt-primary', $post_link, 'icon-download');
					echo $this->icon_msg->msg($post_title, 'txt-primary', $post_link)->icons('icon-download', 'icon-cog-alt')->counter($i, $count);
				}
				
				flush();
				ob_flush();

				#----- DOWNLOAD THUMBNAIL -----#
				if( ! file_exists($save_to_img_path) ) {
					
					try {

						$thumb_link = $card->first($this->get_selector('post-thumb'));
						// $thumb_link =  Helper\slugify($thumb_link);
						// $thumb_link = $this->get_bg_url($thumb_link);
						// Helper\pre('post-title', $post_title);
						// Helper\pre('post-link', $post_link);

						// Helper\pre('thumb_link', $thumb_link);
						// Helper\pre('save_to_img_path', $save_to_img_path);
						// exit;

						$this->img_done = $this->download_thumb($thumb_link, $save_to_img_path);

						if ($this->img_done) {
							// echo("<ul><li class='txt-success'><a href='{$thumb_link}'><i class='icon-picture'></i> Thumbnail <i class='icon-ok'></i></a></li></ul>");
							// Helper\icon_msg('Downloaded', 'txt-success', $thumb_link, 'icon-picture', 'icon-ok');
							echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-ok');

						}
						else {
							// echo("<ul><li class='txt-danger'>[<a href='{$thumb_link}'><i class='icon-cancel'></i> Couldn't find <span>thumbnail</span> <i class='icon-cancel'></i></a>] link!</li></ul>");
							// Helper\icon_msg("Couldn't download!", 'txt-danger', $thumb_link, 'icon-picture', 'icon-cancel');
							echo $this->icon_msg->msg('Downloaded', 'txt-success', $thumb_link)->icons('icon-picture', 'icon-cancel');
						}
					}
					catch (Exception $e) {
						// echo("<ul><li class='txt-danger'>[<a href='{$thumb_link}'><i class='icon-unlink'></i> Couldn't find <span>thumbnail</span>  <i class='icon-cancel'></i></a>] link!</li></ul>");
						// Helper\icon_msg("Couldn't find!", 'txt-danger', $thumb_link, 'icon-picture', 'icon-download');
						echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $thumb_link)->icons('icon-picture', 'icon-unlink');
					}	
				}
				else {
					// echo("<ul><li class='txt-info'><a href='{$post_link}'><i class='icon-picture'></i> is already exist!<i class='icon-info'></i> </a></li></ul>");
					// Helper\icon_msg("Already exist!", 'txt-info', $post_link, 'icon-picture', 'icon-download');
					echo $this->icon_msg->msg('Already exist!', 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
				}

				flush();
				ob_flush();

				#----- DOWNLOAD PDF FILE -----#
				if( ! file_exists($save_to_pdf_path)) {
					
					
					try {

						$direct_download_link = $this->get_element($post_link, 'pdf-link');

						if (! $direct_download_link ){
							// echo("<ul><li class='txt-danger'>[<a href='{$direct_download_link}'><i class='icon-file-pdf'></i> Couldn't find <span>PDF</span> link! <i class='icon-unlink'></i></a>]</li></ul>");
							// Helper\icon_msg("Couldn't find PDF link!", 'txt-danger', $direct_download_link, 'icon-file-pdf', 'icon-unlink');
							echo $this->icon_msg->msg("Couldn't find PDF link!", 'txt-danger', $direct_download_link)->icons('icon-file-pdf', 'icon-unlink');
							echo '</ul>';
							$i++;
							continue;
						}
						$this->pdf_done = $this->download_pdf($direct_download_link, $save_to_pdf_path);

						if($this->pdf_done) {
							// echo("<ul><li class='txt-success'><a href='{$direct_download_link}'><i class='icon-file-pdf'></i> PDF <i class='icon-ok'></i></a></li></ul>");
							// Helper\icon_msg('Downloaded', 'txt-success', $direct_download_link, 'icon-file-pdf', 'icon-ok');
							echo $this->icon_msg->msg("Downloaded", 'txt-success', $direct_download_link)->icons('icon-file-pdf', 'icon-ok');
						}
						else {
							// echo("<ul><li class='txt-danger'>[<a href='{$direct_download_link}'><i class='icon-file-pdf'></i> Couldn't find <span>PDF</span> file link! <i class='icon-unlink'></i></a>]</li></ul>");
							// Helper\icon_msg("Couldn't download!", 'txt-danger', $direct_download_link, 'icon-file-pdf', 'icon-cancel');
							echo $this->icon_msg->msg("Couldn't download!", 'txt-danger', $direct_download_link)->icons('icon-file-pdf', 'icon-cancel');
						}
					}
					catch (Exception $e) {
						// echo("<ul><li class='txt-danger'>[<a href='{$post_link}'><i class='icon-file-pdf'></i> Couldn't find <span>PDF</span> file</a>] link! <i class='icon-unlink'></i></li></ul>");
						// Helper\icon_msg("Couldn't find!", 'txt-danger', $post_link, 'icon-file-pdf', 'icon-download');
						echo $this->icon_msg->msg("Couldn't find!", 'txt-danger', $post_link)->icons('icon-file-pdf', 'icon-cancel');
						// echo '</ul>';
					}
				}
				else {
					// echo("<ul><li class='txt-info'><a href='{$post_link}'><span><i class='icon-file-pdf'></i> PDF</span> file is already exist! <i class='icon-info'></i> </a></li></ul>");
					// Helper\icon_msg("Already exist!", 'txt-info', $post_link, 'icon-picture', 'icon-download');
					echo $this->icon_msg->msg("Already exist!", 'txt-info', $post_link)->icons('icon-picture', 'icon-download');
				}			

				flush();
				ob_flush();
				echo '</ul>';

			} catch (Exception $e) {
				// echo("<ul><li class='txt-danger'><i class='icon-cancel'></i> Error: Couldn't download file '<a class='txt-danger' href=\"{$post_link}\">{$post_title}</a>'</ul>");
				// Helper\icon_msg("Unknown error: {$post_title}", 'txt-danger', $post_link, 'icon-info-circled', 'icon-cancel');
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
		
		if( $this->get_selector('next-page') ){
			$next_page_url = $this->get_host('/').$this->get_next_page_url($document, $this->get_selector('next-page'));
			
			flush();
			ob_flush();	

			if ($next_page_url) {
				
				$this->page_num++;

				$this->set_current_page($this->page_num, $next_page_url);

				$this->start($next_page_url);
			}
		}

		echo ("<p class='proccess-finished txt-light bg-success'>Finished</p>");

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

$url = 'https://kolalkotob.com/cat78.html';
$folder = 'الفقه الإسلامي';
$url = 'https://kolalkotob.com/cat30.html';
$folder = 'المكتبة الإسلامية';

$s = new WebDownloader($url, $path, $folder);
$s->set_selector('posts', '.card');
$s->set_selector('post-title', 'img.card-img-top::attr(alt)');
$s->set_selector('post-link', 'a::attr(href)');
$s->set_selector('post-thumb', 'img.card-img-top::attr(src)');
$s->set_selector('pdf-link', 'a[href^="https://kolalkotob.com/download"]::attr(href)');
$s->set_selector('next-page', '.pagination>li', 'find');

$s->start();

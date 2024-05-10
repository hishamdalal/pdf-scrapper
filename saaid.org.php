<?php
namespace App;
// header('Content-Type: text/html; charset=UTF-8');

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
	function __construct($url, $folder='', $encoding='UTF-8') {
		parent::__construct($url, $folder, $encoding);
	}
	#-------------------------------------------------------------------------------------------#
	function loop_pages() {
		$this->document = new Document($this->url, true);
		$pages = $this->document->find($this->get_selector('pagination'));
		foreach( $pages as $page_url ) {		
			if ( str_starts_with($page_url, 'http:')) {
				$this->start($page_url);
				break;
			}
		}
		$this->stop();
	}
	#-------------------------------------------------------------------------------------------#
	function start($url='') {
		if (ob_get_level() == 0) ob_start();
		

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

		// $url = $url ? $url : $this->url;
		$url = trim($url, ' ');

		// $this->document = new Document($url, true);
		
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

				// $post_link = $card->first($this->get_selector('post-link'));
				$post_link = $this->get_selector_node($card, 'post-link');
				$post_title = $this->get_selector_node($card, 'post-title')->text();
				$post_author = $this->get_selector_node($card, 'post-author')->text();
				// $post_title = $card->find('td:nth-child(1) >a > span > b');
				
				Helper\pre('post-title', $post_title);
				Helper\pre('post-author', $post_author);
				Helper\pre('post-link', $post_link);
				
				continue;

				$post_title = Helper\slugify($post_title, true);
				// $post_title = preg_replace('/^كتاب/' ,'', $post_title);

				// Helper\pre($post_title);
				// Helper\pre($post_link);
				// continue;

				echo '<ul class="line">';
				if ( $post_link ){
			
					$save_to_pdf_path = $this->get_download_path($post_title, 'ttf');

					// Helper\pre($save_to_jpg_path, $save_to_pdf_path);
					// return;
					
					if ( file_exists($save_to_pdf_path) )  {
						echo $this->icon_msg->msg($post_title, 'txt-info', $post_link)->icons('icon-doc')->counter($i, $count);
						echo $this->icon_msg->msg('Already exist')->icons('icon-file-pdf', 'icon-download');
						echo '</ul>';
						// $i++;
						// continue;
					}
					else{
						echo $this->icon_msg->msg($post_title, 'txt-primary', $post_link)->icons('icon-download', 'icon-cog-alt')->counter($i, $count);
						
						
						$this->auto_scroll();
						ob_flush();
						flush();

						#----- DOWNLOAD PDF FILE -----#
						if( ! file_exists($save_to_pdf_path)) {
							
							try {

								// $details_link = $this->get_element($post_link, 'details-link');
								// Helper\pre('details', $details_link); return;

								// if (!$details_link) {
								// 	echo $this->icon_msg->msg("Couldn't find details link!", 'txt-danger', $details_link)->icons('icon-file-pdf', 'icon-unlink');
								// 	echo '</ul>';
								// 	$i++;
								// 	continue;
								// }

								$direct_pdf_link = $this->get_element($post_link, 'pdf-link');

								// Helper\pre('direct_pdf_link', $direct_pdf_link); return;
								
								
								
								if (! $direct_pdf_link ){
									echo $this->icon_msg->msg("Couldn't find `pdf` link!", 'txt-danger', $direct_pdf_link)->icons('icon-file-pdf', 'icon-unlink');
									echo '</ul>';
									// $i++;
									// continue;
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
		$pdf_save_path = '';
		if ($direct_pdf_link) {
			$list = explode('.', $direct_pdf_link);
			$type = array_pop($list);
			$pdf_save_path = $this->get_download_path($post_title, $type);
		}
		if (! $pdf_save_path) {
			die(" Coudn't find pdf type");
		}
		return $pdf_save_path;
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

$title = "القرآن وعلومه والتفسير";
$urls[$title]['المصاحف'] = 'http://saaid.org/book/list.php?cat=132';
$urls[$title]['دروس قرآنية'] = 'http://saaid.org/book/list.php?cat=128';
$urls[$title]['حفظ القرآن الكريم'] = 'http://saaid.org/book/list.php?cat=125';
$urls[$title]['التجويد والقراءات'] = 'http://saaid.org/book/list.php?cat=124';
$urls[$title]['الإعجاز العلمي في القرآن والسنة'] = 'http://saaid.org/book/list.php?cat=104';
$urls[$title]['تفسير القرآن الكريم'] = 'http://saaid.org/book/list.php?cat=101';
$urls[$title]['علوم القرآن الكريم'] = 'http://saaid.org/book/list.php?cat=2';

$title = "الحديث وعلومه";
$urls[$title]['الأربعينات الحديثية'] = 'http://saaid.org/book/list.php?cat=148';
$urls[$title]['الشروح الحديثية'] = 'http://saaid.org/book/list.php?cat=140';
$urls[$title]['علوم الحديث'] = 'http://saaid.org/book/list.php?cat=91';
$urls[$title]['الحديث الشريف'] = 'http://saaid.org/book/list.php?cat=3';

$title = "الفقه الإسلامي";
$urls[$title]['أحكام القضاء'] = 'http://saaid.org/book/list.php?cat=146';
$urls[$title]['علم الفرائض'] = 'http://saaid.org/book/list.php?cat=133';
$urls[$title]['أحكام الجهاد'] = 'http://saaid.org/book/list.php?cat=129';
$urls[$title]['فقه المعاملات'] = 'http://saaid.org/book/list.php?cat=102';
$urls[$title]['فقه العبادات'] = 'http://saaid.org/book/list.php?cat=87';
$urls[$title]['الفقه الإسلامي'] = 'http://saaid.org/book/list.php?cat=4';

$title = "العقيدة";
$urls[$title]['شروحات العقيدة'] = 'http://saaid.org/book/list.php?cat=127';
$urls[$title]['اليوم الآخر'] = 'http://saaid.org/book/list.php?cat=116';
$urls[$title]['توحيد الأسماء والصفات'] = 'http://saaid.org/book/list.php?cat=115';
$urls[$title]['توحيد الألوهية'] = 'http://saaid.org/book/list.php?cat=114';
$urls[$title]['توحيد الربوبية'] = 'http://saaid.org/book/list.php?cat=113';
$urls[$title]['التوحيد والعقيدة'] = 'http://saaid.org/book/list.php?cat=1';


$title = "الأصول والقواعد الفقهية";
$urls[$title]['القواعد الفقهية'] = 'http://saaid.org/book/list.php?cat=130';
$urls[$title]['أصول الفقه'] = 'http://saaid.org/book/list.php?cat=103';

$title = "التربية";
$urls[$title]['التربية والسلوك'] = 'http://saaid.org/book/list.php?cat=82';

$title = "اعرف نبيك صل الله عليه وسلم";
$urls[$title]['محمد صلى الله عليه وسلم'] = 'http://saaid.org/book/list.php?cat=94';

$title = "المواعظ";
$urls[$title]['فضائل الأعمال'] = 'http://saaid.org/book/list.php?cat=142';
$urls[$title]['المواعظ والرقائق'] = 'http://saaid.org/book/list.php?cat=81';

$title = "التراجم والأعلام";
$urls[$title]['السيرة والتراجم والتاريخ'] = 'http://saaid.org/book/list.php?cat=7';

$title = "أحكام المرأة المسلمة";
$urls[$title]['المرأة والأسرة المسلمة'] = 'http://saaid.org/book/list.php?cat=6';

$title = "قضايا طبية";
$urls[$title]['قضايا طبية'] = 'http://saaid.org/book/list.php?cat=151';

$title = "الملل والنحل";
$urls[$title]['اليهود والنصارى'] = 'http://saaid.org/book/list.php?cat=123';
$urls[$title]['العلمانية والليبرالية'] = 'http://saaid.org/book/list.php?cat=122';
$urls[$title]['الصوفية'] = 'http://saaid.org/book/list.php?cat=121';
$urls[$title]['الشيعة'] = 'http://saaid.org/book/list.php?cat=120';
$urls[$title]['الفرق والمذاهب'] = 'http://saaid.org/book/list.php?cat=89';

$title = "كتب الأدب و اللغة العربية";
$urls[$title]['المعاجم والقواميس'] = 'http://saaid.org/book/list.php?cat=126';
$urls[$title]['الشعر والشعراء'] = 'http://saaid.org/book/list.php?cat=126';
$urls[$title]['اللغة العربية'] = 'http://saaid.org/book/list.php?cat=90';


$title = "الأقسام العامة";
$urls[$title]['الأذكار والأدعية الشرعية'] = 'http://saaid.org/book/list.php?cat=144';
$urls[$title]['فوائد وفرائد'] = 'http://saaid.org/book/list.php?cat=143';
$urls[$title]['مكتبة الطفل'] = 'http://saaid.org/book/list.php?cat=106';
$urls[$title]['مكتبة الحاج والمعتمر'] = 'http://saaid.org/book/list.php?cat=99';
$urls[$title]['المكتبة الرمضانية'] = 'http://saaid.org/book/list.php?cat=97';
$urls[$title]['الفتاوى الشرعية'] = 'http://saaid.org/book/list.php?cat=86';
$urls[$title]['قضايا المسلمين'] = 'http://saaid.org/book/list.php?cat=84';

$title = "التراجم والأعلام";
$urls[$title]['السيرة والتراجم والتاريخ'] = 'http://saaid.org/book/list.php?cat=7';

$title = "الردود العلمية";
$urls[$title]['شبهات وردود'] = 'http://saaid.org/book/list.php?cat=131';
$urls[$title]['ردود وتعقيبات'] = 'http://saaid.org/book/list.php?cat=88';

$title = "الردود العلمية";
$urls[$title]['شبهات وردود'] = 'http://saaid.org/book/list.php?cat=131';
$urls[$title]['ردود وتعقيبات'] = 'http://saaid.org/book/list.php?cat=88';

$title = "الدعوة والدعاة";
$urls[$title]['حلقات القرآن الكريم'] = 'http://saaid.org/book/list.php?cat=139';
$urls[$title]['الحوار وآدابه'] = 'http://saaid.org/book/list.php?cat=138';
$urls[$title]['الحسبة'] = 'http://saaid.org/book/list.php?cat=137';
$urls[$title]['الوسائل والأفكار الدعوية'] = 'http://saaid.org/book/list.php?cat=136';
$urls[$title]['خطب ودروس'] = 'http://saaid.org/book/list.php?cat=136';
$urls[$title]['العمل الخيري'] = 'http://saaid.org/book/list.php?cat=134';
$urls[$title]['الدعوة والدعاة'] = 'http://saaid.org/book/list.php?cat=5';


foreach($urls as $title=>$data) {
	foreach($data as $folder=>$url) {

		// $s = new WebDownloader($url, $folder);
		$s = new WebDownloader($url, $folder, 'windows-1256');
		$s->set_page_title($title);
		$s->set_download_file_types('pdf|doc|docx');
		// $s->set_selector('page-title', 'h1.display-4 strong');
		$s->set_selector('posts', 'table[style] table[border="1"] tr[valign]');
		$s->set_selector('post-link',  'td:nth-child(1) >a::attr(href)', 'first', '/');
		$s->set_selector('post-title', 'td:nth-child(1) >a > span > b', 'first');
		$s->set_selector('post-author', 'td:nth-child(2) >a > span > b', 'first');
		// $s->set_selector('post-thumb', '.card-body a>img::attr(src)');
		// $s->set_selector('details-link', '#morris_area_chart .justify-content-center>a[title]::attr(href)', 'find');
		// $s->set_selector('download-link', '.page-content a.bg-green[href^="https://alpdf.com/"]::attr(href)', 'first');
		//$s->set_selector('pdf-link', '.page-content .justify-content-right div:nth-child(3) > a.btn::attr(href)', 'first');
		$s->set_selector('pagination', 'div[style] a::attr(href)', 'find', false);
		// $s->set_selector('next-page-end', '.pagination>li:nth(2).disabled');
		
		// $s->init();
		$s->header();
		$s->loop_pages();

		break;
	}
}






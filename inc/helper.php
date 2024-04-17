<?php
namespace App\Helper;

// https://stackoverflow.com/a/44480391/2269902
// function slugify($str, $all=false){
// 	$str = trim(str_replace("\n", '', strip_tags($str)));

// 	$str = filter_var( $str, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
// 	if($all) {
//         $special_chars = array( '  ', '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '|', '~', '`', '!', '{', '}', '%', '+', '’', '«', '»', '”', '“', '؟', '^', chr( 0 ) );
//         $str = str_replace($special_chars, '', $str);
//     }
//     // return preg_replace("/[^[:alnum:][:space:]]/u", '', $str);
//     return $str;
// }

// https://stackoverflow.com/a/2021647/2269902
// https://stackoverflow.com/a/56328226/2269902
// https://graphemica.com/unicode/characters
function slugify($file_name) {
    $unicode = [
        "~[\x{0000}-\x{001F}]~u", # control characters
        "~[\x{0021}-\x{0024}]~u", # ! " # $
        "~[\x{0027}]~u", # '
        "~[\x{002A}]~u", # *
        "~[\x{002C}]~u", # ,
        "~[\x{002F}]~u", # /
        "~[\x{005C}]~u", # \
        "~[\x{003A}-\x{003C}]~u", # : ; <
        "~[\x{003E}-\x{0040}]~u", # > ? @
        "~[\x{005E}]~u", # ^
        "~[\x{0060}]~u", # `
        "~[\x{007C}]~u", # |
        "~[\x{007E}-\x{00BF}]~u", 
        "~[\x{00C0}-\x{05F4}]~u", 
        "~[\x{0600}-\x{061F}]~u",
        "~[\x{064B}-\x{065F}]~u",
        "~[\x{066B}-\x{2EBE0}]~u",
        "/-+/",
    ];
    $file_name = str_replace([chr(0), chr(9), chr(10), chr(13), chr(128), "\n"], '', $file_name);
    $file_name = preg_replace($unicode, '-', $file_name);
    $file_name = preg_replace('/-+/', '-', $file_name);
    $file_name = trim($file_name, '-');
    return $file_name;
}

function pre($title, $message=''){
	echo '<fieldset>';
	if(!$message) {
		$message = $title;
	}
	else {
		echo '<legend class="txt-danger bg-warning">', $title, '</legend>';
	}
	
	echo '<pre>';
	print_r($message);
	echo '</pre>';
	echo '</fieldset>';
}

function create_folder($path) {
	if (is_dir($path) ) {
        return ['result'=>'fail', 'msg' => 'Directory: "'.$path.'" is already exist!', 'code'=>1];
    }
    if (! mkdir($path, 0777, true) ){
        return ['result'=>'fail', 'msg'=> 'Couldn\' make dir: "'.$path.'"', 'code'=>2];
    }
    return ['result'=>'success', 'msg'=> 'Directory created successfully', 'code'=>3];
}

// https://stackoverflow.com/a/8891890/2269902
function url_origin( $s, $use_forwarded_host = false ) {
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function base_url( $s, $use_forwarded_host = false ) {
   return url_origin( $s, $use_forwarded_host ) .dirname($_SERVER['PHP_SELF']);
}

function full_url( $s, $use_forwarded_host = false ) {
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}

// function icon($code='txt-info') {
//     echo "<i class='{$code}'></i>";
// }
// function icon_msg($message, $color_code='txt-primary', $link='', $prefix_icon_code='', $suffix_icon_code='') {
//     if ($prefix_icon_code) {
//         $prefix_icon_code = "<i class='{$prefix_icon_code}'></i> ";
//     }
//     if ($suffix_icon_code) {
//         $suffix_icon_code = "<i class='{$suffix_icon_code}'></i>";
//     }
//     $prefix_link = $suffix_link = '';
//     if ($link) {
//         $prefix_link = "<a href='{$link}'>";
//         $suffix_link = '</a>';
//     }
//     echo "<li class='msg {$color_code}'>{$prefix_link}{$prefix_icon_code}{$message}{$suffix_icon_code}{$suffix_link}</li>";
// }

// https://stackoverflow.com/a/408416/2269902
function is_404($url) {
    $result = false;

    $handle = curl_init($url);
    curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    
    $response = curl_exec($handle);
    
    /* Check for 404 (file not found). */
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    if($httpCode == 404) {
        $result = true;
    }
    
    curl_close($handle);
    return $result;
}

class IconMsg
{
    private $message;
    private $color;
    private $link;
    private $msg;
    private $counter;
    private $icon;

    function __construct() {
        $this->msg = "";
        $this->color = "txt-primary";
        $this->link = "";
        $this->counter = new \stdClass();
        $this->counter->i = 0;
        $this->counter->count = 0;
        $this->icon = new \stdClass();
        $this->icon->start = "";
        $this->icon->end = "";
    }

    function msg($message, $color='txt-info', $link='') {
        $this->msg = $message;
        $this->color($color);
        $this->link($link);
        $this->counter->i = 0;
        $this->counter->counter = 0;
        return $this;
    }

    function counter($i, $count) {
        $this->counter->i = $i;
        $this->counter->count = $count;
        return $this;
    }

    function icons($icon_start, $icon_end='') {
        $this->icon->start = $icon_start;
        $this->icon->end = $icon_end;
        $this->counter->i = 0;
        $this->counter->counter = 0;
        return $this;
    }

    function color($code='txt-info') {
        $this->color = $code;
        $this->counter->i = 0;
        $this->counter->counter = 0;
        return $this;
    }
    
    function link($href) {
        $this->link = $href;
        $this->counter->i = 0;
        $this->counter->counter = 0;
        return $this;
    }

    function print($return=false) {
        $counter = "";
        if ($this->counter->i > 0 && $this->counter->count > 0) {
            $counter = "<span>{$this->counter->i} / {$this->counter->count} : </span>";
        }

        $prefix_icon_code =  $suffix_icon_code = '';
        if ($this->icon->start) {
            $prefix_icon_code = "<i class='{$this->icon->start}'></i> ";
        }
        if ($this->icon->end) {
            $suffix_icon_code = "<i class='{$this->icon->end}'></i>";
        }

        $prefix_link = $suffix_link = '';
        if ($this->link) {
            $prefix_link = "<a target='_blank' href='{$this->link}'>";
            $suffix_link = '</a>';
        }
        $result = "";
        if ($counter) {
            $result .= "<li class='msg {$this->color}'>{$counter}</li>";
        }
        $result .= "<li class='msg {$this->color}'>{$prefix_link}{$prefix_icon_code}{$this->msg}{$suffix_icon_code}{$suffix_link}</li>";
        if ($return) return $result;
        echo $result;

    }

    function __toString(){
        return $this->print(true);
    }
}

// $msg = new IconMsg();
// // $msg->counter(1, 20);
// echo $msg->msg("Success", "txt-success", "#")->icons("icon-download", "icon-ok")->counter(1, 20); echo '<br>';
// echo $msg->msg("Success", "txt-success", "#")->icons("icon-download", "icon-ok"); echo '<br>';
// echo $msg->msg("Success", "txt-success", "#")->icons("icon-download", "icon-ok")->counter(9, 20); echo '<br>';
// // $msg->icons("icon-download", "icon-ok");
// // $msg->color("txt-success");
// // $msg->link($post_link);
// // echo $msg;


// https://stackoverflow.com/a/2602624/2269902

function get_remote_file_size( $url ) {
    // Assume failure.
    $result = -1;
  
    $curl = curl_init( $url );
  
    // Issue a HEAD request and follow any redirects.
    curl_setopt( $curl, CURLOPT_NOBODY, true );
    curl_setopt( $curl, CURLOPT_HEADER, true );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
  
    $data = curl_exec( $curl );
    curl_close( $curl );
  
    if( $data ) {
      $content_length = "unknown";
      $status = "unknown";
  
      if( preg_match( "/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches ) ) {
        $status = (int)$matches[1];
      } elseif( preg_match( "/^HTTP\/2 (\d\d\d)/", $data, $matches ) ) {
        $status = (int)$matches[1];
      }
  
      if( preg_match( "/Content-Length: (\d+)/", $data, $matches ) ) {
        $content_length = (int)$matches[1];
      } elseif( preg_match( "/content-length: (\d+)/", $data, $matches ) ) {
          $content_length = (int)$matches[1];
      }
  
      // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
      if( $status == 200 || ($status > 300 && $status <= 308) ) {
        $result = $content_length;
      }
    }
  
    return $result;
  }


//   pre( get_remote_file_size($url) );
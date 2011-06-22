<?php
  error_reporting(0);
  ini_set('display_errors', 0);
  date_default_timezone_set('GMT');
	
  function highlight($text, $words){
    $words = stripslashes($words);
    if(preg_match('/^"(.*)"$/', $words, $matches)){
      $text = preg_replace("|($matches[1])|Ui" ,"<strong class='highlight'>$1</strong>", $text);
    }else{
      $words = preg_replace("/[^a-z0-9 ]/i", '', $words);
      $split_words = explode( " " , $words );

      foreach ($split_words as $word) {
        $text = preg_replace("|($word)|Ui" ,"<strong class='highlight'>$1</strong>", $text);
      }
    }    
    return $text;
  }
  
  function time_ago($date){
    $stf = 0;
    $cur_time = time();
    $differ = $cur_time - $date;
    $phrase = array('second','minute','hour','day','week','month','year','decade');
    $length = array(1,60,3600,86400,604800,2630880,31570560,315705600);

    for($i = sizeof($length)-1; ($i >= 0) && (($no = $differ/$length[$i])<= 1); $i--); if($i < 0) $i = 0; $_time = $cur_time -($differ%$length[$i]);
    $no = floor($no); if($no <> 1) $phrase[$i] .='s'; $value=sprintf("%d %s ",$no,$phrase[$i]);

    if(($stf == 1) && ($i >= 1) && (($cur_tm-$_time) > 0)) $value .= time_ago($_time);

    return $value.' ago';
  }
  
  function xmlentities($string) { 
    return strtr($string, array("<", ">", "\"", "'", "&"), array("<", ">", '"', "'", "&#163;")); 
  }

  
  $search_term = isset($_GET['q']) ? $_GET['q'] : false;
  
  if($search_term){
    $data = array();
    $facebook = file_get_contents('http://graph.facebook.com/search?q='.urlencode(stripslashes($search_term)));
    $facebook = json_decode($facebook);
    $facebook_since_id = '';
    
    foreach($facebook->data as $f){
      if($f->type == 'status'){
        $id = explode('_', $f->id);
        $comment_id = $id[1];
        $user_id = $id[0];
        $permalink = 'https://www.facebook.com/permalink.php?story_fbid='.$comment_id.'&id='.$user_id;
      
        $body = array();
    		$body['message'] = $f->message;
        $body['link'] = $permalink;
        $body['user'] = $f->from->name;
        $body['user_image'] = 'https://graph.facebook.com/'.$f->from->id.'/picture';
        $body['source'] = 'facebook';
        
        $facebook_since_id = strtotime($f->created_time);
        $data[strtotime($f->created_time)][] = $body;
      }
    }
    
    $twitter = file_get_contents('http://search.twitter.com/search.json?q='.urlencode(stripslashes($search_term)));
    $twitter = preg_replace('/"id":(\d*),/', '"id":"$1",', $twitter);
    $twitter = json_decode($twitter);
    $twitter_since_id = '';
    
    foreach($twitter->results as $t){
      $body = array();
  		$body['message'] = $t->text;
      $body['link'] = 'https://twitter.com/#!/travelcomments/status/'.$t->id;
      $body['user'] = $t->from_user;
      $body['user_image'] = $t->profile_image_url;
      $body['source'] = 'twitter';
      $twitter_since_id = $t->id;
      $data[strtotime($t->created_at)][] = $body;
    }

    // Sort the timestamps
    krsort($data, SORT_NUMERIC);
  }
  
  $items = '';
  if($search_term){
    // Display the data any way you want
    foreach($data as $timestamp => $d){
      $items .= '<item>
                  <title>'.htmlentities($d[0]['user'].' - '.stripslashes($search_term)).'</title>
                  <link>'.htmlentities($d[0]['link']).'</link>
                  <description><![CDATA['.xmlentities($d[0]['message']).']]></description>              
                  <pubDate>'.strftime("%a, %d %b %Y %T %Z", $timestamp).'</pubDate>
                </item>';
    }
  }  
?>
<?php 
header('Content-type: text/xml'); 
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache")

?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
  <channel> 
  	<title><?php echo stripslashes($search_term); ?> | SocialSearch</title> 
  	<link>http://procodeable.co.uk/socialsearch/feed.php?q=<?php echo urlencode(stripslashes($search_term)); ?></link> 
  	<description>Social Search - Twitter and Facebook search</description> 
  	<lastBuildDate><?php echo strftime("%a, %d %b %Y %T %Z", strtotime('now')) ?></lastBuildDate> 
  	<language>en</language> 

    <?php echo $items; ?>

  </channel>
</rss>
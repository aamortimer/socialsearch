<?php
  error_reporting(0);
  ini_set('display_errors', 0);
  
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
  
  $search_term = isset($_GET['q']) ? $_GET['q'] : false;

  if(isset($_GET['ajax'])){
    $facebook_since = $_GET['facebook_since'];
    $twitter_since = $_GET['twitter_since'];
  }else{
    $facebook_since = '';
    $twitter_since = '';
  }
  
  if($search_term){
    $data = array();
    $facebook = file_get_contents('http://graph.facebook.com/search?q='.urlencode(stripslashes($search_term)).'&since='.$facebook_since);
    $facebook = json_decode($facebook);
    $facebook_since_id = '';
    
    foreach($facebook->data as $f){
      if($f->type == 'status'){
        $id = explode('_', $f->id);
        $comment_id = $id[1];
        $user_id = $id[0];
        $permalink = 'https://www.facebook.com/permalink.php?story_fbid='.$comment_id.'&id='.$user_id;
      
        $body = array();
        $msg = highlight($f->message, $search_term);
    		$body['message'] = $msg;
        $body['link'] = $permalink;
        $body['user'] = $f->from->name;
        $body['user_image'] = 'https://graph.facebook.com/'.$f->from->id.'/picture';
        $body['source'] = 'facebook';
        
        $facebook_since_id = strtotime($f->created_time);
        $data[strtotime($f->created_time)][] = $body;
      }
    }
    
    $twitter = file_get_contents('http://search.twitter.com/search.json?q='.urlencode(stripslashes($search_term)).'&since_id='.$twitter_since);
    $twitter = preg_replace('/"id":(\d*),/', '"id":"$1",', $twitter);
    $twitter = json_decode($twitter);
    $twitter_since_id = '';
    
    foreach($twitter->results as $t){
      $body = array();
  		$msg = highlight($t->text, $search_term);            
  		$body['message'] = $msg;
      $body['link'] = 'https://twitter.com/#!/travelcomments/status/'.$t->id;
      $body['user'] = $t->from_user;
      $body['user_image'] = $t->profile_image_url;
      $body['source'] = 'twitter';
      $twitter_since_id = $t->id;
      $data[strtotime($t->created_at)][] = $body;
    }

    // Sort the timestamps
    krsort($data, SORT_NUMERIC);
    
    if(isset($_GET['ajax'])){
      $facebook_since = $_GET['facebook_since'];
      $twitter_since = $_GET['twitter_since'];
      
      // Display the data any way you want
      foreach($data as $timestamp => $d){
        echo '<div class="status entry clearfix">
                <span class="image"><img src="'.$d[0]['user_image'].'" class="profile_pic"/></span>
                <span class="message">
                '.$d[0]['message'].'
                </span>
                <span class="meta">
                  <a href="'.$d[0]['link'].'">'.$d[0]['user'].'</a> | '.time_ago($timestamp).' | <a href="'.$d[0]['link'].'">'.$d[0]['source'].'</a>
                </span>
              </div>';
      }
      die();
    }
  }
?>
<!DOCTYPE html >
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Social Search</title>
    <meta name=viewport content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/master-min.css" />
    <?php if ($search_term) : ?>
    <link rel="alternate" type="application/rss+xml" title="Travel News" href="http://procodeable.co.uk/socialsearch/feed.php?q=<?php echo urlencode(stripslashes($search_term)); ?>" />
    <?php endif; ?>
  </head>
  <body id="page">
        
    <div id="header">
      <h1 class="title"><a href="http://procodeable.co.uk/socialsearch/" title="Social Search">Social Search</a></h1>
      <h2 class="subtitle">Twitter and Facebook search</h2>
    </div>
    
    <div id="wrap">
      
      <form action="" method="get" accept-charset="utf-8">
        <p>
          <input type="text" name="q" value="<?php echo htmlspecialchars(stripslashes($search_term)); ?>" id="q" placeholder="Enter your search term" />
        </p>
      </form>
      <div id="results">
      <?php        
        if($search_term){
          // Display the data any way you want
          foreach($data as $timestamp => $d){
            echo '<div class="status entry clearfix">
                    <span class="image"><img src="'.$d[0]['user_image'].'" class="profile_pic"/></span>
                    <span class="message">
                    '.$d[0]['message'].'
                    </span>
                    <span class="meta">
                      <a href="'.$d[0]['link'].'">'.$d[0]['user'].'</a> | '.time_ago($timestamp).' | <a href="'.$d[0]['link'].'">'.$d[0]['source'].'</a>
                    </span>
                  </div>';
          }
        }
      ?>
      </div>
      <span class="hide" id="twitter_since"><?php echo $twitter_since_id; ?></span>
      <span class="hide" id="facebook_since"><?php echo $facebook_since_id; ?></span>
      
    </div><!-- end #wrap -->

    <div id="footer" class="clearfix">
      Created by Andy Mortimer <a href="http://twitter.com/mortimer">@mortimer</a>
      <?php if($search_term): ?>
       | <a href="http://procodeable.co.uk/socialsearch/feed.php?q=<?php echo urlencode(stripslashes($search_term)); ?>" title="Search Feed">RSS Feed</a>
      <?php endif; ?>
    </div>
    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/scripts-min.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-24143847-1']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
  </body>
</html>
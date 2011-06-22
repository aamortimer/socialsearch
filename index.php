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
    <title>Social Search</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <!--<link rel="stylesheet" type="text/css" href="css/master.css" />-->
  </head>
  <body id="page">
    <style type="text/css" media="screen">
      body{
        font:normal 18px/22px HelveticaNeue-Light, 'Helvetica Neue Light', sans-serif;
        background:#efefef url('imgs/bkg.gif');
        color:#444;
      }
      #wrap{
        margin:10px auto;
        width:960px;
        padding:10px;
        background:#fff;
        border-radius:10px;
        -moz-box-shadow: rgba(0,0,0, 0.2) 0px 0px 12px;  
        -webkit-box-shadow: rgba(0,0,0, 0.2) 0px 0px 12px;
      }
      .entry{
        margin-bottom:20px;
        background-color:#fff;
        padding:10px;
        border-bottom:1px solid #efefef;
        width:935px;
        clear:both;
      }
      .entry .meta{
        display:block;
        clear:both;
        font-size:14px;
        float:right;
      }
      .entry .message{
        float:left;
        width:850px;
      }
      .entry .image{
        float:left;
        margin-right:5px;
      }
      .highlight{
        background:#FFA;
      }
      #header{
        width:980px;
        margin:30px auto;         
      }
      h1.title a{
        text-shadow: white 0 1px 0px;
        font-weight: normal;
        font-size: 40px;
      }
      h2.subtitle{
        text-shadow: white 0 1px 0px;
        font-weight: normal;
        font-size: 14px;
        margin: -19px 0 0 10px;
        color: #666;
      }
      #footer, #footer a{
        text-shadow: white 0 1px 0px;
        font-weight: normal;
        font-size: 14px;
        color: #666;      
        width:980px;
        margin:10px auto;           
      }
      a:hover{
        color:rgba(81, 203, 238, 1);
      }
      a{
        -webkit-transition: color .4s ease;
        color: #B8B8B8;
        text-decoration:none;
      }
      .clearfix:before, .clearfix:after { content: "\0020"; display: block; height: 0; overflow: hidden; }
      .clearfix:after { clear: both; }
      .clearfix { zoom: 1; }
      @-webkit-keyframes glow {
        from {
          border:1px solid rgba(184, 184, 184, 0.6);
          box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
          -moz-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
          -webkit-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;
        }
        50% {
          border:#35a5e5 1px solid;
          box-shadow: 0 0 5px rgba(81, 203, 238, 1);
          -webkit-box-shadow: 0 0 5px rgba(81, 203, 238, 1);
          -moz-box-shadow: 0 0 5px rgba(81, 203, 238, 1);
        }
        to { 
          border:1px solid rgba(184, 184, 184, 0.6);
          box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
          -moz-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
          -webkit-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;
        }
      }
      #q{
        width:945px;
        padding:10px 5px;
        font-size: 18px;
        color:#444;
        border:1px solid rgba(184, 184, 184, 0.6);
        box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
        -moz-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;  
        -webkit-box-shadow: rgba(0,0,0, 0.1) 0px 0px 8px;
        background: -webkit-gradient(linear, left top, left 25, from(#eeeeee), to(#FFFFFF));  
        background: -moz-linear-gradient(top, #eeeeee, #FFFFFF 25px);
        outline:none;
        -webkit-animation-name: 'glow';
        -webkit-animation-duration: 5s;
        -webkit-animation-iteration-count: infinite;
        -webkit-animation-direction: alternate;
        -webkit-animation-timing-function: ease-in-out;
      }
      #q::-webkit-input-placeholder {
         color: #9c9c9c;
         font-style: italic;
      }
      #q:-moz-placeholder {
         color: #9c9c9c;
         font-style: italic;
      }
      .hide{display:none;}
    </style>
    
    <div id="header">
      <h1 class="title"><a href="" title="Social Search">Social Search</a></h1>
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
    </div>
    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" charset="utf-8">
      function submitenter(form, e){
        var keycode;
        if (window.event) {
          keycode = window.event.keyCode;
        }else if (e){
          keycode = e.which;
        }else{
          return true;
        } 
        if (keycode == 13){
          form.form.submit();
          return false;
        }else{
          return true;
        }
        
        document.getElementById('q').onkeypress = function(e){
          submitenter(this, e);
        }
      }
      
      var q = escape($('#q').val());
      var twitter_since = $('#twitter_since').html();
      var facebook_since = $('#facebook_since').html();
      setInterval(function(){
        $.get('index.php?ajax=true&q='+q+'&twitter_since='+twitter_since+'&facebook_since='+facebook_since, function(data) {
          $('#results').html(data);
        });
      }, 40000);
    </script>
  </body>
</html>
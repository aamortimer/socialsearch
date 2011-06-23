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
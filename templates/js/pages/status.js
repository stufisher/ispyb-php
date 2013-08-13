$(function() {
  var log_thread;
  var lines = [];
  
  $('div.status.pv, div.status.webcams').show()
  
  refresh_pvs()
  
  // Get PVs
  function refresh_pvs() {
    var t = new Date()
    $.ajax({
        url: '/status/ajax/bl/'+bl,
        type: 'GET',
        dataType: 'json',
        error: function(a,b,c) {
           console.log(a)
           console.log(b)
           console.log(c)
        },
           
        success: function(pvs){           
          $.each(pvs, function(k,v) {
            var c;
            if (k == 'Ring Current') c = v < 10 ? 'off' : 'on'
            else if (k == 'Ring State') c = v == 'User' ? 'on' : 'off'
            else if (k == 'Hutch') c = v == 'Locked' ? 'on' : 'off'
            else if (k == 'Refil') c = v == -1 ? 'off' : 'on'
            else c = v == 'Closed' ? 'off' : 'on'
                 
            if ($('.pv[pv="'+k+'"]').length) {
              $('.pv[pv="'+k+'"]').removeClass('on').removeClass('off').addClass(c).children('p').html(v)
            } else {
              $('<div class="pv" pv="'+k+'"><h1>'+k+'</h1><p>'+v+'</p></div>').addClass(c).hide().appendTo('.status .pvs').fadeIn()
            }
          })
        }
      })
  
      if ($('div.status').is(':visible')) {
        setTimeout(function() {
          refresh_pvs()
        }, 3000)
      }
  }

  
  // Status H1 toggles status visibility
  $('h1.status.webcams').click(function() {
    $('div.status.webcams').slideToggle()
                       
    $('.webcam img').each(function(i,w) {
      $(this).attr('src', $('div.status.webcams').is(':visible') ? ('/image/cam/bl/'+bl+'/n/'+i) : '')
    })
  })  
  
  $('h1.status.oavs').click(function() {
    $('div.status.oavs').slideToggle()
                       
    $('.oav img').attr('src', $('div.status.oavs').is(':visible') ? ('/image/oav/bl/'+bl) : '')
  })  
  
  _gda_log()
  
  // Refresh gda log
  function _gda_log(old,p) {
    console.log(p)
    $.ajax({
        url: '/status/ajax/log/bl/'+bl+(p?('/p/'+p):''),
        type: 'GET',
        dataType: 'json',
        success: function(log){
          console.log(log.length)
           
          $.each(old ? log.reverse() : log, function(i,l) {
            // 2013-08-12 17:33:05,892
            var id = l // l.substring(0,90)
            //l = l.replace(/(\d+-\d+-\d+ \d+:\d+:\d+,\d+)/, '<span class="b">$1</span>')
                 
            //if (!$('.log.gda ul li[l="'+id+'"]').length) {
            if ($.inArray(l, lines) == -1) {
              var line = $('<li l="'+id+'">'+l+'</li>').hide()
              old ? line.appendTo('.log.gda ul').fadeIn() : line.prependTo('.log.gda ul').slideDown()
              lines.push(l)
            }
          })
           
          //$.each($('.gda ul li').slice(500), function(i,l) {
          //  $(this).remove()
          //})
        }
      })
  
      log_thread = setTimeout(function() {
        _gda_log()
      }, 3000)
  }

  // Load next page of log on scroll to end
  $('.log.gda ul').bind('scroll', function() {
    if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight*0.8) {
        clearTimeout(log_thread)
        var p = Math.floor($('.log.gda ul li').length / 100) + 1
        _gda_log(true, p)
    }
  })


})

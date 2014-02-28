$(function() {
  var epics_thread = null
  var epics_poll = false;
  var log_thread = null;
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
  
  
  // EPICS screens
  $('.epics').dialog({ autoOpen: false, buttons: { 'Close': function() {
    clearTimeout(epics_thread)
    epics_poll = false;
    $(this).dialog('close');
  } } });
  
  // Load Page Names
  $.ajax({
     url: '/status/ajax/ep',
     type: 'GET',
     dataType: 'json',
     success: function(pages){
         $.each(pages, function(i,t) {
            $('<button id="#'+i+'">'+t+'</button>').button().appendTo($('.screens')).click(function() {
              $('.epics .motors').empty()
              epics_poll = true;
             _load_page(i, function() { $('.epics').dialog('option', 'title', t).dialog('open') })
            })
         })
     }
  })

  
  function _load_page(id,callback) {
    $.ajax({
        url: '/status/ajax/epics/c/'+id+'/bl/'+bl,
        type: 'GET',
        dataType: 'json',
        success: function(pvs){
          $.each(pvs, function(k,v) {
             if (v['t'] == 1) {
               if (!$('.epics .motors .motor[mid="'+k+'"]').length) $(_generate_motor(k,v['val'])).hide().appendTo($('.epics .motors')).fadeIn()
               _update_motor(k,v['val'])
             } else if (v['t'] == 2) {
                 if (!$('.epics .motors .motor[tid="'+k+'"]').length) $(_generate_toggle(k,v['val'])).hide().appendTo($('.epics .motors')).fadeIn()
                 _update_toggle(k,v['val'])
             }
          })
           
          if (callback) callback() 
          if (epics_poll) epics_thread = setTimeout(function() { _load_page(id) }, 1000)
        }
    })
  }
  
  function _update_motor(title, pv) {
    var p = $('.motor[mid="'+title+'"]')
  
    $('.value', p).html(pv['VAL'])
    $('.readback', p).html(pv['RBV'])
    $('.value', p).html(pv['VAL'])
  
    var buttons = {'SEVR': { MAJOR: 'inactive', MINOR: 'minor' },
                   'DMOV': { 0: 'active' },
                   'HLS': { 1: 'minor' },
                   'LLS': { 1: 'minor' },
                   'LVIO': { 1: 'minor' },
    }
  
    $.each(buttons, function(k,b) {
      var bd = $('.button.'+k.toLowerCase(), p)
      $.each(b, function(v,s) {
        pv[k] == v ? bd.addClass(s) : bd.removeClass(s)
      })
    })
  
    var b = $('.button.ffe', p)
    var ffe = (pv['MSTA'] & 1<<6) == 1<<6
    ffe ? b.addClass('inactive') : b.removeClass('inactive')
  
  }
  
  function _generate_motor(title, pv) {
    return '<div class="motor" mid="'+title+'">'+
        '<div class="value" title="Set Value">'+pv['VAL']+'</div>'+
        '<h1>'+title+'</h1>'+
        '<div class="main">'+
            '<div class="below">'+
                '<div class="button l sevr" title="Alarm">!</div>'+
                '<div class="button r dmov" title="Moving">M</div>'+
                '<div class="readback" title="Readback Value">'+pv['RBV']+'</div>'+
            '</div>'+
        '</div>'+
        '<div class="buttons clearfix">'+
            '<div class="label"><div class="button hls">&nbsp;</div> High Limit</div>'+
            '<div class="label"><div class="button lls">&nbsp;</div> Low Limit</div>'+
            '<div class="label"><div class="button lvio">&nbsp;</div> Soft Limit</div>'+
            '<div class="label"><div class="button ffe">&nbsp;</div> Following Error</div>'+
        '</div>'+
    '</div>'
  }
  
  function _generate_toggle(title, v) {
    return '<div class="motor" tid="'+title+'">'+
        '<h1 class="clearfix"><div class="button r">&nbsp;</div>'+title+'</h1>'+
    '</div>'
  }
  
  function _update_toggle(title, v) {
    var p = $('.motor[tid="'+title+'"]')
    var bd = $('.button', p)
    v ? bd.addClass('active') : bd.removeClass('active')
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
    $.ajax({
        url: '/status/ajax/log/bl/'+bl+(p?('/p/'+p):''),
        type: 'GET',
        dataType: 'json',
        success: function(log){
          $.each(old ? log.reverse() : log, function(i,l) {
            // 2013-08-12 17:33:05,892
            var id = l // l.substring(0,90)
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

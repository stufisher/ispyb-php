$(function() {
  
  $('.message.saved').hide()
  
  $('.error').dialog({ autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } } });
  $('.confirm').dialog({ autoOpen: false, modal: true })
  
  $('select.protein, select.pprotein').combobox({invalid: _add_protein, change: function() { _validate_container(); _try_cache() }, select: function() { _validate_container(); _try_cache() } })
  
  $('input.sname,input.comment,input[name=container]').unbind("change keyup input").bind("change keyup input", function() {
    _validate_container()
  })
  
  $('table.samples tr td button.clone').button({ icons: { primary: 'ui-icon-circle-plus' }, text: false }).click(function(e) {
    e.preventDefault();
    _clone($(this))
  })
  
  $('button.clone_puck').button({ icons: { primary: 'ui-icon-circle-plus' } }).click(function(e) {
    for (var i = 0; i< 15; i++) $('button.clone').eq(i).trigger('click')
    return false
  })

  $('button.clear_puck').button({ icons: { primary: 'ui-icon-close' } }).click(function(e) {
    for (var i = 0; i< 16; i++) $('button.delete').eq(i).trigger('click')
    return false
  })  
  
  $('table.samples tr td .top a').button({ icons: { primary: 'ui-icon-triangle-1-n' } })

  $('table.samples tr td button.delete').button({ icons: { primary: 'ui-icon-close' }, text: false }).click(function(e) {
    e.preventDefault();
                                                                                                            
    var idx = $('table.samples tr td button.delete').index($(this))
                                   
    $('select.protein').eq(idx).combobox('value', -1)
    $('input.sname').eq(idx).val('')
    $('select.sg').eq(idx).val('')
    $('input.code').eq(idx).val('')
    $('input.comment').eq(idx).val('')
                                   
    _validate_container()
  })
  
  _get_proteins(function() { _validate_container; _load_cache() })

  $('table.samples tr td button.insert').button({ icons: { primary: 'ui-icon-carat-1-s' }, text: false }).click(function(e) {
    e.preventDefault();
  })
  
  
  // Samples array for plate mode
  var samples = {}
  
  // Switch between container types
  $('select[name=type]').change(function() {
    // Pucks
    if ($(this).val() == 0) {
      $('.plate').hide()
      $('.puck').show()
                                
    // Plates
    } else {
      $('.plate').show()
      $('.puck').hide()
    }
  }).trigger('change')
  
  
  // Generate a confirmation dialog
  function _confirm(t, q, ok_fn) {
    $('.confirm').html(q).dialog('option', 'title', t)
    $('.confirm').dialog('option', 'buttons', {
      'Ok': function() {
        ok_fn()
        $(this).dialog('close');
      },
      'Cancel': function () {
        $(this).dialog('close');
      }
    });

    $('.confirm').dialog('open');
  }
  
  
  // Add a new protein
  function _add_protein(ui,val) {
    var safe = val.replace(/\W/g, '')
    _confirm('Add new protein', 'Do you want to add a new protein called: ' + safe, function() {
      $.ajax({
        url: '/shipment/ajax/addp/name/'+safe,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(pid){
          if (pid) {
            _get_proteins(function() {
              ui.combobox('value', pid)
              _validate_container()
            })
          }
        }
      })
    })
  }
  
  
  // Get protein acronyms
  function _get_proteins(fn,sync) {
    var old = $('select.protein').map(function(i,e) { return $(e).combobox('value') }).get()

    $.ajax({
      url: '/shipment/ajax/pro',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      async: !sync,
      success: function(json){
        var opts = '<option value="-1"></option>'
        $.each(json, function(i,p) {
            opts += '<option value="'+p['PROTEINID']+'">'+p['ACRONYM']+'</option>'
        })
        
        $('select.protein,select.pprotein').html(opts)
           
        $('select.protein,select.pprotein').each(function(i,e) {
            if (old[i] > -1) $(e).combobox('value', old[i])
        })
           
        if (fn) fn()
      }
    })  
  
  }
  
  
  // Clone from previous sample
  function _clone(sel) {
    var sidx = $('button.clone').index(sel)
    var sn = $('input.sname').eq(sidx)
               
    if (sn.val()) {
        var snt = sn.val().replace(/\d+$/, '')
        var nx = $('input.sname').filter(function(i) { return i > sidx && !$(this).val() }).first()
        var rx = new RegExp(snt)
        var nxn = $('input.sname').filter(function() { return snt == ""  ? $(this).val() : $(this).val().match(rx) }).last().val()
      
        var no = nxn.match(/\d+$/)
  
        if (no) no = no.length > 0 ? parseInt(no[0]) : 1
        else no = 1
      
        var nidx = $('input.sname').index(nx)
        $('select.protein').eq(nidx).combobox('value', $('select.protein').eq(sidx).combobox('value'))
        nx.val(snt+(no+1))
        $('select.sg').eq(nidx).val($('select.sg').eq(sidx).val())
      
        _validate_container()
    }
  }
  
  
  // Validate container
  function _validate_container(show_msg) {
    var ret = true, msg
  
    if (!$('input[name=container]').val().match(/^(\w|\-)+$/)) {
        $('input[name=container]').removeClass('fvalid').addClass('ferror')
        ret = false
        msg = 'Your container name is blank, contains special characters and/or spaces'
    } else $('input[name=container]').addClass('fvalid').removeClass('ferror')
  
    $('select.protein').each(function(i,e) {
        var samp = true
        if ($(this).val() > -1) {

          $('input.sname').eq(i).prop('disabled', false).removeClass('disabled')
          $('input.comment').eq(i).prop('disabled', false).removeClass('disabled')
          $('input.code').eq(i).prop('disabled', false).removeClass('disabled')
         
          if (!$('input.sname').eq(i).val().match(/^([\w-])+$/)) {
            $('input.sname').eq(i).removeClass('fvalid').addClass('ferror')
            ret = false
            samp = false
            msg = 'Your sample name is blank, contains special characters and/or spaces. Sample names may only contain letters, numbers, and underscores.'
                             
          } else $('input.sname').eq(i).removeClass('ferror').addClass('fvalid')
                             
          /*if ($('input.comment').eq(i).val() && !$($('input.comment')[i]).val().match(/^[a-zA-Z0-9_ ]+$/)) {
            $('input.comment').eq(i).removeClass('fvalid').addClass('ferror')
            ret = false
            samp = false
            msg = 'Your comment contains special characters. Comments may only contain letters, numbers, spaces, and underscores.'
                             
          } else $('input.comment').eq(i).removeClass('ferror').addClass('fvalid')*/

          if ($('input.code').eq(i).val() && !$($('input.code')[i]).val().match(/^\w+$/)) {
            $('input.code').eq(i).removeClass('fvalid').addClass('ferror')
            ret = false
            samp = false
            msg = 'Your barcode contains special characters. Barcodes may only contain letters and numbers'
                             
          } else $('input.comment').eq(i).removeClass('ferror').addClass('fvalid')
                        
          $(e).parent('td').parent('tr').removeClass('v').removeClass('iv').addClass(samp ? 'v' : 'iv')
                             
        } else {
          $('input.sname').eq(i).prop('disabled', true).addClass('disabled').removeClass('fvalid').removeClass('ferror')
          $('input.comment').eq(i).prop('disabled', true).addClass('disabled').removeClass('fvalid').removeClass('ferror')
          $('input.code').eq(i).prop('disabled', true).addClass('disabled').removeClass('fvalid').removeClass('ferror')
          $(e).parent('td').parent('tr').removeClass('v').removeClass('iv')
        }
    })
  
    if (ret == false && show_msg == true) {
        $('.error .message').html(msg)
        $('.error').dialog('open')
    }
  
    _draw()
  
    return ret
  }
  
  
  
  // Caching
  var cache_thread = null
  $('input.sname,input.comment,input[name=container],input.code,select.sg').bind("change keyup input", _try_cache)
  
  function _try_cache() {
    clearTimeout(cache_thread)
    cache_thread = setTimeout(function() { _cache_container() }, 5000)
  }
  
  // cache container into session
  function _cache_container() {
    //setTimeout(function() { _cache_container() }, 1000*30)
    var has_data = false
  
    $('select.protein').each(function(i,e) {
      if ($(e).val() > -1) has_data = true
    })

    if (!has_data) return
  
  
    if ($('select[name=type]').val() == 0) {
      var fd = {
        protein: $('select.protein').map(function() { return $(this).val() }).get(),
        sname: $('input.sname').map(function() { return $(this).val() }).get(),
        sg: $('select.sg').map(function() { return $(this).val() }).get(),
        code: $('input.code').map(function() { return $(this).val() }).get(),
        comment: $('input.comment').map(function() { return $(this).val() }).get(),
      }
    } else {
      var fd = { samples: samples }
    }
  
    var d = new Date()
    var time = (d.getHours() < 10 ? ('0'+d.getHours()): d.getHours())+':'+(d.getMinutes() < 10 ? ('0'+d.getMinutes()): d.getMinutes())+' '+d.toDateString()
  
    fd.type = $('select[name=type]').val()
    fd.title = $('input[name=container]').val()
    fd.time = time
  
    $.ajax({
      url: '/shipment/ajax/cache/name/container/',
      type: 'POST',
      data: { data: fd },
      dataType: 'json',
      timeout: 5000,
      success: function(success){
        // Last saved message
        $('.messages .saved').html('Container contents last saved: '+time)
        if (!$('.messages .saved').is(':visible')) $('.messages .saved').fadeIn()
      }
    })
  }
  
  
  
  // Load cache from session
  function _load_cache() {
    $.ajax({
      url: '/shipment/ajax/getcache/name/container',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(data){
        if (data && typeof(data) == 'object') {
          if ('title' in data) $('input[name=container]').val(data.title)
           
          // pucks
          if (data.type == 0) {
            if ('protein' in data) $.each(data.protein, function(i,v) {
              $('select.protein').eq(i).combobox('value', v == '' ? -1 : v)
            })
            if ('sname' in data) $.each(data.sname, function(i,v) {
              $('input.sname').eq(i).val(v)
            })
            if ('sg' in data) $.each(data.sg, function(i,v) {
              $('select.sg').eq(i).val(v)
            })
            if ('code' in data) $.each(data.code, function(i,v) {
              $('input.code').eq(i).val(v)
            })
            if ('comment' in data) $.each(data.comment, function(i,v) {
              $('input.comment').eq(i).val(v)
            })
           
          // plates
          } else {
            samples = data.samples
            _draw_plate()
          }
           
          $('.messages .saved').html('Container contents last saved: '+data.time)
          if (!$('.messages .saved').is(':visible')) $('.messages .saved').fadeIn()
           
          _validate_container()
        }
      }
    })
  
  }
  
  
  $('#add_container').submit(function(e) {
    if (_validate_container(true)) {
      $('body').animate({scrollTop: $('.content').offset().top}, 50)
                             
      // submit form via ajax
      if ($('select[name=type]').val() == 0) {
        var fd = {
          p: $('select.protein').map(function(i,e) { return $(this).val() }).get(),
          n: $('input.sname').map(function(i,e) { return $(this).val() }).get(),
          sg: $('select.sg').map(function(i,e) { return $(this).val() }).get(),
          b: $('input.code').map(function(i,e) { return $(this).val() }).get(),
          c: $('input.comment').map(function(i,e) { return $(this).val() }).get(),
        }
      } else {
        var fd = { p: [], n: [], sg: [], b: [], c: [], }
                             
        var max = 0;
        $.each(samples, function(i,s) { if (i > max) max = i })
                             
        for (var i = 0; i < max; i++) {
          if (i in samples) {
            var s = samples[i]
            fd.p.push(s.protein)
            fd.n.push(s.sname)
            fd.sg.push(s.sg)
            fd.b.push('')
            fd.c.push(s.comment)
          } else {
            fd.p.push(-1)
            fd.n.push('')
            fd.sg.push('')
            fd.b.push('')
            fd.c.push('')
          }
                             
        }
                
      }
            
      fd.container = $('input[name=container]').val(),
      fd.type = $('select[name=type]').val()
                             
      $.ajax({
        url: '/shipment/ajax/addcontainer/did/'+did,
        type: 'POST',
        data: fd,
        dataType: 'json',
        timeout: 5000,
        success: function(cid){
          $('<p class="message notify">New container &quot;'+fd.container+'&quot; created. Click <a href="/shipment/cid/'+cid+'">here</a> to view it</div>').hide().appendTo('.messages').toggle('highlight')
          $('button.clear_puck').trigger('click')
          $('input[name=container]').val('')
        },
        error: function() {
          $('<p class="message notify">Something went wrong registering your container, please try again</div>').hide().appendTo('.messages').toggle('highlight')
        }
      })
    }
                             
    e.preventDefault();
  })
  
  
  
  // Pasting contents
  $('.paste').dialog({ autoOpen: false, buttons: { 'Insert': function() { _insert() }, 'Close': function() { $(this).dialog('close') } }, title: 'Paste Container Contents' });
  
  $('button.pf').button().click(function() {
    $('.paste textarea').val('')
    $('.paste').dialog('open')
                                
    return false
  })
  
  function _insert() {
    var lines = $('.paste textarea').val().split('\n')
    $.each(lines, function(i,l) {
        var cols = l.split('\t')
           
        if (cols[2] == 'Puck') {
           $('input[name=container]').val(cols[3])
        }
           
        if (cols[0] > 0 && cols[0] <= 16) {
          if (cols[2]) {
            cols[2] = cols[2].replace(/\W/g, '')
           
            var cb = $('select.protein').eq(cols[0]-1)
            var val = cb.children('option').filter(function() { return $(this).text() == cols[2] }).attr('value');
           
            if (val) cb.combobox('value', val)
            else {
              console.log('adding protein', cols[2])
              var safe = cols[2].replace(/\W/g, '')
              $.ajax({
                url: '/shipment/ajax/addp/name/'+safe,
                type: 'GET',
                dataType: 'json',
                timeout: 5000,
                async: false,
                success: function(pid){
                  if (pid) {
                    _get_proteins(function() {
                      cb.combobox('value', pid)
                    },true)
                  }
                }
              })
            }

          }
           
          $('select.sg').eq(cols[0]-1).val(cols[3])
          $('input.sname').eq(cols[0]-1).val(cols[4])
          $('input.code').eq(cols[0]-1).val(cols[5])
          $('input.comment').eq(cols[0]-1).val(cols[19])
           
          _validate_container()
        }
    })
  
    $('.paste').dialog('close')
  }
  
  
  // Excel style navigation
  //   enter scrolls down a row, shift+enter up
  $.each(['.ui-combobox input', 'input.sname', 'input.comment', 'input.code', 'select.sg'], function(i,el) {
    $(el).keypress(function(e) {
      var idx = $(el).index($(this))
      if(e.which == 13) {
        var dir = e.shiftKey ? -1 : 1
        if (idx < $(el).length) $(el).eq(idx+dir).focus()
                                   
        e.preventDefault()
      }
    })
  })
  
  
  
  
  // Plate plotting
  
  
  
  // Puck plotting
  var centres = [[150,100],
                 [101,136],
                 [120,193],
                 [180,192],
                 [198,135],
                 [150, 40],
                 [90, 59],
                 [49, 105],
                 [40, 168],
                 [65, 224],
                 [119, 256],
                 [181, 257],
                 [234, 224],
                 [259, 167],
                 [251, 105],
                 [210, 59],
                 ]
  
  var canvas = $('.puck canvas')[0]
  var ctx = canvas.getContext('2d')
  
  canvas.width = $('.puck').width()
  canvas.height = $('.puck').width()
  
  var scale = canvas.width/300
  var puck = new Image()
  var hover = null
  
  puck.src = '/templates/images/puck.png'
  puck.onload = function() {
    _draw()
  }
  
  function _draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    var r = 1
    _positions()
    ctx.drawImage(puck, 0, 0, canvas.width, canvas.height)
  }
  
  
  function _circle(x,y,r,c,line) {
    ctx.beginPath()
    ctx.arc(x,y,r, 0, 2*Math.PI, false)
    if (line) {
      ctx.lineWidth = 2
      ctx.strokeStyle = c;
      ctx.stroke()
    } else {
      ctx.fillStyle = c
      ctx.fill()
    }
  }
  
  function _positions() {
    $('table.samples tbody tr').each(function(i,e) {
      if ($(e).hasClass('v') || $(e).hasClass('iv')) {
        var col = $(e).hasClass('v') ? '#82d180' : '#f26c4f'
        _circle(centres[i][0]*scale, centres[i][1]*scale, 28*scale, col)
      }

      if ($(e).hasClass('selected')) {
        _circle(centres[i][0]*scale, centres[i][1]*scale, 23*scale, 'grey', true)
      }
    })
  
    if (hover !== null) {
      _circle(centres[hover][0]*scale, centres[hover][1]*scale, 23*scale, 'grey', true)
    }
  }
  
  $('.puck canvas').click(function(e) {
    var cur = _get_xy(e, this)
                          
    var pos = null
    $.each(centres, function(i,c) {
      var r = 30*scale
      var minx = c[0]*scale - r
      var maxx = c[0]*scale + r
      var miny = c[1]*scale - r
      var maxy = c[1]*scale + r
                                 
      if (cur[0] < maxx && cur[0] > minx && cur[1] < maxy && cur[1] > miny) {
        pos = i
      }
    })
                          
    $('table.samples tbody tr').removeClass('selected').eq(pos).addClass('selected').find('.ui-combobox input').focus()
  })
  
  $('.puck canvas').mousemove(function(e) {
    hover = null
    var cur = _get_xy(e, this)
                          
    var pos = null
    $.each(centres, function(i,c) {
      var r = 30*scale
      var minx = c[0]*scale - r
      var maxx = c[0]*scale + r
      var miny = c[1]*scale - r
      var maxy = c[1]*scale + r
                                 
      if (cur[0] < maxx && cur[0] > minx && cur[1] < maxy && cur[1] > miny) {
        hover = i
      }
    })

    _draw()
  })
  
  
  // Return x,y offset for event
  function _get_xy(e, obj) {
    if (e.offsetX == undefined) {
      return [e.pageX-$(obj).offset().left, e.pageY-$(obj).offset().top]
    } else {
      return [e.offsetX, e.offsetY]
    }
  }
  
  // Set selection on focus
  $('table.samples tbody tr').each(function(i,e) {
    $('input, select', this).focus(function(i,e) {
      $('table.samples tbody tr').removeClass('selected')
      $(this).closest('tr').addClass('selected')
      hover = null
      _draw()
    })
  })
  
})
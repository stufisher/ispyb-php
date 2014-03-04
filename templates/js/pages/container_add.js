$(function() {
  
  $('.error').dialog({ autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } } });
  $('.confirm').dialog({ autoOpen: false, modal: true })
  
  $('select.protein').combobox({invalid: _add_protein, change: _validate_container, select: _validate_container})
  
  $('input.sname,input.comment,input[name=container]').unbind("change keyup input").bind("change keyup input", function() {
    _validate_container()
  })
  
  $('table.samples tr td button.clone').button({ icons: { primary: 'ui-icon-circle-plus' }, text: false }).click(function(e) {
    e.preventDefault();
    _clone($(this))
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
  
  _get_proteins(function() { _validate_container })

  $('table.samples tr td button.insert').button({ icons: { primary: 'ui-icon-carat-1-s' }, text: false }).click(function(e) {
    e.preventDefault();
  })
  
  
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
        
        $('select.protein').html(opts)
           
        $('select.protein').each(function(i,e) {
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
        var nxn = $('input.sname').filter(function() { return $(this).val().match(rx) }).last().val()
      
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
  
  
  $('#add_container').submit(function(e) {
    if (_validate_container(true)) {
      $('input.sname').prop('disabled', false)
      $('input.comment').prop('disabled', false)
      $('input.code').prop('disabled', false)
      return
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
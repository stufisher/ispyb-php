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

  $('table.samples tr td button.delete').button({ icons: { primary: 'ui-icon-close' }, text: false }).click(function(e) {
    e.preventDefault();
  })
  
  _get_proteins(function() { _validate_container })
  
  
  
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
    var safe = val.replace(/\W+/, '')
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
  function _get_proteins(fn) {
    var old = $('select.protein').map(function(i,e) { return $(e).combobox('value') }).get()

    $.ajax({
      url: '/shipment/ajax/pro',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
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
        if ($(this).val() > -1) {

          $('input.sname').eq(i).prop('disabled', false).removeClass('disabled')
          $('input.comment').eq(i).prop('disabled', false).removeClass('disabled')
         
          if (!$('input.sname').eq(i).val().match(/^\w+$/)) {
            $('input.sname').eq(i).removeClass('fvalid').addClass('ferror')
            ret = false
            msg = 'Your sample name is blank, contains special characters and/or spaces. Sample names may only contain letters, numbers, and underscores.'
                             
          } else $('input.sname').eq(i).removeClass('ferror').addClass('fvalid')
                             
          if ($('input.comment').eq(i).val() && !$($('input.comment')[i]).val().match(/^[a-zA-Z0-9_ ]+$/)) {
            $('input.comment').eq(i).removeClass('fvalid').addClass('ferror')
            ret = false
            msg = 'Your comment contains special characters. Comments may only contain letters, numbers, spaces, and underscores.'
                             
          } else $('input.comment').eq(i).removeClass('ferror').addClass('fvalid')
                             
        } else {
          $('input.sname').eq(i).prop('disabled', true).addClass('disabled').removeClass('fvalid').removeClass('ferror')
          $('input.comment').eq(i).prop('disabled', true).addClass('disabled').removeClass('fvalid').removeClass('ferror')
        }
    })
  
    if (ret == false && show_msg == true) {
        $('.error .message').html(msg)
        $('.error').dialog('open')
    }
  
    return ret
  }
  
  
  $('#add_container').submit(function(e) {
    if (_validate_container(true)) return
                             
    e.preventDefault();
  })
  
  
  
  // Pasting contents
  $('.paste').dialog({ autoOpen: false, buttons: { 'Insert': function() { _insert() }, 'Close': function() { $(this).dialog('close') } }, title: 'Paste Container Contents' });
  
  $('button.pf').button().click(function() {
    $('.paste textarea').val('')
    $('.paste').dialog('open')
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
            var cb = $('select.protein').eq(cols[0]-1)
            var val = cb.children('option').filter(function() { return $(this).text() == cols[2] }).attr('value');
           
            if (val) cb.combobox('value', val)
            else {
              var safe = cols[2].replace(/\W+/, '')
              $.ajax({
                url: '/shipment/ajax/addp/name/'+safe,
                type: 'GET',
                dataType: 'json',
                timeout: 5000,
                success: function(pid){
                  if (pid) {
                    _get_proteins(function() {
                      cb.combobox('value', pid)
                    })
                  }
                }
              })
            }

          }
           
          $('select[name^=sg]').eq(cols[0]-1).val(cols[3])
          $('input.sname').eq(cols[0]-1).val(cols[4])
          $('input.comment').eq(cols[0]-1).val(cols[19])
           
          _validate_container()
        }
    })
  
    $('.paste').dialog('close')
  }
  
})
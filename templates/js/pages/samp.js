$(function() {
  var containers = {}
  var shipments = []
  var dewars = []

  $('.error').dialog({ autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } } });  
  $('.confirm').dialog({ autoOpen: false, modal: true })
  $('.add').dialog({ autoOpen: false, buttons: { 'Save': _register_container, 'Cancel': function() { $(this).dialog('close') } } });
  $('.contents').dialog({ autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } } });
  $('select[name=shipment]').combobox({invalid: _add_shipment, select: _load_dewars, change: _validate_container})
  $('select[name=dewar]').combobox({invalid: _add_dewar, change: _validate_container, select: _validate_container})
  
  // Setup beamline puck slots
  function load_blpos() {
    var drop = {
      accept: '.container',
      hoverClass: 'bl_puck_drag',
      drop: _container_drop
    }
  
    var pucks = (bl == 'i04-1' || bl == 'i24') ? 9 : 10
    for (var i = 1; i <= pucks; i++) {
      $('<div id="blp'+i+'" class="bl_puck">'+i+'<div class="ac"></div></div>').droppable(drop).appendTo('#assigned').data('blcid', i)
    }
  
    $('#unassigned').droppable({
        accept: '.bl_puck .ac div',
        hoverClass: 'unassigned_drag',
        drop: _unassigned_drop
    })
  }
  
  
  function _unassigned_drop(e,ui) {
    var t = $('.shipment[sid='+ui.draggable.attr('sid')+']').children('.dewar[did='+ui.draggable.attr('did')+']').children('.containers')
    _confirm('Unassign Container', 'Are you sure you want to unassign this container?', function() {

      ui.draggable.appendTo(t).position({ my: 'left top', at: 'left top', of: t})
      ui.draggable.removeClass('assigned')
  
      // assign db callback
      console.log('remove container id ' + ui.draggable.attr('cid'))
      _unassign_container(ui.draggable.attr('cid'))
    })
  }
  
  
  // Container Drop
  function _container_drop(e,ui) {
    if (ui.draggable.parent().parent().attr('id') != $(this).attr('id')) {
     var p = $(this)
      _confirm('Assign Container', 'Are you sure you want to assign this container? Any other container assigned to this slot will be unassigned', function() {
      if (p.children('div').has('.container').length > 0){
        prev = p.children('div').children('.container')
        t = $('.shipment[sid='+prev.attr('sid')+']').children('.dewar[did='+prev.attr('did')+']').children('.containers')
        prev.appendTo(t).attr('loc', '').removeClass('assigned')
  
        // remove prev db call
        console.log('remove container id ' + prev.attr('cid'))
        _unassign_container(prev.attr('cid'))
        containers[prev.attr('cid')] = 0
      }
  
      p.children('div').html('')
      ui.draggable.appendTo(p.children('div')).position({ my: 'left top', at: 'left top', of: p.children('div')}).addClass('assigned').attr('loc', p.data('blcid'))

  
      // assign db callback & refresh
      console.log('add container id ' + ui.draggable.attr('cid') + ' to ' + bl +'-'+ p.data('blcid'))
      _assign_container(ui.draggable.attr('cid'), p.data('blcid'))
    })
    }
  }
  
  
  function _assign_container(cid, pos) {
    $.ajax({
      url: '/samples/ajax/assign/visit/' + visit + '/cid/' + cid + '/pos/' + pos,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(r){
        _get_containers()
      }
    })
  }
  
  
  function _unassign_container(cid) {
    $.ajax({
      url: '/samples/ajax/unassign/visit/' + visit + '/cid/' + cid,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(r){
        _get_containers()
      }
    })
  }
  
  
  // Retrieve shipments for visit
  function _get_shipments(fn) {
    $.ajax({
      url: '/samples/ajax/ship/visit/' + visit,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var ship_opts = ''
           
        $.each(json, function(i,s) {
            ship_opts += '<option value="'+s['SHIPPINGID']+'">'+s['SHIPPINGNAME']+'</option>';
            if ($.inArray(s['SHIPPINGID'], shipments) == -1) {
               
              $('<div sid="'+s['SHIPPINGID']+'" class="shipment"><h1>'+s['SHIPPINGNAME']+'</h1></div>').hide().appendTo('#unassigned').slideDown()
               
              shipments.push(s['SHIPPINGID'])
            }
        })

        $('select[name=shipment]').html(ship_opts).combobox('value', null)
        if (fn) fn()
        _get_dewars()
      }
    })
  }
  
  
  // Retrieve shipments for visit
  function _get_dewars(fn) {
    $.ajax({
      url: '/samples/ajax/dwr/visit/' + visit,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){   
        $.each(json, function(i,d) {
            if ($.inArray(d['DEWARID'], dewars) == -1) { 
               $('<div did="'+d['DEWARID']+'" class="dewar"><h1>'+d['CODE']+'</h1><div class="containers"></div><div class="clear"></div></div>').hide().appendTo('div[sid='+d['SHIPPINGID']+']').addClass(d['DEWARSTATUS'] == 'processing' ? 'active' : '')
               
               if (d['DEWARSTATUS'] == 'processing') $('div[did='+d['DEWARID']+']').slideDown()
               
              dewars.push(d['DEWARID'])
            }
        })
         
        if (fn) fn()
        _get_containers()
      }
    })  
  }  
  
  
  // Retrieve containers for visit
  function _get_containers() {
    $.ajax({
      url: '/samples/ajax/cnt/visit/' + visit,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
           
        var drag = { 
          containment: '#drag_container',
          stack: '#unassigned div',
          /*cursor: 'move',*/
          /*cancel: 'a',*/
          revert: true
        }
           
        $.each(json, function(i,c) {
            var a = c['SAMPLECHANGERLOCATION']
            //var b = c['BEAMLINELOCATION']
            var assigned = c['SAMPLECHANGERLOCATION'] && (c['BEAMLINELOCATION'] == bl) && (c['DEWARSTATUS'] == 'processing')
                              
            if (!(c['CONTAINERID'] in containers)) {
              sc = $('div[sid='+c['SHIPPINGID']+']').children('div[did='+c['DEWARID']+']').children('div.containers')
               
               $('<div cid="'+c['CONTAINERID']+'" sid='+c['SHIPPINGID']+' did="'+c['DEWARID']+'" loc="'+c['SAMPLECHANGERLOCATION']+'" class="container"><span class="r"><button /></span><h1>'+c['CODE']+'</h1></div>').appendTo(assigned ? ($('#blp'+a).children('div')) : sc).addClass(assigned ? 'assigned' : '').draggable(drag)
              containers[c['CONTAINERID']] = a
               
               
            } else {
               d = $('div[cid='+c['CONTAINERID']+']')
               var state = c['SAMPLECHANGERLOCATION'] == d.attr('loc') && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing'
               if (!state) {
                 if (c['SAMPLECHANGERLOCATION'] && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing') d.appendTo($('#blp'+a).children('div')).addClass('assigned')
                 else d.appendTo($('div[sid='+c['SHIPPINGID']+']').children('div[did='+c['DEWARID']+']').children('div.containers')).removeClass('assigned')
               }
               
            }
        })
           
        map_callbacks()
      }
    })
  }
  
  
  // Retrieve samples for container
  function _get_samples(cid) {
    $.ajax({
      url: '/samples/ajax/smp/visit/'+visit+'/cid/'+cid,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var tab = []
           
        for (var i = 0; i < 16; i++) {
           tab.push('<tr><td>'+(i+1)+'</td><td class="protein_edit"></td><td class="name_edit"></td><td class="comment_edit"></td></tr>')
        }
           
           
        $.each(json, function(i,s) {
            tab[(s['LOCATION']-1)] = '<tr><td>'+s['LOCATION']+'</td><td class="protein_edit">'+s['ACRONYM']+'</td><td class="name_edit">'+s['NAME']+'</td><td class="comment_edit">'+(s['COMMENTS'] == null ? '' : s['COMMENTS'])+'</td></tr>'
        })

        //if (!tab) tab = '<tr><td colspan="3">No samples in this container</td></tr>'
           
        $('.contents').dialog('option', 'title', 'Container: ' + $('.container[cid="'+cid+'"] h1').html())
        $('.contents .samples table tbody').html(tab.join())
           
        /*
        $('.name_edit,.comment_edit').editable(function(v,s) {
            console.log(v,s,this)
            return v
        }, {});
           
        $('.protein_edit').editable(function(v,s) {
            console.log(v,s,this)
            return v
        }, { type: 'select', 'submit': 'Ok',  loadurl: '/samples/ajax/pro/array/1/visit/'+visit+'/' });
        */
           
        $('.contents').dialog('open')
      }
    })  
  }
  
  
  // Add a new container
  function _add_container() {
    var tab = ''
    for (var i = 1; i <= 16; i++) tab += '<tr><td>'+i+'</td><td><select class="protein" name="p'+i+'"></select></td><td><input type="text" class="sname" name="n'+i+'" /></td><td><input type="text" class="comment" name="c'+i+'" /></td><td><button class="clone" /></tr>'
  
    $('.add input[name="title"]').val(visit +'-')
    $('.add .samples table tbody').html(tab)
    $('select.protein').combobox({invalid: _add_protein, change: _validate_container, select: _validate_container})
  
    $('input.sname,input.comment,input[name=title]').unbind("change keyup input").bind("change keyup input", function() {
      _validate_container()
    })
  
    $('.add table tr td button.clone').button({ icons: { primary: 'ui-icon-circle-plus' } }).click(function() { _clone($(this)) } )
  
    _get_proteins(function() { _validate_container })
  
    $('.add').dialog('open')
  }
  
  
  // Clone from previous sample
  function _clone(sel) {
    var sn = sel.parent('td').prev().prev().children('input.sname')
  
    if (sn.val()) {
        var snt = sn.val().replace(/\d+$/, '')
        var nx = $('input.sname').filter(function() { return !$(this).val() }).first()
        var rx = new RegExp(snt)
        var nxn = $('input.sname').filter(function() { return $(this).val().match(rx) }).last().val()
      
        var no = nxn.match(/\d+$/)
  
        if (no) no = no.length > 0 ? parseInt(no[0]) : 1
        else no = 1
      
        nx.parent('td').prev().children('select.protein').combobox('value', sel.parent('td').prev().prev().prev().children('select.protein').combobox('value'))
        nx.val(snt+(no+1))
      
        _validate_container()
    }
  }
  
  
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
        url: '/samples/ajax/addp/visit/'+visit+'/name/'+safe,
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
  
  
  function _load_dewars(fn) {
    var ship = $('select[name=shipment]').combobox('value')
    $.ajax({
      url: '/samples/ajax/dwr/visit/' + visit + '/sid/' + ship,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var dew_opts = ''
        $.each(json, function(i,d) {
          dew_opts += '<option value="'+d['DEWARID']+'">'+d['CODE']+'</option>'
        })
           
        $('select[name=dewar]').html(dew_opts)
        $('select[name=dewar]').combobox('value', null)
           
        _validate_container()
        if (fn) fn()
      }
    })   
  }
  
  
  function _add_shipment(ui, val) {
    var safe = val.replace(/\W+/, '')
    _confirm('Add new shipment', 'Do you want to add a new shipment called: "' + safe + '"', function() {
      $.ajax({
        url: '/samples/ajax/adds/visit/'+visit+'/name/'+safe,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(sid){
          if (sid) {
            _get_shipments(function() {
              ui.combobox('value', sid)
            })
          }
        }
      })
    })
  }
  
  function _add_dewar(ui,val) {
    var safe = val.replace(/\W+/, '')
    _confirm('Add new dewar', 'Do you want to add a new dewar called: "' + safe + '" to the shipment: "' + $('select[name=shipment]').children(':selected').html() + '"', function() {
      $.ajax({
        url: '/samples/ajax/addd/visit/'+visit+'/sid/'+$('select[name=shipment]').combobox('value')+'/name/'+safe,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(did){
          if (did) {
            _get_dewars()
            _load_dewars(function() {
              ui.combobox('value', did)
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
      url: '/samples/ajax/pro/visit/'+visit,
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
  
  
  // Validate container
  function _validate_container(show_msg) {
    var ret = true, msg
  
    if ($('select[name=shipment]').combobox('value') == null) {
        ret = false
        msg = 'You must select a parent shipment for the dewar'
        $('select[name=shipment]').parent('div').addClass('invalid').removeClass('valid')
    } else $('select[name=shipment]').parent('div').addClass('valid').removeClass('invalid')
  
    if ($('select[name=dewar]').combobox('value') == null) {
        ret = false
        msg = 'You must select a parent dewar for the container'
        $('select[name=dewar]').parent('div').addClass('invalid').removeClass('valid')
    } else $('select[name=dewar]').parent('div').addClass('valid').removeClass('invalid')
  
    if (!$('input[name=title]').val().match(/^[a-zA-Z0-9_-]+$/)) {
        $('input[name=title]').parent('div').removeClass('valid').addClass('invalid')
        ret = false
        msg = 'Your container name contains special characters and/or spaces'
    } else $('input[name=title]').parent('div').addClass('valid').removeClass('invalid')
  
    var sc = 0
    $('select.protein').each(function(i,e) {
        //console.log($(e).attr('name'), $(e).combobox('value'))
        if ($(this).combobox('value') > -1) {
          sc++;
          $(this).parent().parent().removeClass('disabled')
          $($('input.sname')[i]).prop('disabled', false)
          $($('input.comment')[i]).prop('disabled', false)
         
          if (!$($('input.sname')[i]).val().match(/^\w+$/)) {
            $(this).parent().parent().removeClass('valid').addClass('invalid')
            ret = false
            msg = 'Your sample name contains special characters and/or spaces. Sample names may only contain letters, numbers, and underscores.'
                             
          } else if ($($('input.comment')[i]).val() && !$($('input.comment')[i]).val().match(/^[a-zA-Z0-9_ ]+$/)) {
            $(this).parent().parent().removeClass('valid').addClass('invalid')
            ret = false
            msg = 'Your comment contains special characters. Comments may only contain letters, numbers, spaces, and underscores.'
                             
          } else $(this).parent().parent().removeClass('invalid').addClass('valid')
                             
        } else {
          $(this).parent().parent().addClass('disabled').removeClass('invalid').removeClass('valid')
          $($('input.sname')[i]).prop('disabled', true)
          $($('input.comment')[i]).prop('disabled', true)
        }
    })
  
    if (sc == 0) {
        ret = false
        msg = 'Your container has no samples defined, please define at least one sample'
    }
  
    if (ret == false && show_msg == true) {
        $('.error').html(msg)
        $('.error').dialog('open')
    }
  
    return ret
  }
  
  
  // Register container
  function _register_container() {
    if (_validate_container(true)) {
      var prs = $('select.protein').map(function() { return $(this).combobox('value') }).get()
      var sns = $('input.sname').map(function() { return $(this).val() }).get()
      var cms = $('input.comment').map(function() { return $(this).val() }).get()
  
      var samples = []
      $.each(prs, function(i,p) {
             if (p > -1) samples.push({id: i, protein: p, name: sns[i], comment: cms[i], sg: ''})
      })
  
      var data = {
        shipment: $('select[name=shipment]').combobox('value'),
        dewar: $('select[name=dewar]').combobox('value'),
        container: $('input[name=title]').val(),
        samples: samples,
      }
  
      $.ajax({
        url: '/samples/ajax/rc/visit/'+visit,
        type: 'POST',
        dataType: 'json',
        data: data,
        timeout: 5000,
        success: function(r){
          if (r) $('.add').dialog('close')
          else {
             $('.error').html('There was an error registering your container')
             $('.error').dialog('open')
          }
          console.log('saved container to db')
          _get_shipments()
        }
  
      })
    }
  }
  
  
  // Map callbacks to containers
  function map_callbacks() {
    $('.container a').unbind('click').click(function() {
      _get_samples($(this).parent().parent().attr('cid'))
      $('.contents').dialog('open')
      return false
    })
  
    $('.shipment > h1').unbind('click').click(function() {
      $(this).siblings('.dewar').slideToggle()
      return false
    })
  
    $('.container span button').button({ icons: { primary: 'ui-icon-search' } }).unbind('click').click(function() {
      _get_samples($(this).parent().parent().attr('cid'))
      $('.contents').dialog('open')
    })

  }
  
  
  load_blpos()
  _get_shipments()
  
  
  // Bind links / buttons
  $('#add').button({ icons: { primary: 'ui-icon-plusthick' } }).click(function() {
    _add_container()
  })
  
  $('.add span a').click(function() {
    $('.add_protein').dialog('open')
    return false
  })
  
});

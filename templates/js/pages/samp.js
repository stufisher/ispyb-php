$(function() {
  var containers = {}
  var shipments = []
  var dewars = []

  var ship = 1
  
  $('.error').dialog({ autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } } });  
  $('.confirm').dialog({ autoOpen: false, modal: true })
  

  // Setup beamline puck slots
  function load_blpos() {
    var drop = {
      accept: '.container',
      hoverClass: 'bl_puck_drag',
      drop: _container_drop
    }
  
    var pucks = (bl == 'i04-1' || bl == 'i24') ? 9 : (bl == 'i03' ? 23 : 10)
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

      if (t.length) {
        ui.draggable.appendTo(t).position({ my: 'left top', at: 'left top', of: t})
        ui.draggable.removeClass('assigned')
      } else ui.draggable.remove()
  
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

      // set dewar as active
      $('div.dewar[did='+ui.draggable.attr('did')+']').addClass('active')
               
  
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
      url: '/samples/ajax/ship/visit/'+visit+'/pp/5'+(ship ? ('/p/'+ship) : ''),
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pgs = []
        var pc = json[0] == 0 ? 1 : json[0]
        for (var i = 0; i < pc; i++) pgs.push('<li><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
        $('.pages').html('<ul>'+pgs.join('')+'</ul>')
        $('.pages li').removeClass('selected').unbind('click').click(function() {
          ship = parseInt($(this).children('a').attr('href').replace('#', ''))
                                                                     
          $('#unassigned').empty()
          shipments = []
          dewars = []
          containers = {}
          $('#assigned .container').each(function(i,e) { containers[$(this).attr('cid')] = 0 })
                                                                     
          _get_shipments()
        }).eq(ship-1).addClass('selected')
           
        var ship_opts = ''
        $.each(json[1], function(i,s) {
            ship_opts += '<option value="'+s['SHIPPINGID']+'">'+s['SHIPPINGNAME']+'</option>';
            if ($.inArray(s['SHIPPINGID'], shipments) == -1) {
              $('<div sid="'+s['SHIPPINGID']+'" class="shipment"><h1>'+s['SHIPPINGNAME']+(s['CREATED'] ? (' [Created: '+s['CREATED']+']') : '')+'</h1></div>').hide().appendTo('#unassigned').slideDown()
               
              shipments.push(s['SHIPPINGID'])
            }
        })

        $('select[name=shipment]').html(ship_opts).combobox('value', null)
        if (fn) fn()
        _get_dewars()
      }
    })
  }
  
  
  // Retrieve dewars for visit
  function _get_dewars(fn) {
    $.ajax({
      url: '/samples/ajax/dwr/visit/' + visit,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){   
        $.each(json, function(i,d) {
            if ($.inArray(d['DEWARID'], dewars) == -1) { 
              $('<div did="'+d['DEWARID']+'" class="dewar"><h1 class="clearfix">'+d['CODE']+'<span class="r deactivate"><button class="deact">Deactivate Dewar</button></span></h1><div class="containers clearfix"></div></div>').hide().appendTo('div.shipment[sid='+d['SHIPPINGID']+']').addClass(d['DEWARSTATUS'] == 'processing' ? 'active' : '').slideDown()
               
              // if (d['DEWARSTATUS'] == 'processing' || 1) $('div[did='+d['DEWARID']+']').slideDown()
               
              dewars.push(d['DEWARID'])
            }
        })
           
        $('div.dewar button.deact').button({ icons: { primary: 'ui-icon-cross' } }).unbind('click').click(function() {
          var d = $(this).closest('div.dewar')
          $.ajax({
            url: '/samples/ajax/deact/visit/'+visit+'/did/'+d.attr('did'),
            type: 'GET',
            dataType: 'json',
            timeout: 5000,
            success: function(json){
              d.removeClass('active')
              _get_dewars()
            }
          })
          return false
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
               
              if (!assigned || (assigned && !$('#blp'+a).children('div').children('div[cid='+c['CONTAINERID']+']').length)) {
               
                $('<div cid="'+c['CONTAINERID']+'" sid='+c['SHIPPINGID']+' did="'+c['DEWARID']+'" loc="'+c['SAMPLECHANGERLOCATION']+'" class="container"><span class="r"><a title="Click to view container contents" href="/shipment/cid/'+c['CONTAINERID']+'">View Container</a></span><h1>'+c['CODE']+'</h1></div>').appendTo(assigned ? ($('#blp'+a).children('div')) : sc).addClass(assigned ? 'assigned' : '').draggable(drag)
                containers[c['CONTAINERID']] = a
              }
               
               
            } else {
               d = $('div[cid='+c['CONTAINERID']+']')
               var state = c['SAMPLECHANGERLOCATION'] == d.attr('loc') && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing'
               if (!state) {
               if (c['SAMPLECHANGERLOCATION'] && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing') {
                   d.appendTo($('#blp'+a).children('div')).addClass('assigned')
               } else d.appendTo($('div[sid='+c['SHIPPINGID']+']').children('div[did='+c['DEWARID']+']').children('div.containers')).removeClass('assigned')
               }
               
            }
        })
           
        map_callbacks()
      }
    })
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
  
  
  // Map callbacks to containers
  function map_callbacks() {
    //$('.shipment > h1').unbind('click').click(function() {
    //  $(this).siblings('.dewar').slideToggle()
    //  return false
    //})
  
    $('.container a').button({ icons: { primary: 'ui-icon-search' }, text: false })
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

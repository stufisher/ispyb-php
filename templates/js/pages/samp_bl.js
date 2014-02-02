var shipments = [];
var dewars = [];
var containers = [];
var ref = null

$(document).on('pageinit', '#allocation', function() {
  //setTimeout(function() { $.mobile.changePage('/samples/bl') }, 600000)
  setTimeout(function() { window.location.href='/samples/bl' }, 600000) //600000)
            
  function _refresh() {
    console.log('refresing shipments')
    _get_shipments()
    ref = setTimeout(_refresh, 10000)
  }
               
  clearTimeout(ref)
  _refresh()
               
  jsKeyboard.init('virtualKeyboard');
  var c = null
       
  $('.pos h3 a').click(function (event) {
        return false;
  });
   
  /*
  $(document).on('pagebeforeshow', '#allocation', function() {
    clearTimeout(ref)
    _refresh()
  })*/
               

  function _load_dewars(fn) {
    var ship = $('#registration select[name=shipment]').val()
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
           
        $('#registration select[name=dewar]').html(dew_opts).selectmenu('refresh')

        if (fn) fn()
      }
    })   
  }
               
               
  $('#registration select[name=shipment]').change(function() {
    _load_dewars(_validate_container)
  })
               
               
  // Add Protein
  $('#add_protein .submit').bind('click', function(e) {
    var safe = $('#add_protein input[name=protein]').val().replace(/\W+/, '')
      $.ajax({
        url: '/samples/ajax/addp/visit/'+visit+'/name/'+safe,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(pid){
          if (pid) {
            _get_proteins()
          }
        }
      })
    $('#add_protein').dialog('close')
  })
               


               
  // Assign a container to sample changer
  $('#assign .submit').bind('click', function(e) {
    var pos = $('#assign input[name=position]:checked').val()
    if (c != null) {
                            
      _unassign_pos(pos)
                            
      $.ajax({
        url: '/samples/ajax/assign/visit/' + visit + '/cid/' + c + '/pos/' + pos,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(r){
          _get_containers()
        }
      })
    }
    $('#assign').dialog('close')
  })

               
  // Unassign existing container
  function _unassign_pos(pos) {
    if ($('.blp'+pos+' li').length) {
      $.ajax({
          url: '/samples/ajax/unassign/visit/' + visit + '/cid/' + $('.blp'+pos+' li').attr('cid'),
          type: 'GET',
          dataType: 'json',
          timeout: 5000,
          success: function(r){
          _get_containers()
          }
      })
    }
  }
               
  // Unassign a container from sample changer
  $('#unassign .submit').bind('click', function(e) {
    if (c != null) {
      $.ajax({
        url: '/samples/ajax/unassign/visit/' + visit + '/cid/' + c,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(r){
          _get_containers()
        }
      })
    }
    $('#unassign').dialog('close')
  })
     
  function map_callbacks() {
      $('.container a.assign').unbind('click').bind('click', function() {
        c = $(this).parent().parent().parent('li').attr('cid')
        var n = $(this).find('.ui-li-heading').html()
        $('#assign .container').html(n)
      })

      $('.assigned a.unassign').unbind('click').bind('click', function() {
        c = $(this).parent().parent().parent('li').attr('cid')
        var n = $(this).find('.ui-li-heading').html()
        $('#unassign .container').html(n)
      })
               
      $('.assigned a.view, .container a.view').unbind('click').bind('click', function() {
        c = $(this).parent('li').attr('cid')
        var n = $(this).parent('li').find('.ui-li-heading').html()
        $('#view .name').html(n)
      })
  }
               
               
  // Retrieve shipments for visit
  function _get_shipments(fn) {
    $.ajax({
      url: '/samples/ajax/ship/visit/'+visit+'/pp/5',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var ship_opts = ''
           
        $.each(json[1], function(i,s) {
            ship_opts += '<option value="'+s['SHIPPINGID']+'">'+s['SHIPPINGNAME']+'</option>';
               
            if ($.inArray(s['SHIPPINGID'], shipments) == -1) {
              $('<div data-role="collapsible" data-content-theme="d" sid="'+s['SHIPPINGID']+'">'+
                '<h3>'+s['SHIPPINGNAME']+'</h3>'+
                '<div data-role="collapsible-set" class="shipment"></div>'+
                '</div>').appendTo('#shipments')
               
              $('div[sid='+s['SHIPPINGID']+'] .shipment').collapsibleset()
               
              // Allow multiple shipments to be open
              $('div[sid='+s['SHIPPINGID']+']').bind('expand', function (e) {
                e.stopPropagation()
              }).bind('collapse', function (e) {
                e.stopPropagation()
              });
               
              shipments.push(s['SHIPPINGID'])
            }
        })
           
        $('#shipments').collapsibleset('refresh');
        $('#registration select[name=shipment]').html(ship_opts)
           
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
              $('<div data-role="collapsible" data-content-theme="d" did="'+d['DEWARID']+'">'+
                '<h3>'+d['CODE']+'</h3>'+
                '<ul data-role="listview" data-inset="true" class="responsive dewar"></ul>'+
                '<div class="clear"></div>'+
                '</div>').prependTo('div[sid='+d['SHIPPINGID']+'] div.shipment')
               
               if (d['DEWARSTATUS'] == 'processing') {
                 $('div[did="'+d['DEWARID']+'"]').attr('data-collapsed', 'false').attr('data-theme', 'a').children('.dewar').attr('data-theme', 'b')
                 $('div[sid='+d['SHIPPINGID']+']').trigger('expand')
               }
               
               $('div[did='+d['DEWARID']+'] .dewar').listview()
               
              dewars.push(d['DEWARID'])
            }
        })
         
        $('.shipment').collapsibleset('refresh');
        
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
           
        $.each(json, function(i,c) {
            var a = c['SAMPLECHANGERLOCATION']
            var assigned = c['SAMPLECHANGERLOCATION'] && (c['BEAMLINELOCATION'] == bl) && (c['DEWARSTATUS'] == 'processing')
                              
            if (!(c['CONTAINERID'] in containers)) {
              sc = $('div[did='+c['DEWARID']+'] .dewar')
               
              $('<li cid="'+c['CONTAINERID']+'" sid='+c['SHIPPINGID']+' did="'+c['DEWARID']+'" loc="'+c['SAMPLECHANGERLOCATION']+'" class="container">'+
                '<a href="#'+(assigned ? 'unassign' : 'assign')+'" class="'+(assigned ? 'unassign' : 'assign')+'" data-rel="dialog"><h3>'+c['CODE']+'</h3></a>'+
                '<a href="#view" class="view">View</a>'+
                '</li>').appendTo(assigned ? $('.blp'+a) : sc).addClass(assigned ? 'assigned' : '')
              containers[c['CONTAINERID']] = a
               
               
            } else {
               d = $('li[cid='+c['CONTAINERID']+']')
               var state = c['SAMPLECHANGERLOCATION'] == d.attr('loc') && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing'
               if (!state) {
                 if (c['SAMPLECHANGERLOCATION'] && c['BEAMLINELOCATION'] == bl && c['DEWARSTATUS'] == 'processing') d.appendTo($('.blp'+a)).addClass('assigned').find('a.assign').attr('href', '#unassign').addClass('unassign').removeClass('assign')
                 else d.appendTo($('div[did='+c['DEWARID']+'] .dewar')).removeClass('assigned').find('a.unassign').attr('href', '#assign').addClass('assign').removeClass('unassign')
               }
               
            }
        })

        $('.dewar').listview('refresh')
        $('.blp').listview('refresh');
        //$('.assigned div').trigger('expand')
        map_callbacks()
      }
    })
  }
       

  $(document).on('pagebeforeshow', '#registration', function(e) {
    $('#registration input[name=container]').val('')
    $('#registration select[name=pos]').val(-1)
    $('#registration input.sname').val('')
    $('#registration input.comment').val('')
    $('#registration select.protein').val(-1)
                 
    _get_proteins(_validate_container)
  })
               
  $(document).on('pageshow', '#registration', function(e) {
    var cont = $('#registration input[name=container]')
    $('#registration [data-role=footer]').fixedtoolbar({ hideDuringFocus: '', tapToggle: false });
                 
    cont.focus()
    jsKeyboard.currentElement = cont
    jsKeyboard.currentElementCursorPosition = 0;
                 
    $('#virtualKeyboard td.button').unbind('click').click(function(e) {
      e.preventDefault()
    })
  })

               
  // Get samples for a container
  $(document).on('pagebeforeshow', '#view', function(e) {
    if (c != null)
    $.ajax({
      url: '/samples/ajax/smp/visit/'+visit+'/cid/'+c,
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

        $('#view table tbody').html(tab.join())
        //$('#view table').table('refresh')
      }
    })
  })
               

  // Get protein acronyms
  function _get_proteins(fn) {
    var old = $('select.protein').map(function(i,e) { return $(e).val() }).get()

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
            if (old[i] > -1) $(e).val(old[i])
        })
           
        $('select.protein').selectmenu('refresh')
        if (fn) fn()
      }
    })  
  
  }
  
               

  $('#registration a.clone').bind('click', function() { _clone($(this)) })
  
  // Clone from previous sample
  function _clone(sel) {
    var sidx = $('a.clone').index(sel)
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
        $('select.protein').eq(nidx).val($('select.protein').eq(sidx).val()).selectmenu('refresh')
        nx.val(snt+(no+1))
      
        _validate_container()
    }
  }
               

  // Delete a sample from table
  $('#registration a.delete').bind('click', function() {
    var idx = $('a.delete').index($(this))
                                   
    $('select.protein').eq(idx).val(-1).selectmenu('refresh')
    $('input.sname').eq(idx).val('')
    $('input.comment').eq(idx).val('')
                                   
    _validate_container()
  })
               
               
               
  $('input.sname,input.comment,input[name=container]').unbind("change keyup input").bind("change keyup input", function() {
    _validate_container()
  })
               
  $('select.protein').change(function() {
    _validate_container()
    //var sn = $('input.sname').eq($('select.protein').index($(this)))
    //sn.focus()
    //jsKeyboard.currentElement = sn
    //jsKeyboard.currentElementCursorPosition = 0;
  })
               
  // Validate container
  function _validate_container(show_msg) {
    var ret = true, msg
  
    if (!$('input[name=container]').val().match(/^[a-zA-Z0-9_-]+$/)) {
        $('input[name=container]').parent('div').removeClass('valid').addClass('invalid')
        ret = false
        msg = 'Your container name contains special characters and/or spaces'
    } else $('input[name=container]').parent('div').addClass('valid').removeClass('invalid')
  
    $('select.protein').each(function(i,e) {
        if ($(this).val() > -1) {

          $('input.sname').eq(i).prop('disabled', false).parent('div').removeClass('disabled')
          $('input.comment').eq(i).prop('disabled', false).parent('div').removeClass('disabled')
         
          if (!$('input.sname').eq(i).val().match(/^\w+$/)) {
            $('input.sname').eq(i).parent('div').removeClass('valid').addClass('invalid')
            ret = false
            msg = 'Your sample name contains special characters and/or spaces. Sample names may only contain letters, numbers, and underscores.'
                             
          } else $('input.sname').eq(i).parent('div').removeClass('invalid').addClass('valid')
                             
          if ($('input.comment').eq(i).val() && !$($('input.comment')[i]).val().match(/^[a-zA-Z0-9_ ]+$/)) {
            $('input.comment').eq(i).parent('div').removeClass('valid').addClass('invalid')
            ret = false
            msg = 'Your comment contains special characters. Comments may only contain letters, numbers, spaces, and underscores.'
                             
          } else $('input.comment').eq(i).parent('div').removeClass('invalid').addClass('valid')
                             
        } else {
          $('input.sname').eq(i).prop('disabled', true).parent('div').addClass('disabled').removeClass('valid').removeClass('invalid')
          $('input.comment').eq(i).prop('disabled', true).parent('div').addClass('disabled').removeClass('valid').removeClass('invalid')
        }
    })
  
    if (ret == false && show_msg == true) {
        $('#error .message').html(msg)
        $.mobile.changePage('#error', {transition: 'pop', role: 'dialog'});
    }
  
    return ret
  }
  

  // Register new container
  $('#registration .submit').click(function() {
    if(_validate_container(true)) {
      var prs = $('select.protein').map(function() { return $(this).val() }).get()
      var sns = $('input.sname').map(function() { return $(this).val() }).get()
      var cms = $('input.comment').map(function() { return $(this).val() }).get()
  
      var samples = []
      $.each(prs, function(i,p) {
             if (p > -1) samples.push({id: i, protein: p, name: sns[i], comment: cms[i], sg: ''})
      })
  
      var data = {
        //shipment: $('select[name=shipment]').val(),
        //dewar: $('select[name=dewar]').val(),
        container: $('input[name=container]').val(),
        pos: $('#registration select[name=pos]').val(),
        samples: samples,
      }
    
      // Unassign existing position
      if ($('#registration select[name=pos]').val() > -1) {
        _unassign_pos($('#registration select[name=pos]').val())
      }
                                   
      $.ajax({
        url: '/samples/ajax/rc/visit/'+visit,
        type: 'POST',
        dataType: 'json',
        data: data,
        timeout: 5000,
        success: function(r){
          if (r) {
            _get_shipments(function() { $.mobile.changePage('#allocation') })
          }else {
            $('#error .message').html('There was an error registering your container')
            $.mobile.changePage('#error', {transition: 'pop', role: 'dialog'});
          }
        }
  
      })
    }
  })
               
})
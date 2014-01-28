$(function() {
  
  $('a.label').button({ icons: { primary: 'ui-icon-print' } })
  
  // Bind links / buttons
  $('#add_dewar').button({ icons: { primary: 'ui-icon-plusthick' } }).click(function() {
    if ($('table.dewars tbody tr.new').length) return
    
    var vals = '';
    $.ajax({
        url: '/shipment/ajax/vis',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           
            var vals = '<option value=""></option>'
            $.each(json, function(k,e) {
               vals += '<option value="'+k+'">'+e+'</option>'
            })
                                                                            
            $('<tr class="new">'+
              '<td><input name="code" /></td>'+
              '<td>&nbsp;</td>'+
              '<td><input name="fcode" /></td>'+
              '<td><select name="exp">'+vals+'</select></td>'+
              '<td><input name="trackto" /></td>'+
              '<td><input name="trackfrom" /></td>'+
              '<td>&nbsp;</td>'+
              '<td>&nbsp;</td>'+
              '<td>&nbsp;</td>'+
              '<td><button class="save">Save Dewar</button> <button class="cancel">Cancel</button></td>'+
              '</tr>').appendTo('table.dewars tbody')
                         
                                    
            // Add a new dewar
            $('button.save').button({ icons: { primary: 'ui-icon-check' }, text: false }).click(function() {
                if (!/^(\w|\-)+$/.test($('.new input[name=code]').val())) {
                   $('.new input[name=code]').addClass('ferror')
                   return
                } else $('.new input[name=code]').removeClass('ferror')
                                                                                   
                $.ajax({
                    url: '/shipment/ajax/addd/sid/'+sid,
                    type: 'POST',
                    data: { code: $('.new input[name=code]').val(),
                            trackto: $('.new input[name=trackto]').val(),
                            trackfrom: $('.new input[name=trackfrom]').val(),
                            exp: $('.new select[name=exp]').val(),
                            fcode: $('.new select[name=fcode]').val(),
                    },
                    dataType: 'json',
                    timeout: 5000,
                    success: function(json){
                       _get_dewars()
                    }
                })
            })
                                                                                    
            // Cancel adding a dewar
            $('button.cancel').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).unbind('click').click(function() {
                $(this).parent('td').parent('tr').remove()
            })
        }
           
    })
  })
  
  
  $('.dewars tbody tr').click(function() {
    _load_dewar($(this).attr('did'))
  })
  
  
  // Get dewars list
  function _get_dewars() {
    $.ajax({
        url: '/shipment/ajax/dewars/sid/'+sid,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          var d_out = ''
          $.each(json, function(i,d) {
            d_out += '<tr did="'+d['DEWARID']+'" name="'+d['CODE']+'">'+
                    '<td title="Click to edit the dewar name"><span class="code">'+d['CODE']+'</span></td>'+
                    '<td>'+d['BARCODE']+'</td>'+
                    '<td><span class="facilitycode">'+(d['FACILITYCODE'] ? d['FACILITYCODE'] : '')+'</span></td>'+
                    '<td><span class="exp">'+(d['EXP'] ? d['EXP'] : '')+'</span></td>'+
                    '<td><span class="trackto">'+(d['TRACKINGNUMBERTOSYNCHROTRON'] ? d['TRACKINGNUMBERTOSYNCHROTRON'] : '')+'</span>'+(courier in tracking && d['TRACKINGNUMBERTOSYNCHROTRON'] ?  (' <a class="track" href="'+tracking[courier]+d['TRACKINGNUMBERTOSYNCHROTRON']+'">Track</a>'): '')+'</td>'+
                    '<td><span class="trackfrom">'+(d['TRACKINGNUMBERFROMSYNCHROTRON'] ? d['TRACKINGNUMBERFROMSYNCHROTRON'] : '')+'</span>'+(courier in tracking && d['TRACKINGNUMBERFROMSYNCHROTRON'] ?  (' <a class="track" href="'+tracking[courier]+d['TRACKINGNUMBERFROMSYNCHROTRON']+'">Track</a>'): '')+'</td>'+
                    '<td>'+d['DEWARSTATUS']+'</td>'+
                    '<td>'+(d['STORAGELOCATION'] ? d['STORAGELOCATION'] : 'Unknown')+'</td>'+
                    '<td>'+d['CCOUNT']+'</td>'+
                    '<td><a class="add" title="Click to add a container" href="/shipment/addc/did/'+d['DEWARID']+'">Add Container</a></td>'+
                '</tr>'
          })
           
          if (!json.length) d_out = '<tr><td colspan="8">No dewars in this shipment</td></tr>'
           
          $('table.dewars tbody').html(d_out)
          
          $('a.add').button({ icons: { primary: 'ui-icon-plus' }, text: false })
          $('a.track').button({ icons: { primary: 'ui-icon-extlink' }, text: false })
           
          $('.dewars tbody tr').unbind('click').click(function() {
            _load_dewar($(this).attr('did'))
          })
         
          _load_dewar($('.dewars tbody tr').eq(0).attr('did'))
        }
           
    })
  }
  
  _get_dewars()
  
  
  // Load dewar details
  function _load_dewar(did) {
   // var name = $('[did='+did+']').children('td').eq(0).children('span').html()
    $('.dewar_name').html($('[did='+did+']').attr('name'))
  
    $.ajax({
        url: '/shipment/ajax/did/'+did,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          var c_out = ''
          $.each(json, function(i,c) {
            c_out += '<li cid="'+c['CONTAINERID']+'">'+c['CODE']+' ('+c['SCOUNT']+' samples) <span class="r"><a class="view" title="Click to View Container" href="/shipment/cid/'+c['CONTAINERID']+'">View Container</a></span></li>'
            //<button class="delete">Delete Container</button>
                 
          })
           
          if (!c_out) c_out = '<li>No containers in this dewar</li>'
           
          $('.containers').html(c_out)
           
          _map_callbacks()
        }
    })
  
    $.ajax({
        url: '/shipment/ajax/history/did/'+did,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          var h_out = ''
           
          $.each(json, function(i,h) {
            h_out += '<tr>'+
                    '<td>'+h['ARRIVAL']+'</td>'+
                    '<td>'+h['DEWARSTATUS']+'</td>'+
                    '<td>'+(h['STORAGELOCATION'] ? h['STORAGELOCATION'] : '')+'</td>'+
                 '</tr>'
                 
          })
           
          if (!h_out) h_out = '<tr><td colspan="3">No history for this dewar</td></tr>'
           
          $('.history tbody').html(h_out)
        }
    })
  }
  
  
  
  // Editables
  $('.title').editable('/shipment/ajax/update/sid/'+sid+'/ty/title/', {
                       width: '180px',
                       height: '25px',
                       type: 'text',
                       submit: 'Ok',
                       style: 'display: inline',
                       
                       onsubmit: function(s,td) {
                         $(this).validate({
                            validClass: 'fvalid', errorClass: 'ferror',
                            errorElement: 'span',
                            rules: { value: { wwsdash: true }}
                         })
                         return $(this).valid();
                       },
                       }).addClass('editable');
  
  $('.lcout').editable('/shipment/ajax/update/sid/'+sid+'/ty/lcout/', {
                       loadurl: '/shipment/ajax/lc/',
                       type: 'select',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');
  
  $('.lcret').editable('/shipment/ajax/update/sid/'+sid+'/ty/lcret/', {
                       loadurl: '/shipment/ajax/lc/',
                       type: 'select',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');

  $('.safety').editable('/shipment/ajax/update/sid/'+sid+'/ty/safety/', {
                       data: {'Green': 'Green', 'Yellow':'Yellow', 'Red': 'Red'},
                       type: 'select',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');
  
  $('.courier').editable('/shipment/ajax/update/sid/'+sid+'/ty/cour/', {
                       width: '100px',
                       height: '20px',
                       type: 'text',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');

  $('.courierac').editable('/shipment/ajax/update/sid/'+sid+'/ty/courac/', {
                       width: '100px',
                       height: '20px',
                       type: 'text',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');
  
  $('.shippingdate').editable('/shipment/ajax/update/sid/'+sid+'/ty/sd/', {
                       type: 'datepicker',
                       submit: 'Ok',
                       style: 'display: inline',
                       onblur: 'ignore',
                       datepicker: { dateFormat: 'dd-mm-yy' },
                       }).addClass('editable');
  
  $('.deliverydate').editable('/shipment/ajax/update/sid/'+sid+'/ty/dd/', {
                       type: 'datepicker',
                       submit: 'Ok',
                       style: 'display: inline',
                       onblur: 'ignore',
                       datepicker: { dateFormat: 'dd-mm-yy' },
                       }).addClass('editable');
  
  $('.comment').editable('/shipment/ajax/update/sid/'+sid+'/ty/com/', {
        type: 'textarea',
        rows: 5,
        submit: 'Ok',
        onblur: 'ignore',
  }).addClass('editable');
  
  
  
  function _map_callbacks() {
      $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
       
      $('button.delete').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).unbind('click').click(function() {
      })
  
  
      $('.code').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/code/', {
                           width: '100px',
                           height: '100%',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })

      $('.exp').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/exp/', {
                           loadurl: '/shipment/ajax/vis/',
                           type: 'select',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
  
      $('.trackto').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/tt/', {
                           width: '100px',
                           height: '100%',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
      
      $('.trackfrom').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/tf/', {
                           width: '100px',
                           height: '100%',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
  
      $('.facilitycode').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/fc/', {
                           width: '100px',
                           height: '100%',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
  }
})
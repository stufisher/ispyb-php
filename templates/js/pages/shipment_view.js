$(function() {
  
    // Bind links / buttons
  $('#add_dewar').button({ icons: { primary: 'ui-icon-plusthick' } }).click(function() {
    if ($('table.dewars tbody tr.new').length) return
                                                                            
    $('<tr class="new">'+
      '<td><input name="code" /></td>'+
      '<td>&nbsp;</td>'+
      '<td><input name="trackto" /></td>'+
      '<td><input name="trackfrom" /></td>'+
      '<td colspan="3">&nbsp;</td>'+
      '<td><button class="save"></button> <button class="cancel"></button></td>'+
      '</tr>').appendTo('table.dewars tbody')
                 
                            
    // Add a new dewar
    $('button.save').button({ icons: { primary: 'ui-icon-check' } }).click(function() {
        $.ajax({
            url: '/shipment/ajax/addd/sid/'+sid,
            type: 'POST',
            data: { code: $('.new input[name=code]').val(),
                    trackto: $('.new input[name=trackto]').val(),
                    trackfrom: $('.new input[name=trackfrom]').val(),
            },
            dataType: 'json',
            timeout: 5000,
            success: function(json){
               _get_dewars()
            }
        })
    })
                                                                            
    // Cancel adding a dewar
    $('button.cancel').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
        $(this).parent('td').parent('tr').remove()
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
            d_out += '<tr did="'+d['DEWARID']+'">'+
                    '<td><span class="code">'+d['CODE']+'</span></td>'+
                    '<td>'+d['BARCODE']+'</td>'+
                    '<td><span class="trackto">'+(d['TRACKINGNUMBERTOSYNCHROTRON'] ? d['TRACKINGNUMBERTOSYNCHROTRON'] : '')+'</span></td>'+
                    '<td><span class="trackfrom">'+(d['TRACKINGNUMBERFROMSYNCHROTRON'] ? d['TRACKINGNUMBERFROMSYNCHROTRON'] : '')+'</span></td>'+
                    '<td>'+d['DEWARSTATUS']+'</td>'+
                    '<td>'+(d['STORAGELOCATION'] ? d['STORAGELOCATION'] : '')+'</td>'+
                    '<td>'+d['CCOUNT']+'</td>'+
                    '<td><a class="small add" title="Add Container" href="/shipment/addc/did/'+d['DEWARID']+'"></a></td>'+
                '</tr>'
          })
           
          if (!json.length) d_out = '<tr><td colspan="8">No dewars in this shipment</td></tr>'
           
          $('table.dewars tbody').html(d_out)
          
          $('a.add').button({ icons: { primary: 'ui-icon-plus' } })
           
          $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).unbind('click').click(function() {
            window.location.href='/shipment/addc/did/'+$(this).parent('td').parent('tr').attr('did')
          })
           
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
    var name = $('[did='+did+']').children('td').eq(0).children('span').html()
    $('.dewar_name').html(name)
  
    $.ajax({
        url: '/shipment/ajax/did/'+did,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          var c_out = ''
          $.each(json, function(i,c) {
            c_out += '<li cid="'+c['CONTAINERID']+'">'+c['CODE']+' ('+c['SCOUNT']+' samples) <span class="r"><a class="small view" title="View Container" href="/shipment/cid/'+c['CONTAINERID']+'"></a> <button class="small delete"></button></span></li>'
                 
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
        style: 'display: inline',
        width: '40%',
        height: '100%',
        submit: 'Ok',
        onblur: 'ignore',
  }).addClass('editable');
  
  
  
  function _map_callbacks() {
      $('a.view').button({ icons: { primary: 'ui-icon-search' } })
      $('a.delete').button({ icons: { primary: 'ui-icon-closethick' } })
       
      $('button.delete').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
      })
  
  
      $('.code').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/code/', {
                           width: '100px',
                           height: '20px',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })

      $('.trackto').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/tt/', {
                           width: '100px',
                           height: '20px',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
      
      $('.trackfrom').each(function(i,e) {
        var did = $(this).parent('td').parent('tr').attr('did')
        $(e).editable('/shipment/ajax/updated/did/'+did+'/ty/tf/', {
                           width: '100px',
                           height: '20px',
                           type: 'text',
                           submit: 'Ok',
                           style: 'display: inline',
                           }).addClass('editable');
      })
  }
})
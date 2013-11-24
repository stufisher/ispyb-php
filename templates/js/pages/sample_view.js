$(function() {
  
  var val = ['<img src="/templates/images/info.png" alt="N/A"/>',
         '<img src="/templates/images/run.png" alt="Running"/>',
         '<img src="/templates/images/ok.png" alt="Completed"/>',
         '<img src="/templates/images/cancel.png" alt="Failed"/>']

  function _load_history() {
    $.ajax({
      url: '/dc/ajax/sid/'+sid+'/page/'+page,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pgs = []
        for (var i = 0; i < json[0]; i++) pgs.push('<li'+(i+1==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
        $('.pages').html('<ul>'+pgs.join('')+'</ul>')
     
        $('.pages a').unbind('click').click(function() {
          page = parseInt($(this).attr('href').replace('#', ''))
          _load_history()
          url = window.location.pathname.replace(/\/page\/\d+/, '')+'/page/'+page
          window.history.pushState({}, '', url)
          return false
        })
           
           
        $('.history tbody').empty()
        $.each(json[1], function(i,e) {
          if (e['TYPE'] == 'data') {
             if (e['COMMENTS'].indexOf('Diffraction grid scan of') > 1) e['TYPE'] = 'grid'
               
             var desc = '&Omega; St: '+e['AXISSTART']+'&deg;, &Omega; Osc: '+e['AXISRANGE']+'&deg;, No: '+e['NUMIMG']+', Res: '+e['RESOLUTION']+'&#197;, &lambda;: '+e['WAVELENGTH']+'&#197;, Exp: '+e['EXPOSURETIME']+'s, Trn: '+e['TRANSMISSION']+'%'
               
             r = [e['ST'], e['TYPE'] == 'data' ? 'Data Collection' : 'Grid Scan', desc, e['NUMIMG'] < 10 ? '<span class="indexing">' : '<span class="ap">', '', '<a href="/dc/visit/'+e['VIS']+'/id/'+e['ID']+'" class="small view"></a>']
               
          } else if (e['TYPE'] == 'edge') {
             r = [e['ST'], 'Edge Scan','', '', '', '<a href="/dc/visit/'+e['VIS']+'/id/'+e['ID']+'" class="small view"></a>']
               
          } else if (e['TYPE'] == 'mca') {
             
             var st = 'AutoPyMCA ' + (e['ELEMENTS'].length ? val[2] : val[3])
             var el = []
             $.each (e['ELEMENTS'], function(i,e) {
               el.push(e.split(' ')[0])
             })
               
             var desc = 'Energy: '+e['WAVELENGTH']+'eV, Exp: '+e['EXPOSURETIME']+'s, Trn: '+e['TRANSMISSION']+'%'
               
             r = [e['ST'], 'Fluorescence Scan', desc, st, el.join(', '), '<a href="/dc/visit/'+e['VIS']+'/id/'+e['ID']+'" class="small view"></a>']
               
          } else if (e['TYPE'] == 'load') {
             if (r['IMP'] == 'LOAD' || r['IMP'] == 'UNLOAD')
               r = [e['ST'], 'Robot '+r['IMP']+'ed Sample','', '', '', '']
             else
               r = [e['ST'], 'Sample '+r['IMP'],'', '', '', '']
               
          }
               
          var row = ''
          //for (var i = 0; i < r.length; i++) {
          $.each(r, function(i,e) {
             row += '<td>'+e+'</td>'
          })
                  
          $('<tr dcid="'+e['ID']+'" type="'+e['TYPE']+'">'+row+'</tr>').appendTo('table.history tbody')
        })
           
        $('a.view').button({ icons: { primary: 'ui-icon-search' } })
        _get_status();
           
        if (!json[1].length) $('<tr><td colspan="6">No data collections for this sample</td></tr>').appendTo('table.history tbody')
      }
           
    })
  
  }
  
  _load_history()

  
  function _get_status() {  
    $.ajax({
        url: '/dc/ajax/aps/prop/'+prop,
        type: 'POST',
        data: { ids: $('tr[type=data]').map(function(i,e) { return $(e).attr('dcid') }).get() },
        dataType: 'json',
        timeout: 10000,
        success: function(list) {
         $.each(list, function(i, r) {
           var id = r[0]
           var res = r[1]
           var img = r[2]
           var dcv = r[3]
                
           var md = $('tr[dcid='+id+']')
           var div = $(md).children('td').eq(3).children('span')
           var ld = $(md).data('apr')
           
           if (div.hasClass('ap')) {
               if (res[2] || res[3] || res[4] || res[5]) md.attr('proc', 1)
                
               $(div).html('Fast DP: ' + val[res[2]] +
                         ' Xia2: ' + val[res[3]] + ' ' +val[res[4]] + ' ' +val[res[5]])
               //$(sp[1]).html('Fast EP: ' + val[res[6]] + ' Dimple: ' + val[res[7]])
           
           } else {
               $(div).html('Mosflm: ' + val[res[0]] + ' EDNA: ' + val[res[1]])
           }
                
           $(md).data('apr', res)
                
           _get_details()
         })
        }
           
    })
  }
  
  
  
  function _get_details() {
    $('tr[type=data][proc=1]').each(function(i,r) {
      $.ajax({
        url: '/dc/ajax/ap/id/' + $(r).attr('dcid'),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
             if (json.length) {
                var p = json[0]
                if (p['SHELL'] == 'overall')
                    $(r).children('td').eq(4).html('Rm:'+p['RMERGE']+' C:'+p['COMPLETENESS']+' Res:'+p['RHIGH'])
             }
        }
      })
    })
  }
  
  
  // Editables
  $('.comment').editable('/sample/ajax/update/sid/'+sid+'/ty/comment/', {
        type: 'text',
        width: '50%',
        height: '100%',
        submit: 'Ok',
        style: 'display: inline',
  }).addClass('editable');
  
  $('.name').editable('/sample/ajax/update/sid/'+sid+'/ty/name/', {
       width: '20%',
       height: '100%',
       type: 'text',
       submit: 'Ok',
       style: 'display: inline',
  }).addClass('editable');

  $('.sg').editable('/sample/ajax/update/sid/'+sid+'/ty/sg/', {
       data: sgs,
       type: 'select',
       submit: 'Ok',
       style: 'display: inline',
  }).addClass('editable');
  
  
  $.editable.addInputType('autocomplete', {
      //element : $.editable.types.text.element,
                          
      element: function(settings, original) {
        $(this).append('<input type="text"/>');
        var hidden = $('<input id="prac" type="hidden"/>');
        $(this).append(hidden);
        return(hidden);
      },
                          
      plugin : function(settings, original) {
        var set = $.extend({ select: function(e,ui) { $('#prac').val(ui.item.id) } }, settings.autocomplete)
        $('input[type=text]', this).autocomplete(set);
      },
                          
      submit: function(settings, original) {
        if (!$('input[type=hidden]', this).val()) {
          original.reset()
          return false
        }
     
      },
  })

  $('.acronym').editable('/sample/ajax/update/sid/'+sid+'/ty/acronym/', {
    type: 'autocomplete',
    autocomplete: { source: '/shipment/ajax/pro/array/1/' },
    width: '15%',
    height: '100%',
    submit: 'Ok',
    style: 'display: inline',
    onblur: 'ignore',
  }).addClass('editable');
  
  
  
})

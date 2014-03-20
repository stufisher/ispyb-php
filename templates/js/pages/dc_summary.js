$(function() {
  
  var thread = null;
  $('input[name=search]').keyup(function() {
      clearTimeout(thread);
      $this = $(this);
      thread = setTimeout(function() {
            page = 1
            search = $this.val()
                          
            url = window.location.pathname.replace(/\/s\/\w+/, '')+(search ? ('/s/'+search) : '')
            window.history.pushState({}, '', url)
                          
            _load_summary();
      }, 800);
  });
  
  $('input[name=search]').focus()
  if (search) $('input[name=search]').val(search)
  
  $('input.search-mobile').focus().keyup(function() {
    $('input[name=search]').val($(this).val()).trigger('keyup')
  }).parent('span').addClass('enable')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  
  function _load_summary() {
    $.ajax({
      url: '/dc/ajax/t/fc/pp/15'+ (is_visit ? ('/visit/'+visit) : '') + (page ? ('/page/'+page) : '') + (search ? ('/s/'+search) : ''),
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pgs = []
        for (var i = 0; i < json[0]; i++) pgs.push('<li'+(i+1==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
        $('.pages').html('<ul>'+pgs.join('')+'</ul>')
     
        $('.pages a').unbind('click').click(function() {
          page = parseInt($(this).attr('href').replace('#', ''))
          _load_summary()
          url = window.location.pathname.replace(/\/page\/\d+/, '')+'/page/'+page
          window.history.pushState({}, '', url)
          return false
        })
           
           
        $('.summary tbody').empty()
        $.each(json[1], function(i,e) {
          if (e['COMMENTS'].indexOf('Diffraction grid scan of') > -1) e['TYPE'] = 'grid'
          if (e['OVERLAP'] != 0) e['TYPE'] = 'screen'
                  
          $('<tr dcid="'+e['ID']+'" type="'+e['TYPE']+'">'+
                '<td>'+e['FILETEMPLATE'].replace('_####.cbf', '')+'</td>'+
                '<td></td>'+
                '<td>'+e['ST']+'</td>'+
                '<td>'+e['NI']+'</td>'+
                '<td>'+e['AXISRANGE']+'</td>'+
                '<td>'+e['EXPOSURETIME']+'</td>'+
                '<td>'+e['TRANSMISSION']+'</td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td><a href="/dc/visit/'+(is_visit ? visit : (prop+'-'+e['VN']))+'/id/'+e['ID']+'" class="view" title="View full details for the selected data collection">View Data Collection</a></td>'+
            +'</tr>').appendTo('table.summary tbody')
        })
           
        $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
        _get_status();
           
        if (!json[1].length) $('<tr><td colspan="10">No data collections found</td></tr>').appendTo('table.summary tbody')
      }
           
    })
  
  }
  
  _load_summary()

  
  function _get_status() {  
    $.ajax({
        url: '/dc/ajax/aps/prop/'+prop,
        type: 'POST',
        data: { ids: $('tr[type=data]').map(function(i,e) { return $(e).attr('dcid') }).get() },
        dataType: 'json',
        timeout: 10000,
        success: function(list) {
         $.each(list, function(i, r) {
           if (i == 'profile') return
           var id = r[0]
           var res = r[1]
           var img = r[2]
           var dcv = r[3]
                
           var md = $('tr[dcid='+id+']')
           if (md.attr('type') == 'data' && (res[2] ==2 || res[3] ==2 || res[4] ==2 || res[5] ==2)) {
             md.attr('proc', 1)
             $(md).children('td').eq(7).html('<img class="load" width="16" height="16" src="/templates/images/ajax-loader.gif" alt="Loading..." />')
           }
         })
         _get_details()
        }
           
    })
  
    var ids = [], tys = []
    $('tr[type=data]').each(function(i,dc) {
      if (!$(dc).attr('sample')) {
        ids.push($(dc).attr('dcid'))
        tys.push($(dc).attr('type'))
      }
    })
  
    if (ids.length) {
      $.ajax({
        url: '/dc/ajax/sf' + (is_visit ? ('/visit/'+visit) : ''),
        type: 'POST',
        data: { ids: ids, tys: tys },
        dataType: 'json',
        timeout: 20000,
        success: function(list) {
          $.each(list, function(id,dc) {
            var d = $('tr[dcid='+id+'][type='+dc['TY']+']')
            if (d.length) {
              if (dc['SID'] && !$(d).find('.sample').length) {
                $(d).children('td').eq(1).html('<a href="/sample/sid/'+dc['SID']+'/visit/'+prop+'">' + dc['SAN'] + '</a> (m' + dc['SCON'] + 'p' + dc['SPOS']+')')
              
              }
              $(d).attr('sample', true)
            }
          })
             
          list = null
        }
      })
    }
  
  }
  
  
  
  function _get_details() {
    $('tr[type=data][proc=1]').each(function(i,r) {
      $.ajax({
        url: '/dc/ajax/ap/id/' + $(r).attr('dcid'),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          var size = json[0]
          var data = json[1]
            
          if (size > 0) {
             
            var ord = {'Fast DP': 1, 'XIA2 3da': 3, 'XIA2 2da': 2, 'XIA2 3daii': 4};
            var od = {}
            $.each(data, function(id,e) { od[ord[e['TYPE']]] = id })
             
            var best = data[od[Math.max.apply(null,Object.keys(od))]]
            if (best) {
              var c = best['CELL']
              var s = best['SHELLS']
              var rm = best['TYPE'] == 'Fast DP' ? 'RMERGE' : 'RMEAS'
             
              var sp = []
              var sc = []
              $.each(['overall', 'innerShell', 'outerShell'], function(i,k) {
                sp.push('<span class="'+(s[k]['COMPLETENESS'] > 95 ? 'active' : (s[k]['COMPLETENESS'] > 80 ? 'minor' : 'inactive'))+'">')
                sc.push('<span class="'+(s[k][rm] < 0.5 ? 'active' : (s[k][rm] < 0.6 ? 'minor' : 'inactive'))+'">')
              })
             
              var st = 7
              $(r).children('td').eq(st).html(best['SG'])
              $(r).children('td').eq(st+1).html(c['CELL_A']+' ('+c['CELL_AL']+')<br />'+c['CELL_B']+' ('+c['CELL_BE']+')<br />'+c['CELL_C']+' ('+c['CELL_GA']+')')
              $(r).children('td').eq(st+2).html(s['overall']['RLOW']+' - '+s['overall']['RHIGH']+'<br />'+s['innerShell']['RLOW']+' - '+s['innerShell']['RHIGH']+'<br />'+s['outerShell']['RLOW']+' - '+s['outerShell']['RHIGH'])
              $(r).children('td').eq(st+3).html(sc[0]+s['overall'][rm]+'</span><br />'+sc[1]+s['innerShell'][rm]+'</span><br />'+sc[2]+s['outerShell'][rm]+'</span>')
              $(r).children('td').eq(st+4).html(sp[0]+s['overall']['COMPLETENESS']+'</span><br />'+sp[1]+s['innerShell']['COMPLETENESS']+'</span><br />'+sp[2]+s['outerShell']['COMPLETENESS']+'</span>')

              $(r).children('td').eq(st+5).append(' <a href="/download/id/'+$(r).attr('dcid')+'/aid/'+best['AID']+'" class="dll" title="Download MTZ file">Download MTZ file</a>')
            }
          }
             
          $('a.dll').button({ icons: { primary: 'ui-icon-arrowthick-1-s' }, text: false })
        }
      })
    })
  }
  
});

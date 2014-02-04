$(function() {
  var sid;
  var prid;
  
  // Toggle implicit and explicit project members
  var imp = 1;
  $('button.implicit').button({ icons: { primary: 'ui-icon-arrow-4-diag' } }).click(function() {
    imp = imp ? 0 : 1
    sid = 0
    prid = 0
    $(this).button('option', 'label', imp ? 'Implicit Project Members' : 'Explicit Project Members')
    
    // Refresh tables
    samples.fnDraw()
    dcs.fnDraw()
    ed.fnDraw()
    fl.fnDraw()
  })
  
  
  // DataTables options
  var dtops = {sPaginationType: 'full_numbers',
               bProcessing: true,
               bServerSide: true,
               bAutoWidth:false ,
               aaSorting: [[ 0, 'desc' ]],
  }
  
  
  // Work out pages
  function _pages(aoData) {
    var st = 0, pp = 10
    $.each(aoData, function(i,d) {
      if (d['name'] == 'iDisplayStart') st = d['value']
      if (d['name'] == 'iDisplayLength') pp = d['value']
           
      if (d['name'] == 'sSearch') {
        aoData.push({ name: 's', value: d['value'] })
      }
    })
    aoData.push({ name: 'page', value: (st/pp)+1 })
    aoData.push({ name: 'pp', value: pp })
  
    return aoData
  }
  
  function _pp(aoData) {
    $.each(aoData, function(i,d) {
      if (d['name'] == 'iDisplayLength') return d['value']
    })
  
    return 10
  }
  
  
  function _map_callbacks() {
    // Icons on ajax requests
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
  
    // Make sample rows clickable
    $('.robot_actions.samples tbody tr').unbind('click').click(function(i,e) {
      var cur = $(this).hasClass('selected')
      $('.robot_actions.samples tbody tr').removeClass('selected')
      if (cur) {
        sid = 0
      } else {
        $(this).addClass('selected')
        sid = $(this).children('td').eq(0).html()
      }
          
      dcs.fnDraw()
      ed.fnDraw()
      fl.fnDraw()
    })
  
    // Make protein rows clickable
    $('.robot_actions.proteins tbody tr').unbind('click').click(function(i,e) {
      var cur = $(this).hasClass('selected')
      $('.robot_actions.proteins tbody tr').removeClass('selected')
      if (cur) {
        prid = 0
      } else {
        $(this).addClass('selected')
        prid = $(this).children('td').eq(0).children('span').attr('value')
      }
          
      samples.fnDraw()
    })
  }
  
  
  // Protein List
  var dt = $.extend(dtops, {
            sAjaxSource: '/sample/ajax/proteins/pjid/'+pid+'/',
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            }
  })
  var proteins = $('.robot_actions.proteins').dataTable(dt).fnSetFilteringDelay();

  
  // Sample List
  var dt = $.extend(dtops, {
            sAjaxSource: '/sample/ajax/samples/pjid/'+pid+'/',
            fnServerParams: function ( aoData ) {
              aoData.push( { 'name': 'imp', 'value': imp } )
              if (prid) aoData.push( { 'name': 'pid', 'value': prid } )
            },
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            },
  })
  var samples = $('.robot_actions.samples').dataTable(dt).fnSetFilteringDelay();
  
  
  // Data Collection List
  var dctype = 'dc'
  var dt = $.extend(dtops, {
            bSort: false,
            sAjaxSource: '/dc/ajax/',
            fnServerParams: function ( aoData ) {
              aoData.push( { 'name': 'imp', 'value': imp } )
              aoData.push( { 'name': 't', 'value': dctype } )
              aoData.push( sid ? { 'name': 'sid', 'value': sid} : { 'name': 'pjid', 'value': pid} )
            },
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, _pages(aoData), function (json) {
                          
                  var p = []
                  $.each(json[1], function(i, e) {
                    p.push([e['ST'], (e['DIR']+e['FILETEMPLATE']).replace('_####.cbf', ''), e['SAMPLE'] ? e['SAMPLE'] : 'N/A', e['AXISRANGE'], e['NUMIMG'], e['WAVELENGTH'], e['TRANSMISSION'], e['EXPOSURETIME'], e['COMMENTS'], '<a class="view" href="/dc/visit/'+e['VIS']+'/id/'+e['ID']+'">View Data Collection</a> <button class="atp" ty="dc" iid="'+e['DCG']+'" name="'+e['DIR']+e['FILETEMPLATE']+'">Add to/Remove from Project</button>'])
                  })
                          
                  fnCallback({ iTotalRecords: _pp(aoData)*json[0], iTotalDisplayRecords: _pp(aoData)*json[0], aaData: p })
                  _map_callbacks()
                })
            },
  })
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  var dcs = $('.robot_actions.dcs').dataTable(dt).fnSetFilteringDelay();

  // Filter by type
  $('.filter ul li').click(function() {
    if ($(this).hasClass('current')) {
        $(this).removeClass('current')
        dctype = 'dc'
    } else {
        $('.filter ul li').removeClass('current')
        $(this).addClass('current')
        dctype = $(this).attr('id')
    }
    
    dcs.fnDraw()
  })
  
  
  // Energy Scans
  var dt = $.extend(dtops, {
            bSort: false,
            bFilter: false,
            sAjaxSource: '/dc/ajax/t/ed/',
            fnServerParams: function ( aoData ) {
              aoData.push( { 'name': 'imp', 'value': imp } )
              aoData.push( sid ? { 'name': 'sid', 'value': sid} : { 'name': 'pjid', 'value': pid} )
            },
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, _pages(aoData), function (json) {
                  var p = []
                  $.each(json[1], function(i, e) {
                    p.push([e['ST'], e['DIR'], e['SAMPLE'] ? e['SAMPLE'] : 'N/A', e['TRANSMISSION'], e['EXPOSURETIME'], e['EPK']+'ev, f&rsquo;'+e['RESOLUTION']+'e, f&rsquo;&rsquo;:'+e['AXISSTART'], e['EIN']+'ev, f&rsquo;:'+e['AXISRANGE']+'e, f&rsquo;&rsquo;:'+e['WAVELENGTH']+'e', e['COMMENTS'], '<a class="view" href="/dc/visit/'+e['VIS']+'/t/edge/id/'+e['ID']+'">View Scan</a>'])
                  })
                          
                  fnCallback({ iTotalRecords: _pp(aoData)*json[0], iTotalDisplayRecords: _pp(aoData)*json[0], aaData: p })
                  _map_callbacks()
                })
            },
  })
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  var ed = $('.robot_actions.energy').dataTable(dt)

  
  // Fluorescence Spectra
  var dt = $.extend(dtops, {
            bSort: false,
            bFilter: false,
            sAjaxSource: '/dc/ajax/t/fl/',
            fnServerParams: function ( aoData ) {
              aoData.push( { 'name': 'imp', 'value': imp } )
              aoData.push( sid ? { 'name': 'sid', 'value': sid} : { 'name': 'pjid', 'value': pid} )
            },
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, _pages(aoData), function (json) {
                  var p = []
                  $.each(json[1], function(i, e) {
                    /*var el = []
                    $.each (e['ELEMENTS'], function(i,e) {
                      el.push(e.split(' ')[0])
                    })*/
                         
                    p.push([e['ST'], e['SAMPLE'] ? e['SAMPLE'] : 'N/A', e['WAVELENGTH'], e['TRANSMISSION'], e['EXPOSURETIME'], '', e['COMMENTS'], '<a class="view" href="/dc/visit/'+e['VIS']+'/t/mca/id/'+e['ID']+'">View Spectrum</a>'])
                  })
                          
                  fnCallback({ iTotalRecords: _pp(aoData)*json[0], iTotalDisplayRecords: _pp(aoData)*json[0], aaData: p })
                  _map_callbacks()
                })
            },
  })
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  var fl = $('.robot_actions.mca').dataTable(dt)
  
  
  // Repsonsive tables
  $(window).resize(function() { _resize() })
  function _resize() {
    $.each([2,3],function(i,n) {
      proteins.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  
    $.each([0,3,4,5,6,7],function(i,n) {
      samples.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  }
  
  _resize()
  
  
  
  
  $.each({'title': 'wwsdash', 'acronym': 'wwdash'}, function(e,t) {
    $('.'+e).editable('/projects/ajax/update/pid/'+pid+'/ty/'+e+'/', {
       type: 'text',
       height: '100%',
       width: '20%',
       submit: 'Ok',
       style: 'display: inline',
       onsubmit: function(s,td) {
         var r = { value: {}}
         r.value[t] = true
         $(this).validate({
           validClass: 'fvalid', errorClass: 'ferror',
           errorElement: 'span',
           rules: r
         })
         return $(this).valid();
       },
                      
    }).addClass('editable');
  })
  
  $('.tabs').tabs()
})

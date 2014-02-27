$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/pid/'+pid+'/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            }
  }
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  var dt = $('.samples').dataTable(dt)
  $('.table input').focus()
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
  $.each([0,3,4,5,6,7],function(i,n) {
         dt.fnSetColumnVis(n, !($(window).width() <= 600))
         })
  }
  
  _resize()
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
    $('table.samples a.view').hide()
  
    $('table.samples tbody tr').unbind('click').click(function() {
      window.location = $('td:last-child a.view', this).attr('href')
    })
  }

  $.each({'name': 'wwdash', 'acronym': 'wwdash', 'mass': 'number'}, function(e,t) {
    $('.'+e).editable('/sample/ajax/updatep/pid/'+pid+'/ty/'+e+'/', {
                      height: '100%',
                      type: 'text',
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
  
  
  $('.seq').editable('/sample/ajax/updatep/pid/'+pid+'/ty/seq/', {
    type: 'textarea',
    rows: 5,
    width: '100%',
    submit: 'Ok',
    style: 'display: inline',
    
  }).addClass('editable');
  
  
  $('#ap').validate({
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        pdb_file: {
          extension: 'pdb',
        },
        pdb_code: {
          minlength: 4,
          maxlength: 4,
        },
    }
  })
  
  // Get list of pdbs for proposal
  function _get_pdbs() {
    $.ajax({
      url: '/sample/ajax/pdbs'+(pid ? ('/pid/'+pid) : ''),
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = ''
        $.each(json, function(i,p) {
          pdb_out += '<li pdbid="'+p['PDBID']+'">'+p['NAME']+(p['CODE'] ? ' [Code]' : ' [File]')+' <span class="r"><button class="delete">Delete</button></span></li>'
        })
           
        $('.pdb ul').html(pdb_out)
           
        // Enable delete buttons
        $('button.delete').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).click(function(i,e) {
          $.ajax({
            url: '/sample/ajax/rempdb/pid/'+pid+'/pdbid/'+$(this).parent('span').parent('li').attr('pdbid'),
            type: 'GET',
            dataType: 'json',
            success: function(json){
              _get_pdbs()
            }
          })
        })
      }
    })
  }
  _get_pdbs()
  
  
  $('#add_pdb .progress').progressbar({ value: 0 });
  $('#add_pdb').dialog({ title: 'Add PDB', autoOpen: false, buttons: { 'Add': function() { _add_pdb() }, 'Cancel': function() { $(this).dialog('close') } } });
  
  $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).click(function(i,e) {
    $('.progress').progressbar({ value: 0 });
    _get_all_pdbs(function() { $('#add_pdb').dialog('open') })
  })
  
  function _get_all_pdbs(callback) {
    $.ajax({
      url: '/sample/ajax/pdbs',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = '<option value="">N/A</option>'
        $.each(json, function(i,p) {
          pdb_out += '<option value="'+p['PDBID']+'">'+p['NAME']+(p['CODE'] ? ' [Code]' : ' [File]')+'</option>'
        })
           
        $('select[name=existing_pdb]').html(pdb_out)
        if (callback) callback()
      }
    })
  }
  
  
  // Upload new pdb file
  function _add_pdb() {
      var fd = new FormData($('form#ap')[0])
      $.ajax({
        url: '/sample/ajax/addpdb/pid/'+pid,
        type: 'POST',
        data: fd,
        dataType: 'json',
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr()
          if(myXhr.upload) myXhr.upload.addEventListener('progress', _upload_progress, false)
          return myXhr;
        },
             
        cache: false,
        contentType: false,
        processData: false,
             
        success: function(json){
          $('#add_pdb').dialog('close')
          _get_pdbs()
        }
      })
  }
  
  function _upload_progress(e) {
    var pc = (e.loaded / e.total)*100;
    $('.progress').progressbar({ value: pc });
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
  
  // DataTables options
  var dtops = {sPaginationType: 'full_numbers',
               bProcessing: true,
               bServerSide: true,
               bAutoWidth:false ,
               aaSorting: [[ 0, 'desc' ]],
  }
  
  
  // Data Collection List
  var dctype = 'dc'
  var dt = $.extend(dtops, {
            bSort: false,
            sAjaxSource: '/dc/ajax/',
            fnServerParams: function ( aoData ) {
              aoData.push( { 'name': 't', 'value': dctype } )
              aoData.push( { 'name': 'pid', 'value': pid} )
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
              aoData.push( { 'name': 'pid', 'value': pid } )
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
              aoData.push( { 'name': 'pid', 'value': pid } )
            },
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, _pages(aoData), function (json) {
                  var p = []
                  $.each(json[1], function(i, e) {
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
  
  $('.tabs').tabs()
})
$(function() {

  var tot = 0
  var cur = 0
  var suc = 0
  var fail = 0
  var skip = 0
  var processed = []
  var stats = {}
  var perbl = {}
  var perbl_old = {}
  var blns = ['i02', 'i03', 'i04', 'i04-1', 'i24']
  
  $('h1.ph').click(function() {
    $('div.pdbs').slideToggle()
  })
  
  var dt = $('.robot_actions').dataTable({'sPaginationType': 'full_numbers',
                                          'bProcessing': true,
                                          'bServerSide': true,
                                          'sAjaxSource': '/cell/ajax/analysed/',
                                          'fnServerData': function (sSource, aoData, fnCallback, oSettings) {
                                         oSettings.jqXHR = $.ajax({
                                                    'dataType': 'json',
                                                    'type': 'GET',
                                                    'url': sSource,
                                                    'data': aoData,
                                                    'success': function(json) {
                                                        stats = json['stats'],
                                                        processed = json['processed'],
                                                        perbl = json['perbl'],
                                                        perbl_old = json['perbl_old'],
                                                        _plots()
                                                        fnCallback(json)
                                                    }
                                                })
                                            }
                                         })
  
  function _get_ids(bl) {
    var query = '<orgPdbQuery>'+
        '<queryType>org.pdb.query.simple.XrayDiffrnSourceQuery</queryType>'+
        '<description>Diamond</description>'+
        '<diffrn_source.pdbx_synchrotron_site.comparator>contains</diffrn_source.pdbx_synchrotron_site.comparator>'+
        '<diffrn_source.pdbx_synchrotron_site.value>DIAMOND</diffrn_source.pdbx_synchrotron_site.value>'
  
    if (bl) query += '<diffrn_source.pdbx_synchrotron_beamline.comparator>contains</diffrn_source.pdbx_synchrotron_beamline.comparator>'+
        '<diffrn_source.pdbx_synchrotron_beamline.value>'+bl+'</diffrn_source.pdbx_synchrotron_beamline.value>'
  
    query += '</orgPdbQuery>'
  
    $.ajax({
         url: 'http://www.rcsb.org/pdb/rest/search/',
         data: query,
         type: 'POST',
         dataType: 'text',
         timeout: 5000,
         success: function(text){
            $('.count').html(text.split('\n').length)
            $('textarea[name=pdb_list]').html(text.split('\n').join(', '))
         }
           
    })
  
  }
  
  $('select[name=beamline]').change(function() { _get_ids($(this).val()) })
  
  
  
  $('button[name=process]').click(function() {
    var pdbs = $('textarea[name=pdb_list]').val().split(',').map(function(s) { return s.trim() }).filter(function(e) { return !!e }).reverse()
                                  
    if (pdbs.length > 0) {
      tot = pdbs.length
      cur = 0
      fail = 0
      skip = 0
      _update_count()
      $('button[name=process]').attr('disabled', true)
      _get_pdb(pdbs,0)
    }
  })
  
  
  function _get_pdb(pdbs, id) {
    if (id < pdbs.length) {
      var p = pdbs[id].toUpperCase()
      cur++
  
      if (processed.indexOf(p) > -1) {
        skip++
        _update_count()
        if ((id+1) < pdbs.length) _get_pdb(pdbs, id+1)
  
      } else {
        var tol = $('input[name=tol]').val() ? $('input[name=tol]').val() : 0.01
  
        $.ajax({
            url: 'http://www.rcsb.org/pdb/rest/customReport?pdbids='+p+'&customReportColumns=structureTitle,unitCellAngleAlpha,unitCellAngleBeta,unitCellAngleGamma,lengthOfUnitCellLatticeA,lengthOfUnitCellLatticeB,lengthOfUnitCellLatticeC,structureAuthor,citationAuthor,diffractionSource,resolution,releaseDate',
            type: 'GET',
            dataType: 'xml',
            timeout: 5000,
            success: function(xml){
                var data = { pdb: p, tol: tol }
                $(xml).find('record').each(function(i,r) {
                    $.each({ a: 'lengthOfUnitCellLatticeA',
                            b: 'lengthOfUnitCellLatticeB',
                            c: 'lengthOfUnitCellLatticeC',
                            al: 'unitCellAngleAlpha',
                            be: 'unitCellAngleBeta',
                            ga: 'unitCellAngleGamma',
                           }, function(k,v) {
                        data[k] = $(r).find('dimStructure\\.'+v).text()
                    })
                            
                    data['title'] = $(r).find('dimStructure\\.structureTitle').text()
                                           
                    // add 25% tolerance on resolution limit
                    data['res'] = $(r).find('dimStructure\\.resolution').text()*1.25
                                 
                    if ($(r).find('dimStructure\\.diffractionSource').text() != 'null') data['bl'] = $(r).find('dimStructure\\.diffractionSource').text()
                    if ($(r).find('dimStructure\\.structureAuthor').text() != 'null') data['author'] = $(r).find('dimStructure\\.structureAuthor').text()
                    data['year'] = $(r).find('dimStructure\\.releaseDate').text()
                })
               
                $.ajax({
                    url: '/cell/ajax/',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 20000,
                    success: function(json){
                       suc++
                       _update_count()
                       _get_pdb(pdbs, id+1)
                    },
                    error: function(xhr, status, error) {
                        console.log(xhr, status, error)
                        fail++
                        _update_count()
                        _get_pdb(pdbs, id+1)
                    }
                       
                })
            }
        })
      }
    } else {
        $('button[name=process]').attr('disabled', false)
    }
  }
  
  
  
  function _update_count() {
    $('.processing').html(' | '+cur+'/'+tot+' PDB files processed, '+suc+' successful, '+fail+' failures, '+skip+' already processed')
  }
  
  
  
  function _plots() {
    var pie = []
    for (t in stats) pie.push({label: t, data: stats[t]})
  
    $.plot('#visit_pie', pie, {
         series: {
            pie: {
                show: true,
                radius: 1,
                label: {
                    show: true,
                    radius: 2/3,
                    formatter: labelFormatter,
                    //threshold: 0.1
                }
            }
         },
         legend: {
            show: false
         },
         grid: {
            hoverable: true,
         }
    })
  
    var pery = []
    var pery2 = []
    var yk = []
    for (var y in perbl) yk.push(y)
    yk.sort()
  
    $.each(yk, function(i,y) {
        var data = []
        for (d in perbl[y]) data.push([d, perbl[y][d]])
  
        pery.push({label:y, data: data})
  
        var data = []
        for (d in perbl_old[y]) data.push([d, perbl_old[y][d]])
        pery2.push({label:y, data: data})
    })
  
    var ticks = []
    $.each(blns, function(i,e) { ticks.push([i,e]) })
  
    var ops = {
        series: {
            bars: {
                show: true,
                barWidth: .9,
                align: 'center'
            },
            stack: true
        },
        grid: {
            hoverable: true,
            borderWidth: 0,
        },
        xaxis: {
           ticks: ticks
        },
    }
  
    $.plot('#pdbs', pery, ops)
    $.plot('#pdbs2', pery2, ops)
  
  }
  
  function labelFormatter(label, series) {
    return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + series.percent.toFixed(1) + "%</div>";
  }
  
  
})

$(function() {

  $('button[name=get_pdb]').button({ icons: { primary: 'ui-icon-arrowthick-1-s' } }).click(function() {
                               
    $('.data_collections').empty()
    $('.pdb_details_not_found').hide()
    $('.pdb_details').hide()
                                  
    if ($('input[name=pdb]').val()) {
      $.ajax({
        url: 'http://www.rcsb.org/pdb/rest/customReport?pdbids='+$('input[name=pdb]').val()+'&customReportColumns=structureId,structureTitle,unitCellAngleAlpha,unitCellAngleBeta,unitCellAngleGamma,lengthOfUnitCellLatticeA,lengthOfUnitCellLatticeB,lengthOfUnitCellLatticeC,structureAuthor,citationAuthor,firstPage,lastPage,journalName,title,volumeId,publicationYear,diffractionSource,resolution,spaceGroup,releaseDate',
        type: 'GET',
        dataType: 'xml',
        timeout: 5000,
        success: function(xml){
            var found = false;
            $(xml).find('record').each(function(i,r) {
                found = true;
                $.each({ a: 'lengthOfUnitCellLatticeA',
                        b: 'lengthOfUnitCellLatticeB',
                        c: 'lengthOfUnitCellLatticeC',
                        al: 'unitCellAngleAlpha',
                        be: 'unitCellAngleBeta',
                        ga: 'unitCellAngleGamma',
                       }, function(k,v) {
                    $('input[name='+k+']').val($(r).find('dimStructure\\.'+v).text())
                })
                        
                $('.pdb_details .title').html($('input[name=pdb]').val()+': '+$(r).find('dimStructure\\.structureTitle').text())
                                       
                var res = $(r).find('dimStructure\\.resolution').text()
                $('.pdb_details .res').html(res)
                $('input[name=res]').val(res)
                                       
                $('input[name=sg]').val($(r).find('dimStructure\\.spaceGroup').text())
                                       
                if ($(r).find('dimStructure\\.diffractionSource').text() != 'null') $('.pdb_details .beamline').html($(r).find('dimStructure\\.diffractionSource').text())
                $('.pdb_details .author').html($(r).find('dimStructure\\.structureAuthor').text())
                                       
                var cit = $(r).find('dimStructure\\.title').text()
                if (cit) {
                    $.each(['citationAuthor', 'firstPage', 'volumeId', 'journalName', 'publicationYear'], function(i,f) {
                        if ($(r).find('dimStructure\\.'+f).text() != 'null') cit += ', '+$(r).find('dimStructure\\.'+f).text()
                    })
                } else cit = 'N/A'
                                       
                $('.pdb_details .citation').html(cit)
                $('.pdb_details .date').html($(r).find('dimStructure\\.releaseDate').text())
                $('.pdb_details').slideDown()
                                       
                $('button[name=lookup]').trigger('click')
                    
            })
            if (!found) $('.pdb_details_not_found').slideDown()
        }
           
      })
    }
  })
  
  
  // Get data collections for a particular cell
  $('button[name=lookup]').button({ icons: { primary: 'ui-icon-search' } }).click(function() {
    var uc = {}
    $.each(['a', 'b', 'c', 'al', 'be', 'ga'], function(i,e) {
        uc[e] = $('input[name='+e+']').val()
    })
                                 
    // 25% tolerance on resolution
    if ($('input[name=res]').val()) uc['res'] = $('input[name=res]').val()*1.25
    if ($('input[name=sg]').val()) uc['sg'] = $('input[name=sg]').val()
                                 
    uc['tol'] = $('input[name=tol]').val()
                  
    if ($('.pdb_details .date').html()) uc['year'] = $('.pdb_details .date').html()
                     
    $('.count').html('Searching...')
    $('.data_collections').empty()
                                                                                  
    $.ajax({
        url: '/cell/ajax/',
        type: 'GET',
        data: uc,
        dataType: 'json',
        timeout: 20000,
        success: function(json){
            $('.count').html(json[0])
           
            $('.data_collections').empty()
            $.each(json[1], function(i,r) {
                  var us = []
                  $.each(r['USERS'], function(i,u) {
                    var last = $(u.split(' ')).get(-1)
                    if ($('.pdb_details .author').html().indexOf(last) > -1) us.push('<span class="found">'+u+'</span>')
                    else us.push(u)
                  })
                   
                  $('<div class="cells data_collection" dcid="'+r['ID']+'">'+
                        '<h1><a href="/dc/visit/'+r['VISIT']+'/id/'+r['ID']+'" title="Click to view full details for this data collection">'+r['VISIT']+': '+r['BL']+' - '+r['ST']+'</a> (<span title="Distance between search unit cell parameters and those for this data set. A smaller number means the data set is close to the searched parameters">Distance: '+parseFloat(r['DIST']).toFixed(2)+', '+r['TYPE']+')</span></h1>'+
                        '<h2>'+r['DIR']+r['FILETEMPLATE']+'</h2>'+
                    
                        '<div class="users">'+
                            '<h3>Users</h3>'+
                            '<p class="ulist" title="Users that match the user list for the designated PDB file are highlighted in green">'+us.join(', ')+'</p>'+
                        '</div>'+
                    
                        '<div class="cell">'+
                        '<h3>Cell Parameters</h3>'+
                        '<ul>'+
                            '<li>A: '+r['CELL_A']+'</li>'+
                            '<li>B: '+r['CELL_B']+'</li>'+
                            '<li>C: '+r['CELL_C']+'</li>'+
                            '<li>&alpha;: '+r['CELL_AL']+'</li>'+
                            '<li>&beta;: '+r['CELL_BE']+'</li>'+
                            '<li>&gamma;: '+r['CELL_GA']+'</li>'+
                        '</ul>'+
                        '</div>'+
                    
                        '<div class="stats">'+
                        '<h3>Statistics</h3>'+
                        '<ul>'+
                             '<li>Resolution: '+r['RHIGH']+' - '+r['RLOW']+'&#197;</li>'+
                             '<li>Spacegroup: '+r['SG']+'</li>'+
                             '<li>Rmerge: '+r['RMERGE']+'</li>'+
                             '<li>Completeness: '+r['COMPLETENESS']+'</li>'+
                             '<li>I/&sigma;(I): '+r['ISIGI']+'</li>'+
                             '<li>Multiplicity: '+r['MULTIPLICITY']+'</li>'+
                        '</ul>'+
                        '</div>'+
    
                    
                        '<div class="data">'+
                        '<h3>Data Collection</h3>'+
                        '<ul>'+
                             '<li>&Omega; Start: '+r['AXISSTART']+'&deg;</li>'+
                             '<li>&Omega; Osc: '+r['AXISRANGE']+'&deg;</li>'+
                             '<li>No. Images: '+r['NUMIMG']+'</li>'+
                             '<li>Wavelength: '+r['WAVELENGTH']+'&#197;</li>'+
                             '<li>Exposure: '+r['EXPOSURETIME']+'s</li>'+
                             '<li>Transmission: '+r['TRANSMISSION']+'%</li>'+
                        '</ul>'+
                        '</div>'+
                    
                    '<div class="clear"></div>'+
                    '</div>').hide().appendTo('.data_collections').slideDown()
            })
        }
    })
                                 
                                 
  })
  
  
  if (pdb) {
    $('input[name=pdb]').val(pdb)
    $('button[name=get_pdb]').trigger('click')
  }

})

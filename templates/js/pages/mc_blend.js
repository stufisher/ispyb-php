$(function() {
  
  var auto_load = 1;
  
  
  $('#dialog').dialog({ autoOpen: false, buttons: { 'Ok': function() { $(this).dialog('close'); } } });
  
  // Filter selection by Rmerge
  $('input[name=rmerge]').keyup(function() {
    var val = parseFloat($(this).val())
    $('.integrated tr').each(function(i,e) {
        var r = parseFloat($($(this).children('td')[5]).html())
        r < val ? $(this).addClass('selected') : $(this).removeClass('selected')
    })
    count()
  })
  
  // Initiate a blend run
  $('button[name=analyse]').click(function() {
     if (!$('.integrated tr.selected').length) {
        alert('You need to select some data sets to blend')
        return
     } else {
        $.ajax({
            url: '/mc/ajax/blend/visit/' + visit,
            type: 'POST',
            data: { dcs: $('.integrated tr.selected').map(function() { return parseInt($(this).attr('dcid')) }).get(), res: $('input[name=res]').val(), isigi: $('input[name=isigi]').val(), rfrac: $('input[name=rfrac]').val() },
            dataType: 'json',
            timeout: 15000,
            success: function(r){
               if (r) state = 1
            }
        })
                                  
        $('#dialog').dialog('open')
     }
     
    
  })
  
  // Load list of integrated data sets
  function load_datacollection() {
      $.ajax({
             url: '/mc/ajax/visit/' + visit,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
                $('.integrated tbody').empty()
             
                $.each(json[1].reverse(), function(i,r) {
                    if (r['INT'] == 2) {
                    $('<tr dcid="'+r['DID']+'">'+
                      '<td>'+r['DIR']+'</td>'+
                      '<td>'+r['PREFIX']+'</td>'+
                      '<td>'+r['OST']+'</td>'+
                      '<td>'+r['STATS']['SG']+'</td>'+
                      '<td>'+r['STATS']['CELL']+'</td>'+
                      '<td>'+r['STATS']['R']+'</td>'+
                      '<td>'+r['STATS']['C']+'</td>'+
                      '<td>'+r['STATS']['RESH']+'</td>'+
                      '</tr>').appendTo($('.integrated tbody'))
                    }
                })
                       
                $('.integrated td').unbind('click').click(function() {
                    $(this).parent().hasClass('selected') ? $(this).parent().removeClass('selected') : $(this).parent().addClass('selected')
                    count()
                })
             }
        })
  }
  
  load_datacollection()
  
  
  // List of blended data sets
  function load_blended() {
      var val = ['Running <img src="/templates/images/run.png" alt="Running"/>',
         'Completed <img src="/templates/images/ok.png" alt="Completed"/>',
         'Failed <img src="/templates/images/cancel.png" alt="Failed"/>']
  
      $.ajax({
             url: '/mc/ajax/blended/visit/' + visit,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
             
                $.each(json, function(i,r) {
                  if ($('div[run='+r['ID']+']').length) {
                    var last = $('div[run='+r['ID']+']').attr('state')
                    
                    if (last != r['STATE']) {
                       $('div[run='+r['ID']+']').children('.run_state').html(val[r['STATE']])
                       if (r['STATE'] == 1) $('div[run='+r['ID']+']').children('.files').after(_make_table(r))
                    }
                       
                    $('div[run='+r['ID']+']').attr('state', r['STATE'])
                       
                  } else {
                    var tab = r['STATE'] == 1 ? _make_table(r) : ''
                       
                    var files = ''
                    for (var i = 0; i < r['FILES'].length; i++) {
                       files += '<li>'+r['FILES'][i]+'</li>'
                    }
                       
                    $('<div class="data_collection" run="'+r['ID']+'" state="'+r['STATE']+'">'+
                      '<span class="run_state">Radfrac: '+r['RFRAC']+' I/&sigma;(I): '+r['ISIGI']+' | '+val[r['STATE']]+'</span>'+
                      '<h1>Run '+r['ID']+'</h1>'+
                      '<div class="files"><h2>'+r['FILES'].length+' Files</h2><ul>'+files+'</ul></div>'+
                      tab+
                      '<div class="clear"></div></div>').hide().prependTo($('.blended')).slideDown()
                  }
                       
                })
             }
      })
  
      setTimeout(function() { load_blended() }, 15000)
  }
  
  load_blended()
  
  function _make_table(r) {
    tab = '<table class="robot_actions half">'+
            '<thead>'+
              '<tr>'+
                '<th>&nbsp;</th>'+
                '<th>Overall</th>'+
                '<th>Inner</th>'+
                '<th>Outer</th>'+
              '</tr>'+
            '</thead><tbody>'

    for (var k in r['STATS']) {
        tab += '<tr>'+
                 '<td>'+r['STATS'][k][0]+'</td>'+
                 '<td>'+r['STATS'][k][1]+'</td>'+
                 '<td>'+r['STATS'][k][2]+'</td>'+
                 '<td>'+r['STATS'][k][3]+'</td>'+
               '</tr>'
    }

    return tab+'</tbody></table>'
  }
  
  
  // Check how many jobs are currently running
  function job_state() {
    $.ajax({
        url: '/mc/ajax/status',
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(jobs){
          jobs > 0 ? $('.jobs').parent('li').addClass('running') : $('.jobs').parent('li').removeClass('running')
          $('.jobs').html(jobs)
        }
    })
  
    setTimeout(function() { job_state() }, 5000)
  }
  
  job_state()
  
  
  function count() {
    $('.count').html($('.integrated tr.selected').length)
  }
             
});

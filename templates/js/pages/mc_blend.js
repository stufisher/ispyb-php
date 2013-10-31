$(function() {
  
  var user = null;
  
  var auto_load = 1;
  var auto_load_thread = null
  var dend = null
  var ids = []
  var blended = {}
  var t = []
  
  var check = false
  var last_job_count = 0
  
  $('.blended_wrap, .table_wrap').height($(window).height()*.28)
  
  $('#dialog').dialog({ autoOpen: false, buttons: { 'Ok': function() { $(this).dialog('close'); } } });

  $('#stats').dialog({ width: 'auto', resize: 'auto',  autoOpen: false, buttons: { 'Close': function() { $(this).dialog('close') } }, position: { my: 'center', at: 'center', of: '.blended_wrap'} });
  
  $('.slider').slider({ stop: _scale_plot })
  
  // Filter selection by Rmerge
  $('input[name=rmerge]').keyup(function() {
    var val = parseFloat($(this).val())
    $('.integrated tr').each(function(i,e) {
        var r = parseFloat($($(this).children('td')[5]).html())
        r < val ? $(this).addClass('selected') : $(this).removeClass('selected')
    })
    count()
  })
  
  
  // Select data sets from dendrogram
  $('.dendrogram').bind('plotselected', function (event, ranges) {
        if (!$('input[name=additive]').is(':checked')) $('.integrated tr').removeClass('selected')
        for (var i = Math.round(ranges.yaxis.from); i <= Math.round(ranges.yaxis.to); i++) {
            $('tr[dcid='+ids[t[i]-1]+']').addClass('selected')
        }
        count()
  });
  
  // Clear selection
  $('button[name=clear]').click(function() {
        $('.integrated tr').removeClass('selected')
        count()
  })
  
  // Rescale dendrogram
  function _scale_plot(ui, event) {
        dend.getOptions().xaxes[0].max = $('.slider').slider('value');
        dend.setupGrid();
        dend.draw();
  
        count()
  }
  
  
  // Show a different users output
  $('select[name=user]').change(function() {
    owner = $('select[name=user] option[value='+$('select[name=user]').val()+']').html() == cas
    
    owner ? $('.options.data_collection').slideDown() : $('.options.data_collection').slideUp()
    owner ? $('button[name=analyse]').show() : $('button[name=analyse]').hide()
                                
    user = $(this).val()
    load_datacollection()
    clearTimeout(auto_load_thread)
    $('.blended_table tbody').empty()
    load_blended()
  })
  
  
  // Initiate a blend run or analyse all data sets
  $('button[name=analyse],button[name=blend]').click(function() {
     var ty = $(this).attr('name') == 'analyse' ? 1 : 0
                                                     
     if (ty) check = true
                                                      
     if (!$('.integrated tr.selected').length && !ty) {
        alert('You need to select some data sets to blend')
        return
     } else {
        var sel = ty ? '.integrated tr' : '.integrated tr.selected'
                                                     
        $.ajax({
            url: '/mc/ajax/blend/visit/' + visit,
            type: 'POST',
            data: { dcs: $(sel).map(function() { return parseInt($(this).attr('dcid')) }).get(), res: $('input[name=res]').val(), isigi: $('input[name=isigi]').val(), rfrac: $('input[name=rfrac]').val(), type: ty, sg: $('input[name=sg]').val() },
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
             url: '/mc/ajax/visit/' + visit + (user != null ? ('/user/'+user) : ''),
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
                $('.integrated tbody').empty()
             
                var no = 1
                $.each(json[1].reverse(), function(i,r) {
                    if (r['INT'] == 2) {
                    $('<tr dcid="'+r['DID']+'">'+
                      //'<td>'+r['DID']+'</td>'+
                      '<td>'+r['DIR']+'</td>'+
                      '<td>'+r['PREFIX']+'</td>'+
                      '<td>'+r['OST']+'</td>'+
                      '<td>'+r['STATS']['SG']+'</td>'+
                      '<td>'+r['STATS']['CELL']+'</td>'+
                      '<td>'+r['STATS']['R']+'</td>'+
                      '<td>'+r['STATS']['C']+'</td>'+
                      '<td>'+r['STATS']['RESH']+'</td>'+
                      '</tr>').hide().appendTo($('.integrated tbody')).fadeIn()
                       no++;
                    }
                })
                       
                $('.integrated td').unbind('click').click(function() {
                    $(this).parent().hasClass('selected') ? $(this).parent().removeClass('selected') : $(this).parent().addClass('selected')
                    count()
                })
             
                _plot()
             }
        })
  }
  
  load_datacollection()
  
  
  // List of blended data sets
  function load_blended() {
      var val = ['<img src="/templates/images/run.png" alt="Running"/>',
         '<img src="/templates/images/ok.png" alt="Completed"/>',
         '<img src="/templates/images/cancel.png" alt="Failed"/>']
  
      $.ajax({
             url: '/mc/ajax/blended/visit/' + visit + (user != null ? ('/user/'+user) : ''),
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
             
                $.each(json, function(i,r) {
                  blended[r['ID']] = r
                       
                  if ($('tr[run='+r['ID']+']').length) {
                    var last = $('tr[run='+r['ID']+']').attr('state')
                    
                    if (last != r['STATE']) {
                       var row = $('tr[run='+r['ID']+']').children('td')
                       $(row[4]).html(val[r['STATE']])
                       
                       if (r['STATE'] == 1) {
                         $(row[5]).html(r['SG'])
                         $(row[6]).html(r['STATS']['RESL'][1]+' - '+r['STATS']['RESH'][1]+' ('+r['STATS']['RESL'][3]+' - '+r['STATS']['RESH'][3]+')')
                         $(row[7]).html(r['STATS']['RMERGE'][1]+' ('+r['STATS']['RMERGE'][3]+')')
                         $(row[8]).html(r['STATS']['C'][1]+' ('+r['STATS']['C'][3]+')')
                         $(row[9]).html(r['STATS']['ISIGI'][1]+' ('+r['STATS']['ISIGI'][3]+')')
                         $(row[10]).html(r['STATS']['M'][1]+' ('+r['STATS']['M'][3]+')')
                       }
                    }
                       
                    $('tr[run='+r['ID']+']').attr('state', r['STATE'])
                    $('tr[run='+r['ID']+']').data('ids', r['IDS'])
                       
                  } else {
                       $('<tr run="'+r['ID']+'" state="'+r['STATE']+'">'+
                           '<td>'+r['ID']+'</td>'+
                           '<td>'+r['IDS'].length+'</td>'+
                           '<td>'+r['RFRAC']+'</td>'+
                           '<td>'+r['ISIGI']+'</td>'+
                           '<td>'+val[r['STATE']]+'</td>'+
                           (r['STATE'] == 1 ? (
                           '<td>'+r['SG']+'</td>'+
                           '<td>'+r['STATS']['RESL'][1]+' - '+r['STATS']['RESH'][1]+' ('+r['STATS']['RESL'][3]+' - '+r['STATS']['RESH'][3]+')</td>'+
                           '<td>'+r['STATS']['RMERGE'][1]+' ('+r['STATS']['RMERGE'][3]+')</td>'+
                           '<td>'+r['STATS']['C'][1]+' ('+r['STATS']['C'][3]+')</td>'+
                           '<td>'+r['STATS']['ISIGI'][1]+'('+r['STATS']['ISIGI'][3]+')</td>'+
                           '<td>'+r['STATS']['M'][1]+' ('+r['STATS']['M'][3]+')</td>') :
                            
                           ('<td>-</td>'+
                            '<td>-</td>'+
                            '<td>-</td>'+
                            '<td>-</td>'+
                            '<td>-</td>'+
                            '<td>-</td>'))+
                         '<td><button class="logv"></button> <button class="mtz"></button> &nbsp; '+(owner ? '<button class="delete"></button>':'')+'</td>'+
                         '</tr>').data('ids', r['IDS']).hide().prependTo($('.blended_table tbody')).fadeIn()
                  }
                       
                })
             
                $('.blended_table tbody tr').unbind('click').click(function() {
                    var ids = $(this).data('ids')
                                  
                    $('.blended_table tbody tr').removeClass('selected')
                    $(this).addClass('selected')
                                                                   
                    if (ids) {
                        $('.integrated tr').removeClass('selected')
                        $.each(ids, function(i,e) {
                           $('tr[dcid='+e+']').addClass('selected')
                        })

                    } else $('.integrated tr').removeClass('selected')
                                                                   
                    count()
                })
             
                $('button.logv').button({ icons: { primary: 'ui-icon-search' } }).unbind('click').click(function() {
                    $('#stats table tbody').html('')
                    var run = $(this).parent('td').parent('tr').attr('run')
                    if (run in blended) {
                        if (blended[run]['STATE'] == 1) {
                          $.each(blended[run]['STATS'], function(i,s) {
                            $('#stats table tbody').append('<tr>'+
                              '<td>'+s[0]+'</td>'+
                              '<td>'+s[1]+'</td>'+
                              '<td>'+s[2]+'</td>'+
                              '<td>'+s[3]+'</td>'+
                            '</tr>')
                          })
                          $('#stats').dialog('option', 'position', 'center');
                          $('#stats').dialog('option', 'title', 'Stats for Blend Run '+run)
                          //$('#stats')position({my: "center center", at: "center center", of: $(window)});
                          $('#stats').dialog('open')
                        }
                    }
                })
             
                $('button.mtz').button({ icons: { primary: 'ui-icon-arrowthick-1-s' } })
             
                $('button.delete').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
                        var run = $(this).parent('td').parent('tr').attr('run')
                        $.ajax({
                            url: '/mc/ajax/delete/visit/'+visit+'/run/'+run,
                            type: 'GET',
                            dataType: 'json',
                            timeout: 5000,
                            success: function(r){
                              if (r) {
                                $('tr[run='+run+']').remove()
                              }
                            }
                        })
                })
             
             }
      })
  
      auto_load_thread = setTimeout(function() { load_blended() }, 10000)
  }
  
  load_blended()
  
  
  // Check how many jobs are currently running
  function job_state() {
    $.ajax({
        url: '/mc/ajax/status/local/1',
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(jobs){
          jobs > 0 ? $('.jobs').parent('li').addClass('running') : $('.jobs').parent('li').removeClass('running')
          $('.jobs').html(jobs)
           
          if (check && last_job_count > 0 && jobs == 0) {
            _plot()
            check = false
          }
          last_job_count = jobs;
        }
    })
  
    setTimeout(function() { job_state() }, 5000)
  }
  
  job_state()
  
  
  // Count how many data sets are selected and highlight them on dendrogram
  function count() {
    $('.count').html($('.integrated tr.selected').length)
    $('.dendrogram .yAxis .tickLabel').removeClass('selected')
  
    $('.integrated tr.selected').each(function() {
        var dcid = $(this).attr('dcid')
        var idx = String(ids.indexOf(dcid) + 1)
        var tdx = t.indexOf(idx)
        $($('.dendrogram .yAxis .tickLabel')[tdx]).addClass('selected')
    })
  }
  
  
  // Plot dendrogram from CLUSTERS.txt
  function _plot() {
    $.ajax({
        url: '/mc/ajax/dend/visit/' + visit + (user != null ? ('/user/'+user) : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
            var din = json[1]
            ids = json[0]

            $('.dendrogram').css('height', ids.length*17)
           
            t = din[0][0][0].split(/\+/)
            var ticks = []
            for (var i = 0; i < t.length; i++) {
                var tr = $('.integrated tbody tr[dcid='+(ids[t[i]-1])+']')
                ticks.push([i,$(tr.children('td')[0]).html()+$(tr.children('td')[1]).html().replace(/####\.cbf/, '')])
            }
          
            var data = []
            var verts = {}
            var last_pos = {}
            $.each(t, function(i, k) { last_pos[String(k)] = { h: 0, m: i } });
  
            $.each(din.reverse(), function(i,d) {
                var h = d[1]
                $.each(d[0], function(j,e) {
                    var no = String(din[i][0][j]).split(/\+/)
                    var idx = t.indexOf(no[0])                       
                       
                    if (no.length == 1) {
                       data.push({data: [[last_pos[no[0]].h,idx], [h,idx]], color: 'blue'})
                       last_pos[no[0]].h = h
                       
                    } else {
                       var l = no.length - 1
                       var y = (last_pos[no[0]].m+last_pos[no[l]].m)/2
                       var dist = Math.abs(last_pos[no[0]].m-last_pos[no[l]].m)
                       var st = Math.min(last_pos[no[0]].m,last_pos[no[l]].m)
                       
                       if (!(e in verts)) verts[e] = {data: [[last_pos[no[0]].h,st], [last_pos[no[0]].h,st+dist]], color: 'blue'}
                       
                       data.push({data: [[last_pos[no[0]].h,y], [h,y]], color: 'blue'})
                       $.each(no, function(k,n) { last_pos[n].h = h; last_pos[n].m = y })
                       
                    }
                })
            })
            for (k in verts) data.push(verts[k])
           
            var opts = {
              selection: { mode: 'xy' },
              grid: {
                borderWidth: 0,
                tickColor: '#f4f4f4',
                backgroundColor: '#f4f4f4',
              },
              yaxis: {
                ticks: ticks,
              },
            }
          
            dend = $.plot($('.dendrogram'), data, opts)
            //setTimeout(4000, function(){$('.slider').slider('option', 'max', dend.getOptions().xaxes[0].max)});
        }
    })
  }
  
});

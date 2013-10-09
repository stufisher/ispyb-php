$(function() {
  
  var auto_load = 1;
  var auto_load_thread = null
  var dend = null
  var ids = []
  var t = []
  
  $('#dialog').dialog({ autoOpen: false, buttons: { 'Ok': function() { $(this).dialog('close'); } } });
  
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
        for (var i = Math.round(ranges.yaxis.from); i < Math.round(ranges.yaxis.to); i++) {
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
  }
  
  // Show/Hide dendrogram
  $('.dend_toggle').click(function() {
    $('.dend_wrap').slideToggle()
  })
  
  
  // Initiate a blend run or analyse all data sets
  $('button[name=analyse],button[name=blend]').click(function() {
     var ty = $(this).attr('name') == 'analyse' ? 1 : 0
                                                      
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
             url: '/mc/ajax/visit/' + visit,
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
             url: '/mc/ajax/blended/visit/' + visit,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
             
                $.each(json, function(i,r) {
                  if ($('tr[run='+r['ID']+']').length) {
                    var last = $('tr[run='+r['ID']+']').attr('state')
                    
                    if (last != r['STATE']) {
                       var row = $('tr[run='+r['ID']+']').children('td')
                       $(row[4]).html(val[r['STATE']])
                       $(row[5]).html(r['SG'])
                       $(row[6]).html(r['STATS']['RESL'][1]+' - '+r['STATS']['RESH'][1]+' ('+r['STATS']['RESL'][3]+' - '+r['STATS']['RESH'][3]+')')
                       $(row[7]).html(r['STATS']['RMERGE'][1]+' ('+r['STATS']['RMERGE'][3]+')')
                       $(row[8]).html(r['STATS']['C'][1]+' ('+r['STATS']['C'][3]+')')
                       $(row[9]).html(r['STATS']['ISIGI'][1]+' ('+r['STATS']['ISIGI'][3]+')')
                       $(row[10]).html(r['STATS']['M'][1]+' ('+r['STATS']['M'][3]+')')
                    }
                       
                    $('tr[run='+r['ID']+']').attr('state', r['STATE'])
                       
                  } else {
                       $('<tr run="'+r['ID']+'" state="'+r['STATE']+'">'+
                           '<td>'+r['ID']+'</td>'+
                           '<td>'+r['FILES'].length+'</td>'+
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
                         '<td><button class="delete"></button></td>'+
                         '</tr>').hide().prependTo($('.blended_table tbody')).fadeIn()
                  }
                       
                })
             
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
  
      auto_load_thread = setTimeout(function() { load_blended() }, 15000)
  }
  
  load_blended()
  
  
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
    $('.dendrogram .yaxis .tickLabel').removeClass('selected')
  
    $('.integrated tr.selected').each(function() {
        var dcid = $(this).attr('dcid')
        var idx = String(ids.indexOf(dcid) + 1)
        var tdx = t.indexOf(idx)
        $($('.dendrogram .yaxis .tickLabel')[tdx]).addClass('selected')
    })
  }
  
  
  function _plot() {
    $.ajax({
        url: '/mc/ajax/dend/visit/' + visit,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
            var din = json[1]
            ids = json[0]

            $('.dendrogram').css('height', ids.length*15)
           
            t = din[0][0][0].split(/\+/)
            var ticks = []
            for (var i = 0; i < t.length; i++) {
                var tr = $('.integrated tbody tr[dcid='+(ids[t[i]-1])+']')
                ticks.push([i,$(tr.children('td')[0]).html()+$(tr.children('td')[1]).html().replace(/####\.cbf/, '')])
            }
          
            data = []
            last = 1
            for (var i = 0; i < din.length; i++) {
                for (var j = 0; j < din[i][0].length; j++) {
                    if (!isNaN(din[i][0][j])) {
                        var idx = t.indexOf(String(din[i][0][j]))
                        data.push({data: [[last,idx,last], [din[i][1],idx,din[i][1]]], color: 'blue'})
                    } else {
                        var nos = din[i][0][j].split(/\+/)
                        var idx = t.indexOf(nos[0])
                        var y = idx + (nos.length-1)/2
                        data.push({data: [[din[i][1],y,din[i][1]], [last,y,last]], color: 'blue'})
                    }
                }
           
                //data.push({data: [[din[i][1],idx], [din[i][1],idx+(nos.length-1)]], color: 'blue'})
                last = din[i][1]
            }
           
          
            var opts = {
              selection: { mode: "xy" },
              grid: {
                borderWidth: 0,
              },
              yaxis: {
                ticks: ticks,
              },
            }
          
            dend = $.plot($('.dendrogram'), data, opts)
            setTimeout(1000, function(){$('.slider').slider('option', 'max', dend.getOptions().xaxes[0].max)});
        }
    })
  }
  
});

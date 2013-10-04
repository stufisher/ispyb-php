$(function() {
  
  var auto_load = 1
  var search = ''
  var dir = ''
  var plots = {}
  var state = 0
  var cells = {}
  
  $('#dialog').dialog({ autoOpen: false, buttons: { 'Ok': function() { $(this).dialog('close'); } } });
  
  // Search as you type
  var thread = null;
  
  $('input[name=search]').keyup(function() {
      clearTimeout(thread);
      $this = $(this);
      thread = setTimeout(function() {
            search = $this.val()
            $('.dc').remove()
            plots = {}
            if (search) load_datacollection();
      }, 500);
  });
  
  
  $('button[name=all]').click(function() {
    $('.dc').each(function(i,d){
       if (!$(this).hasClass('selected')) $(this).addClass('selected')
    })
  })
  
  
  $('input[name=start]').keyup(function() {
    var val = parseInt($(this).val())
    for (var i in plots) {
      var cur = plots[i].getSelection()
      var end = cur ? cur.xaxis.to : 10
      plots[i].setSelection({xaxis: { from: val, to: end } })
    }
  })

  
  $('input[name=end]').keyup(function() {
    var val = parseInt($(this).val())
    for (var i in plots) {
      var cur = plots[i].getSelection()
      var start = cur ? cur.xaxis.from : 0
      plots[i].setSelection({xaxis: { from: start, to: val } })
    }
  })
  
  
  $('input[name=minspots]').keyup(function() {
    var val = parseInt($(this).val())
    for (var i in plots) {
      var data = plots[i].getData()[0].data
      var list = []
      for (var j = 0; j < data.length; j++) list.push(data[j][1])
                                  
      var max = Math.max.apply(null, list)
      var c = $('div[dcid='+i+']')
      max >= val ? c.addClass('selected') : c.removeClass('selected')
      if (max < val) plots[i].setSelection({})
    }
  })  
  
  
  $('button[name=integrate').click(function() {
     if (!$('.dc.selected').length) {
        alert('You need to select some data sets to integrate')
        return
     } else {
        var integrate = []
        $('.dc.selected').each(function(i,e) {
            var sel = plots[$(e).attr('dcid')].getSelection()
            if (sel) integrate.push([parseInt($(e).attr('dcid')), parseInt(Math.round(sel.xaxis.from)), parseInt(Math.round(sel.xaxis.to))])
        })
                
        data = {int: integrate}
        var cc = ['a', 'b', 'c', 'alpha', 'beta', 'gamma']
        for (var i = 0; i < cc.length; i++) {
          data[cc[i]] = $('input[name='+cc[i]+']').val()
        }
                      
        data['res'] = $('input[name=res]').val()
        data['sg'] = $('input[name=sg]').val()
                                   
        $.ajax({
            url: '/mc/ajax/integrate/visit/' + visit,
            type: 'POST',
            data: data,
            dataType: 'json',
            timeout: 15000,
            success: function(r){
               if (r) state = 1
            }
        })

        $('#dialog').dialog('open')
     }
     
    
  })
  
  
  $('select[name=cells]').change(function() {
    var id = $(this).val()
    if (id in cells) {
        e = cells[id]
        $('input[name=sg]').val(e['SG'])
        $('input[name=a]').val(e['CELL_A'])
        $('input[name=b]').val(e['CELL_B'])
        $('input[name=c]').val(e['CELL_C'])
        $('input[name=alpha]').val(e['CELL_AL'])
        $('input[name=beta]').val(e['CELL_BE'])
        $('input[name=gamma]').val(e['CELL_GA'])
    }
  })
  
  function load_cells() {
    $.ajax({
        url: '/mc/ajax/cells/visit/' + visit + '/d/'+dir,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           cells = {}
           $('select[name=cells]').empty()
           $.each(json, function(i,e) {
             cells[e['ID']] = e
             $('select[name=cells]').append('<option value="'+e['ID']+'">'+e['DIR']+e['PREFIX']+' ('+e['SG']+': '+e['CELL_A']+','+e['CELL_B']+','+e['CELL_C']+','+e['CELL_AL']+','+e['CELL_BE']+','+e['CELL_GA']+') ['+e['TYPE']+']</option>')
           })
        }
    })
  }
  
  
  $('select[name=dir]').change(function() {
    dir = $(this).val()
    if (dir) {
        $('.dc').remove()
        plots = {}
        load_datacollection()
        load_cells()
    }
  })
  
  function load_dirs() {
    $.ajax({
        url: '/mc/ajax/dirs/visit/' + visit,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           $('select[name=dir]').empty()
           $.each(json, function(i,e) {
             $('select[name=dir]').append('<option value="'+e+'">'+e+'</option>')
           })
           $('select[name=dir]').trigger('change')
        }
    })
  }
  
  load_dirs()
  
  
  // Async load of data collections
  function load_datacollection() {
      var val = ['<img src="/templates/images/run.png" alt="Running"/>',
         '<img src="/templates/images/ok.png" alt="Completed"/>',
         '<img src="/templates/images/cancel.png" alt="Failed"/>']
  
      $.ajax({
             url: '/mc/ajax/visit/' + visit + (search ? ('/s/'+search) : '') + (dir ? ('/d/'+dir) : ''),
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
                $('.count').html(json[0]+' data collections')
             
                $.each(json[1].reverse(), function(i,r) {
                    if ($('.dc[dcid='+r['DID']+']').length > 0) {
                       var last = $('.dc[dcid='+r['DID']+']').attr('int')
                       
                       if (last != r['INT']) {
                           if (r['INT'] > 0) {
                           state = val[(r['INT']-1)]
                           if (r['INT'] == 2) state += '<ul>'+
                               '<li>R:'+r['STATS']['R']+'</li>'+
                               '<li>C:'+r['STATS']['C']+'</li>'+
                               '<li>Res:'+r['STATS']['RESH']+'</li>'+
                               '</ul>'
                           }
                       
                           $('.dc[dcid='+r['DID']+']').children('.state').html(state)
                           $('.dc[dcid='+r['DID']+']').attr('int', r['INT'])
                       }
                       
                       
                    } else {
                       
                        var state = ''
                        if (r['INT'] > 0) {
                          state = val[(r['INT']-1)]
                          if (r['INT'] == 2) state += '<ul>'+
                           '<li>R:'+r['STATS']['R']+'</li>'+
                           '<li>C:'+r['STATS']['C']+'</li>'+
                           '<li>Res:'+r['STATS']['RESH']+'</li>'+
                           '</ul>'
                        }
                           
                        $('<div class="dc" dcid="'+r['DID']+'" int="'+r['INT']+'">'+
                          '<div class="state">'+state+'</div>'+                      
                          '<h1>'+r['PREFIX']+'</h1>'+
                          '<h2>'+r['DIR']+'</h2>'+
                          '<div class="distl"></div>'+
                          '<span>&Omega; Start: '+r['OST']+'&deg; &Omega; Osc: '+r['OOS']+'&deg; | <a href="/dc/view/id/'+r['DID']+'" target="_blank">Images</a></span>'+
                          '</div>').hide().prependTo('.data_collections').slideDown()
                           
                    }
                })
             
                load_imq()
             
                $('.dc h1').unbind('click').click(function() {
                    $(this).parent().hasClass('selected') ? $(this).parent().removeClass('selected') : $(this).parent().addClass('selected')
                    if (!$(this).parent().hasClass('selected')) plots[$(this).parent('div').attr('dcid')].setSelection({})
                })
             }
      })
  
      if (auto_load) {
        auto_load_thread = setTimeout(function() {
            load_datacollection()
        }, 10000)
      }  
  
  }
  
  
  function load_imq() {
    $('.dc').each(function(i,d) {
      if (!plots[$(d).attr('dcid')]) {
        $.ajax({
             url: '/dc/ajax/imq/id/' + $(d).attr('dcid'),
             type: 'GET',
             dataType: 'json',
             timeout: 15000,
             success: function(j){
                 var options = {
                    xaxis: {
                        minTickSize: 1,
                        tickDecimals: 0,
             
                    },
                    selection: { mode: "x" },
                    grid: {
                        borderWidth: 0,
                    },
                    series: {
                        lines: { show: false },
                        points: {
                            show: true,
                            radius: 1,
                        }
                    },
                 }
             
                 plots[$(d).attr('dcid')] = $.plot($(d).children('div.distl'), [j[0], j[1]], options);
               
                 $(d).bind('plotselected', set_selected.bind(null,$(d).attr('dcid')))
                 $(d).bind('plotunselected', set_deselected.bind(null,$(d).attr('dcid')))
            }
        })
      }
    })
  }
  
  
  function set_selected(dcid, x1,x2) {
    if (x1 != null && x2 != null) $('div[dcid='+dcid+']').addClass('selected')
  }
  
  function set_deselected(dcid, e) {
    $('div[dcid='+dcid+']').removeClass('selected')
  }
  
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
  
  
});

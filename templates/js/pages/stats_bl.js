$(function() {

  if (t) {
    $('.filter.ty ul li').removeClass('current')
    $('.filter.ty ul li[id='+t+']').addClass('current')
  }
  
  // Filter by type
  $('.filter.ty ul li').click(function() {
    if ($(this).hasClass('current')) {
        $(this).removeClass('current')
        t = ''
        $('.type').html('Hour')
    } else {
        $('.filter ul li').removeClass('current')
        $(this).addClass('current')
        t = $(this).attr('id')
        $('.type').html($(this).html())
    }
    
    url = window.location.pathname.replace(/\/t\/\w+/, '')+(t ? ('/t/'+t) : '')
    window.history.pushState({}, '', url)
                           
    _get_stats()
  })
  
  var all_data = null
  //var selected_data = null
  
  var ops = {
    axisLabels: {
      show: true
    },
    series: {
       bars: {
       barWidth: .09,
       align: 'center'
       },
    },
    grid: {
        hoverable: true,
        borderWidth: 0,
    },
    tooltip: true,
    tooltipOpts: {
        content: "%s: %y.2"
    },
    xaxis: {
        rotateTicks: 45,
    },
    yaxes: [{}, { position: 'right', transform: function (v) { return -v; } }],
    xaxes: [{}],
  }
  
  $('.series').on('click', 'li', function(e) {
    if ($(e.target).is('input')) return
    $(this).children('input').trigger('click')
  })
  
  $('.series').on('change', 'input', function(e) {   
    _plot()
  })
  
  function _plot() {
    var pld = []
    $.each(all_data.data, function(bl, d) {
      if ($('input[name="'+bl+'"]').is(':checked')) {
        $.each(d, function(i, data) {
          var pl = {label: bl, data: data.data, yaxis: (i+1) }
          pl[data.series] = { show: true }
          pld.push(pl)
        })
      }
    })
  
    if (pld.length) $.plot($('#logon'), pld, ops)
  }
  
  function _get_stats() {
    $('#logon').addClass('loading')
    $.ajax({
        url: '/stats/ajax/bl'+(t ? ('/t/'+t) : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 35000,
        success: function(data){
           all_data = data
           ops.xaxis.ticks = data.ticks
           ops.xaxes[0].axisLabel = data.xaxis
           ops.yaxes[0].axisLabel = data.yaxis
           $('.plot_title').html(data.title)
           
           var opts = ''
           $.each(data.data, function(bl, d) {
             opts += '<li><input type="checkbox" name="'+bl+'" checked="checked"> '+bl+'</li>'
           })
           
           $('.series').html(opts)
           
           _plot()
           $('#logon').removeClass('loading')
        }
    })
  }
  
  _get_stats()
  
})
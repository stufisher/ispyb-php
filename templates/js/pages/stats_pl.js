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
  
  function _get_stats() {
    $('#logon').addClass('loading')
    $.ajax({
        url: '/stats/ajax/pl'+(t ? ('/t/'+t) : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 80000,
        success: function(data){
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
                    ticks: data.ticks,
                    rotateTicks: 45,
                },
                yaxes: [{ axisLabel: data.yaxis }, { position: 'right', transform: function (v) { return -v; } }],
                xaxes: [{ axisLabel: data.xaxis }],
            }
           
           $('.plot_title').html(data.title)
           
           pld = []
           $.each(data.data, function(bl, d) {
             $.each(d, function(i, data) {
                var pl = {label: bl, data: data.data, yaxis: (i+1) }
                if (i > 0) pl['lines'] = { show: true }
                else pl[data.series] = { show: true }
                pld.push(pl)
             })
           })
           
           $.plot($('#logon'), pld, ops)
           $('#logon').removeClass('loading')
        }
    })
  }
  
  _get_stats()
  
})
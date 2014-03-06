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
                           
    _get_logons()
  })
  
  function _get_logons() {
    $.ajax({
        url: '/stats/ajax/logon'+(t ? ('/t/'+t) : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(data){
            var ops = {
                series: {
                    bars: {
                        show: true,
                        barWidth: .9,
                        align: 'center'
                    },
                },
                grid: {
                    hoverable: true,
                    borderWidth: 0,
                },
                xaxis: {
                    tickDecimals: 0,
                },
                yaxis: {
                    tickDecimals: 0,
                }
            }
           
           if (t == 'wd') ops.xaxis.ticks = [[0,'Mon'], [1,'Tue'], [2,'Wed'], [3,'Thu'], [4,'Fri'], [5,'Sat'], [6,'Sun']]
           
           $.plot($('#logon'), data, ops)
        }
    })
  }
  
  _get_logons()
  
})
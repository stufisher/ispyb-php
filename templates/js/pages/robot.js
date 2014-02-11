$(function() {
  
  function getToolTip(x,y) {
    var v = ''
    for (var i = 0; i < ticks.length; i++) {
        if (ticks[i][0] == x) {
            v = ticks[i][1];
            break;
        }
    }
  
    return v + ': ' + y.toFixed(1) + 's';
  }
  
  var data = []
  $.each(bld, function(n,d) {
    data.push({ data: d, label: n })
  })
  
  options = {
      series: {
        lines: {
            show: true
        },
        points: {
            show: true
        }
      },
      xaxis: {
        ticks: ticks,
      },
      grid: {
          borderWidth: 0,
          hoverable: true,
          clickable: true
      },
      tooltip: true,
      tooltipOpts: {
        content: getToolTip,
      }
  }
         
  $.plot('#avg_time', data, options)
  
  $('#avg_time').bind("plothover", function (event, pos, item) {
    if (url) document.body.style.cursor = item ? 'pointer' : 'default';
  });    
  
  $('#avg_time').bind("plotclick", function (event, pos, item) {
    var v = ''
    for (var i = 0; i < ticks.length; i++) {
        if (ticks[i][0] == item.datapoint[0]) {
            v = rids[ticks[i][1]];
            break;
        }
    }
    if (v && url) window.location = '/robot/bl/'+item.series.label+'/run/'+v
  })
  
  $('.legend table tr').click(function() {
    window.location = '/robot/bl/'+$(this).children('td.legendLabel').html()
  })
  
});

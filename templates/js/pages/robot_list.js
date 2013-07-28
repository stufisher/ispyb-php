$(function() {
  
  function getToolTip(x,y) {
    v = ''
    for (var i = 0; i < avg_ticks.length; i++) {
        if (avg_ticks[i][0] == x) {
            v = avg_ticks[i][1];
            break;
        }
    }
  
    return v + ': ' + y.toFixed(1) + 's';
  }
  
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
        ticks: avg_ticks,
        rotateTicks: 80,
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
  
  data = {
      data: avg_time,
      color: 'rgb(100,100,100)',
  }
  
  $.plot('#avg_time', [data], options);
  
  $('#avg_time').bind("plothover", function (event, pos, item) {
    if (url) document.body.style.cursor = item ? 'pointer' : 'default';
  });    
  
  $('#avg_time').bind("plotclick", function (event, pos, item) {
    v = ''
    for (var i = 0; i < avg_ticks.length; i++) {
        if (avg_ticks[i][0] == item.dataIndex) {
            v = avg_ticks[i][1];
            break;
        }
    }
        
    if (v && url) window.location = '/robot/visit/'+v
  })
  
  if ($(window).width() <= 600) {
    $('.robot_actions').dataTable({
      'sPaginationType': 'full_numbers',
      'bScrollCollapse': true,
      'sScrollX': '100%',
      'aaSorting': [[0,'desc']]
    })
  } else {
    $('.robot_actions').dataTable({
      'sPaginationType': 'full_numbers',
      'aaSorting': [[0,'desc']]
    });
  }
  
});

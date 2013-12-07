$(function() {
  
  $('.logcontent').hide()
  $('tr.log').click(function() {
    $(this).next('.logcontent').fadeToggle()
  })
  
  function getToolTip(x, y) {
    len = 0
    e = ''
    for (i = 0; i < visit_info.length; i++) {
        v = visit_info[i]['data'][1]

        if (v[2] <= x && v[0] >= x && v[1] == y) {
            len = (v[0]-v[2])/1000
            if (y == 2 || y == 4) e = visit_info[i]['status']
            break

        }
        
    }
  
     return (y == 1 ? 'Data Collection': (y == 2 ? 'Robot Action' : (y == 3 ? 'Edge Scan': 'Issue'))) + ': ' + len + 's ' + e
  }
  
  options = {
      grid: {
          borderWidth: 0,
      },
  
      selection: { mode: "x" },

      bars: {
        horizontal: true,
        show: true,
        lineWidth: 0.5,
        barWidth: 0.8,
        stack: true,
      },
  
      xaxis: {
            mode: 'time',
            timezone: 'browser',
            //timeformat: "%H:%M",
            min: start,
            max: end,
      },
  
      yaxis: {
        show: false,
      }
  }
  
  options2 = $.extend(true, {}, options);
  options2.tooltip = true;
  options2.grid.hoverable = true
  options2.tooltipOpts = { content: getToolTip }
  
  var main = $.plot('#avg_time', visit_info, options2);
  
  $('#avg_time').bind("plotselected", function (event, ranges) {
    $.plot($('#avg_time'), visit_info,
        $.extend(true, {}, options2, { xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to },
    }));
                      
    overview.setSelection(ranges, true);
  });
  
  
  var overview = $.plot('#overview', visit_info, options);
  $('.plot_container button[name=reset]').button({ icons: { primary: 'ui-icon-refresh' }, text: false }).click(function() { main.setSelection({xaxis: { from: start, to: end } }); overview.clearSelection(); })
  
  $('#overview').bind("plotselected", function (event, ranges) {
    main.setSelection(ranges);
  });
  
  
  var dc_opts = {
     bars: {
        show: true,
     },
     grid: {
        borderWidth: 0,
     },
     xaxis: {
        ticks: dc_hist2[0],
        tickSize: 1,
        tickLength: 0,
     },
  }
  
  $.plot('#dc_hist', [dc_hist[1]], dc_opts)
  
  $.plot('#dc_hist2', [dc_hist2[1]], dc_opts)
  
  $.plot('#visit_pie', pie, {
         series: {
            pie: {
                show: true,
                radius: 1,
                label: {
                    show: true,
                    radius: 2/3,
                    formatter: labelFormatter,
                    //threshold: 0.1
                }
            }
         },
         legend: {
            show: false
         },
         grid: {
            hoverable: true,
         }     
    });
  
  
    function labelFormatter(label, series) {
        return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
    }
  
    if ($(window).width() <= 600) {
      $($('.robot_actions')[0]).dataTable({
        'sPaginationType': 'full_numbers',
        'bScrollCollapse': true,
        'sScrollX': '100%',
        'aaSorting': [[0,'desc']]
      });
    } else {
      $($('.robot_actions')[0]).dataTable({
        'sPaginationType': 'full_numbers',
        'aaSorting': [[0,'desc']]
      });
    }

});

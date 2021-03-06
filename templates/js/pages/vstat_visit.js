$(function() {
  
  $('a.dcs').button({ icons: { primary: 'ui-icon-search' } })
  
  $('.logcontent').hide()
  $('tr.log').click(function() {
    $(this).next('.logcontent').fadeToggle()
  })
  
  function getToolTip(x, y) {
    len = 0
    e = ''
    var ty = ''
    for (i = 0; i < visit_info.length; i++) {
        v = visit_info[i]['data'][1]

        if (v[2] <= x && v[0] >= x && v[1] == y) {
            len = (v[0]-v[2])/1000
            if (y == 2 || y == 4) e = visit_info[i]['status']
            ty = visit_info[i]['type']
  
            break

        }
        
    }
  
     return (y == 1 ? (ty == 'ai' ? 'Auto Indexing' : 'Data Collection') : (y == 2 ? 'Robot Action' : (y == 3 ? (ty == 'mca' ? 'MCA Spectrum' : 'Edge Scan'): 'Issue'))) + ': ' + len + 's ' + e
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
            timezone: 'Europe/London',
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
  options2.grid.clickable = true
  options2.tooltipOpts = { content: getToolTip }
  
  var main = $.plot('#avg_time', visit_info, options2);
  
  $('#avg_time').bind("plotselected", function (event, ranges) {
    $.plot($('#avg_time'), visit_info,
        $.extend(true, {}, options2, { xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to },
    }));
                      
    overview.setSelection(ranges, true);
  });
  
  $('#avg_time').bind("plotclick", function (event, pos, item) {
    if (!item) return
    if (item.datapoint[1] == 1 || item.datapoint[1] == 3) {
        window.location.href = '/dc/visit/'+visit+'/t/'+item.series.type+'/id/'+item.series.id
    }
  })
  
  
  var overview = $.plot('#overview', visit_info, options);
  $('.plot_container button[name=reset]').button({ icons: { primary: 'ui-icon-refresh' }, text: false }).click(function() { main.setSelection({xaxis: { from: start, to: end } }); overview.clearSelection(); })
  
  $('#overview').bind("plotselected", function (event, ranges) {
    main.setSelection(ranges);
  });
  
  
  var dc_opts = {
     bars: {
        show: true,
        align: 'center',
     },
     grid: {
        borderWidth: 0,
        hoverable: true,
     },
     xaxis: {
        tickSize: 1,
        tickLength: 0,
     },
     tooltip: true,
     tooltipOpts: {
       content: "%y"
     },
  }
  
  $.plot('#dc_hist', [dch], dc_opts)
  
  $.plot('#dc_hist2', [slh], dc_opts)
  
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
        return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + Math.round(series.percent) + "%<br />(" + series.data[0][1].toFixed(1) + "hr)</div>";
    }
  
    var dt = $('.robot_actions.robot').dataTable({
        'sPaginationType': 'full_numbers',
        'aaSorting': [[0,'desc']]
    });
  
    $(window).resize(function() { _resize() })
  
    function _resize() {
      if (!$('.robot_actions.robot').length) return
      $.each([1,2,3,4,5,6],function(i,n) {
        dt.fnSetColumnVis(n, !($(window).width() <= 600))
      })
    }
  
    _resize()

});

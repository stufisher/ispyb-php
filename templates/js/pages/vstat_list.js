$(function() {

    function showTooltip(x, y, contents) {
        $('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 5,
            left: x + 5,
            border: '1px solid #fdd',
            padding: '2px',
            'background-color': '#fee',
            opacity: 0.80,
            'border-radius': '5px',
        }).appendTo("body").fadeIn(200);
    }

    var previousPointidx = null;
    var previousPointsx = null;
    $('#visit_breakdown').bind("plothover", function (event, pos, item) {
        if (item) {
            document.body.style.cursor = 'pointer';
            if (previousPointidx != item.dataIndex || previousPointsx != item.seriesIndex) {
                previousPointidx = item.dataIndex;
                previousPointsx = item.seriesIndex
                $("#tooltip").remove();
                
                showTooltip(item.pageX, item.pageY, item.series.label + ': ' + (item.datapoint[1]-item.datapoint[2]).toFixed(2) + 'hr');
            }
        } else {
            document.body.style.cursor = 'default';
            $("#tooltip").remove();
            previousPointidx = null;
            previousPointsx = null;
        }
    });  

    $('#visit_breakdown').bind("plotclick", function (event, pos, item) {
        var id = null
        id = vids.slice(page*pp, page*pp+pp)[item.dataIndex]
        console.log(id)
        if (id) window.location = '/vstat/visit/'+prop+'-'+id
    })
  
  
    $('.robot_actions').dataTable({
    'sPaginationType': 'full_numbers',
  });
  

  // Paging
  var pp = 25
  page = 0
  var pc = Math.floor(visit_ticks.length/pp)
  if (visit_ticks.length % pp != 0) pc++;
  
  var pgs = []
  for (var i = 0; i < pc; i++) pgs.push('<li'+(i==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
  
  $('.pages').html('<ul>'+pgs.join('')+'</ul>')
  
  $('.pages a').unbind('click').click(function() {
       $('.pages li').removeClass('selected')
       page = parseInt($(this).attr('href').replace('#', '')) - 1
       $($('.pages li')[page]).addClass('selected')
       plot()
       return false
  })
  
  
  // Plot bags
  function plot() {
      var options = {
          series: {
              bars: {
                  show: true,
                  barWidth: .9,
                  align: "center"
              },
              stack: true
          },
          xaxis: {
            ticks: visit_ticks.slice(page*pp, page*pp+pp),
            rotateTicks: 80,
          },
          yaxis: {
              min: 0,
              max: 24.1,
              ticks: [0,8,16,24]
          },
          grid: {
              borderWidth: 0,
              hoverable: true,
              clickable: true
          },
          legend: {
            noColumns: 6,
            container: $('.legend'),
          }
      }
      
      var data = [{ data: visit_data[0].slice(page*pp, page*pp+pp),
               label: 'Startup',
               color: 'yellow',
              }, { data: visit_data[1].slice(page*pp, page*pp+pp),
               label: 'Data Collection',
               color: 'green',
              }, { data: visit_data[2].slice(page*pp, page*pp+pp),
               label: 'Edge Scans',
               color: 'orange'
              }, { data: visit_data[3].slice(page*pp, page*pp+pp),
               label: 'Robot Actions',
               color: 'blue'
              }, { data: visit_data[5].slice(page*pp, page*pp+pp),
               label: 'Thinking',
               color: 'purple',
              }, { data: visit_data[4].slice(page*pp, page*pp+pp),
               label: 'Remaining',
               color: 'red',
              }]
      
      $.plot('#visit_breakdown', data, options);
  }
  
  plot()
  
});

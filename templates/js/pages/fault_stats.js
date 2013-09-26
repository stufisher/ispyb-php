$(function() {
  $.plot('#ovr_pie', ovr_pie, {
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
            clickable: true,
         }     
    });

  $('#ovr_pie').bind('plotclick', _on_sys_click)
  _load_component(ovr_pie[0].sid, ovr_pie[0].label)
  
  function _on_sys_click(evt, pos, obj) {
    var sys = null
    for (var i = 0; i < ovr_pie.length; i++) {
        if (ovr_pie[i].label == obj.series.label) {
            sys = ovr_pie[i]
            break
        }
    }
  
    if (sys != null) _load_component(sys.sid, sys.label)
  }
  
  
  function _load_component(sys, label) {
    if (sys in sys_pie) {
      $.plot('#system_pie', sys_pie[sys], {
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
                clickable: true,
             }     
        });
        $('#system_pie').siblings('p').children('span').html(label)
    }
  }
  
  function labelFormatter(label, series) {
  return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
  }
  
})
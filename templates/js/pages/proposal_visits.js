$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/proposal/ajax/visits/prop/'+prop+'/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            bFilter: false,
            fnServerData: function (sSource, aoData, fnCallback, oSettings) {
                     oSettings.jqXHR = $.ajax({
                                'dataType': 'json',
                                'type': 'GET',
                                'url': sSource,
                                'data': aoData,
                                'success': function(json) {
                                    fnCallback(json)
                                    _map_callbacks()
                                }
                            })
            }                                
  }
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  $('.robot_actions').dataTable(dt)
  
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' } })
    $('a.stats').button({ icons: { primary: 'ui-icon-image' } })
    $('a.report').button({ icons: { primary: 'ui-icon-document' } })
    $('a.process').button({ icons: { primary: 'ui-icon-gear' } })
  }
  
  
})

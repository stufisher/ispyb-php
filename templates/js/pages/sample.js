$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            //fnDrawCallback: _map_callbacks(),
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            }
  }
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  $('.robot_actions').dataTable(dt).fnSetFilteringDelay()
  
  
  function _map_callbacks() {
    setTimeout(function() {
      $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
    }, 500)
  }


})

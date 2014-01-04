$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/contact/ajax/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            bFilter: false,
            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            }
  }
  
  /*if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)*/
  
  var dt = $('.robot_actions').dataTable(dt)
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
  $.each([2,3],function(i,n) {
         dt.fnSetColumnVis(n, !($(window).width() <= 600))
         })
  }
  
  _resize()
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
  }
  
  $('a.add').button({ icons: { primary: 'ui-icon-plus' } })

})
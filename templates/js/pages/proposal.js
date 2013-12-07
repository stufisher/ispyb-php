$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/proposal/ajax/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
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
  
  $('.robot_actions').dataTable(dt)
  
  
  function _map_callbacks() {
      $('button.activate').button({ icons: { primary: 'ui-icon-check' }, text: false }).unbind('click').click(function() {
        var r = $(this).parent('td').parent('tr')
        $.ajax({
            url: '/proposal/ajax/set/prop/'+r.children('td').eq(1).html()+r.children('td').eq(2).html(),
            type: 'GET',
            dataType: 'text',
            timeout: 5000,
            success: function(json){
               window.location = '/proposal/visits'
            }
               
        })
      })
  
      $('table.proposals tr').unbind('click').click(function() {
        $.ajax({
            url: '/proposal/ajax/set/prop/'+$(this).children('td').eq(1).html()+$(this).children('td').eq(2).html(),
            type: 'GET',
            dataType: 'text',
            timeout: 5000,
            success: function(json){
               window.location = '/proposal/visits'
            }
               
        })
      })
  }
  
})

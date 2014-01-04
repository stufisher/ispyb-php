$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/pid/'+pid+'/',
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
  
  var dt = $('.robot_actions').dataTable(dt)
  $('.table input').focus()
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
  $.each([0,3,4,5,6,7],function(i,n) {
         dt.fnSetColumnVis(n, !($(window).width() <= 600))
         })
  }
  
  _resize()
  
  function _map_callbacks() {
    //setTimeout(function() {
      $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
    //}, 500)
  }

  $.each(['name', 'acronym', 'mass'], function(i,e) {
    $('.'+e).editable('/sample/ajax/updatep/pid/'+pid+'/ty/'+e+'/', {
                       height: '100%',
                       type: 'text',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');
  })
  
  
  $('.seq').editable('/sample/ajax/updatep/pid/'+pid+'/ty/seq/', {
    type: 'textarea',
    rows: 5,
    width: '100%',
    submit: 'Ok',
    style: 'display: inline',
    
  }).addClass('editable');
})
$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            fnDrawCallback: _iconify,
  }
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  $('.robot_actions').dataTable(dt)
  
  $('a.add').button({ icons: { primary: 'ui-icon-plus' } })
  
  function _iconify() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
    $('a.label').button({ icons: { primary: 'ui-icon-print' }, text: false })
  }
})
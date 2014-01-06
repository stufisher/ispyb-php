$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bAutoWidth: false,
            aaSorting: [[ 0, 'desc' ]],
            fnDrawCallback: _iconify,
  }
  
  /*if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)*/
  
  var dt = $('.robot_actions').dataTable(dt)
  $('.table input').focus()
  
  $('input.search-mobile').keyup(function() {
    $('.dataTables_filter input').val($(this).val()).trigger('keyup')
  }).parent('span').addClass('enable')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  $('a.add').button({ icons: { primary: 'ui-icon-plus' } })
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
    $.each([2,3,5,6],function(i,n) {
      dt.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  }
  
  _resize()
  
  function _iconify() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).hide()
    $('a.label').button({ icons: { primary: 'ui-icon-print' }, text: false })
  
    $('table.shipments tr').unbind('click').click(function() {
      window.location = $('td:last-child a.view', this).attr('href')
    })
  }
})
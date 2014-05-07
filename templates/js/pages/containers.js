$(function() {
  var dt = {
    sPaginationType: 'full_numbers',
    bProcessing: true,
    bServerSide: true,
    sAjaxSource: '/shipment/ajax/containersall/',
    bAutoWidth:false ,
    aaSorting: [[ 0, 'desc' ]],
    fnServerData: function ( sSource, aoData, fnCallback ) {
      $.getJSON( sSource, aoData, function (json) {
        fnCallback(json)
        _map_callbacks()
      })
    }
  }
  
  var dt = $('.robot_actions').dataTable(dt)
  $('.table input').focus()
  
  $('input.search-mobile').keyup(function() {
    $('.dataTables_filter input').val($(this).val()).trigger('keyup')
  }).parent('span').addClass('enable')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
    $.each([1,2,4],function(i,n) {
      dt.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  }
  
  _resize()
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).hide()
    $('table.containers tbody tr').unbind('click').click(function() {
      window.location = $('td:last-child a.view', this).attr('href')
    })
  }
})
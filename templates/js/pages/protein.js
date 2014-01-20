$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/proteins/',
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
  
  /*if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)*/
  
  var dt = $('.robot_actions').dataTable(dt).fnSetFilteringDelay()
  $(window).resize(function() { _resize() })
  
  function _resize() {
    $.each([2,3],function(i,n) {
      dt.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  }
  
  _resize()
  
  $('.table input').focus()
  
  $('input.search-mobile').keyup(function() {
    $('.dataTables_filter input').val($(this).val()).trigger('keyup')
    }).parent('span').addClass('enable')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).hide()
  
    $('table.proteins tbody tr').unbind('click').click(function() {
      window.location = $('td:last-child a.view', this).attr('href')
    })
  }
  
  $('a.add').button({ icons: { primary: 'ui-icon-plus' } })

})
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
  
  /*if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)*/
  
  var dt = $('.robot_actions').dataTable(dt)
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
    $.each([1,4,5,6],function(i,n) {
      dt.fnSetColumnVis(n, !($(window).width() <= 600))
    })
  }
  
  _resize()
  
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).hide()
    $('a.stats').button({ icons: { primary: 'ui-icon-image' }, text: false })
    $('a.report').button({ icons: { primary: 'ui-icon-document' }, text: false })
    $('a.export').button({ icons: { primary: 'ui-icon-extlink' }, text: false })
    $('a.process').button({ icons: { primary: 'ui-icon-gear' }, text: false })
  
  
    $('table.visits tr').unbind('click').click(function() {
      window.location = $('td:last-child a.view', this).attr('href')
    })
  
    $('.comment').each(function(i,e) {
        var vid = $(this).parent('td').parent('tr').children('td').eq(2).html()
        $(e).editable('/proposal/ajax/comment/prop/'+prop+'-'+vid+'/', {
          width: '100px',
          height: '100%',
          type: 'text',
          submit: 'Ok',
          style: 'display: inline',
        }).addClass('editable');
      })
  }
  
  
})

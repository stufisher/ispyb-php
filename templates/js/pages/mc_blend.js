$(function() {
  
  $('input[name=rmerge]').keyup(function() {
    var val = parseFloat($(this).val())
    $('.integrated tr').each(function(i,e) {
        var r = parseFloat($($(this).children('td')[5]).html())
        r < val ? $(this).addClass('selected') : $(this).removeClass('selected')
                             
    })
  })
  
  $('button[name=analyse]').click(function() {
     if (!$('.integrated tr.selected').length) {
        alert('You need to select some data sets to blend')
        return
     } else {
        $.ajax({
            url: '/mc/ajax/blend/visit/' + visit,
            type: 'POST',
            data: { dcs: $('.integrated tr.selected').map(function() { return parseInt($(this).attr('dcid')) }).get() },
            dataType: 'json',
            timeout: 15000,
            success: function(r){
               if (r) state = 1
            }
        })
                                   
     }
     
    
  })
  
  
  function load_datacollection() {
      $.ajax({
             url: '/mc/ajax/visit/' + visit,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
                $('.integrated tbody').empty()
             
                $.each(json[1].reverse(), function(i,r) {
                    if (r['INT'] == 2) {
                    $('<tr dcid="'+r['DID']+'">'+
                      '<td>'+r['DIR']+'</td>'+
                      '<td>'+r['PREFIX']+'</td>'+
                      '<td>'+r['OST']+'</td>'+
                      '<td>'+r['STATS']['SG']+'</td>'+
                      '<td>'+r['STATS']['CELL']+'</td>'+
                      '<td>'+r['STATS']['R']+'</td>'+
                      '<td>'+r['STATS']['C']+'</td>'+
                      '<td>'+r['STATS']['RESH']+'</td>'+
                      '</tr>').appendTo($('.integrated tbody'))
                    }
                })
                       
                $('.integrated td').unbind('click').click(function() {
                    $(this).parent().hasClass('selected') ? $(this).parent().removeClass('selected') : $(this).parent().addClass('selected')
                })
             }
        })
    }
  
    load_datacollection()
});

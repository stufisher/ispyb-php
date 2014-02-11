$(function() {

  function _get_users() {
    $.ajax({
        url: '/stats/ajax',
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(users){
          $.each(users, function(i,u) {
           if ($('table.users tbody tr[fedid='+u['USERNAME']+']').length) {
             var r = $('table.users tbody tr[fedid='+u['USERNAME']+']')
             r.children('td').eq(1).html(u['COMMENTS'])
             r.children('td').eq(2).html(u['TIME'])
                 
           } else {
              $('<tr fedid="'+u['USERNAME']+'">'+
                    '<td>'+u['NAME']+'</td>'+
                    '<td>'+u['COMMENTS']+'</td>'+
                    '<td>'+u['TIME']+'</td>'+
                '</tr>').hide().appendTo($('table.users tbody')).fadeIn()
           }
          })
        }
    })
  
    setTimeout(function() { _get_users() }, 2000)
  }
  
  _get_users()

      $.ajax({
        url: '/stats/ajax/last',
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(users){
          $.each(users, function(i,u) {
              $('<tr fedid="'+u['USERNAME']+'">'+
                    '<td>'+u['NAME']+'</td>'+
                    '<td>'+u['COMMENTS']+'</td>'+
                    '<td>'+u['TIME']+'</td>'+
                '</tr>').hide().appendTo($('table.activity tbody')).fadeIn()
          })
        }
    })
  
})
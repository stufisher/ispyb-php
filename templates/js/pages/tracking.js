$(function() {
  var thread;
  var dewar;
  var location
  var step = 0;
  
  $('.page button.reset').click(function() {
    window.location.href = '/tracking'
  })
  
  $('.page input').focus().keyup(function() {
    clearTimeout(thread)
    thread = setTimeout(function() {
      if ($('.page input').val()) {
                        
        // Get dewar
        if (!step) {
          dewar = $('.page input').val()
          step = 1
          $('.page h1.title').html('Location')
          $('.page input').val('')
                        
        // Get location
        } else if (step == 1) {
          location = $('.page input').val()
          
          $('.page input').hide()
          $('.page h1.title').html('Confirm')
                        
          $('.page h1').after('<form method="post">'+
            '<p>Dewar: '+dewar+'<input type="hidden" name="dewar" value="'+dewar+'" /></p>'+
            '</p>Location: '+location+'<input type="hidden" name="location" value="'+location+'" /></p>'+
            '<input type="hidden" name="submit" value="1" />'+
            '<button type="submit" name="go">Confirm</button>'+
            '</form>')
        }
      }
    }, 400)
  })

})
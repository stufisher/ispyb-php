$(function() {
  $('#add_contact').validate({
    errorElement: 'span',
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
      cardname: {
        wwdash: true
      }
                             
    }
  })

})
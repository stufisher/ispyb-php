$(function() {
  $.validator.addMethod('wwdash', function(value, element) {
    return this.optional(element) || /^(\w|\-)+$/.test(value);
  }, "This field must contain only letters numbers, underscores, and dashes")
  
  $('#add_protein').validate({
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        name: {
            wwdash: true,
        },
                             
        acronym: {
            wwdash: true,
        }
    }
  })
})
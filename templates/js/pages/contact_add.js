$(function() {
  $('#add_contact').validate({
    errorElement: 'span',
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
      cardname: {
        wwdash: true
      },
      familyname: {
        wwdash: true
      },
      givenname: {
        wwdash: true
      },
      labname: {
        wwsdash: true
      },
      courier: {
        wwsdash: true
      },
      courieraccount: {
        wwdash: true
      },
      billingreference: {
        wwsdash: true
      },
      customsvalue: {
        digits: true
      },
      transportvalue: {
        digits: true
      },
      address: {
        wwsdash: true
      }
    }
  })

})
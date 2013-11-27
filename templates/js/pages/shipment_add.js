$(function() {
  $('input[name=shippingdate]').datepicker({ dateFormat: "dd-mm-yy" });
  $('input[name=deliverydate]').datepicker({ dateFormat: "dd-mm-yy" });
  
  $.validator.addMethod('wwdash', function(value, element) {
    return this.optional(element) || /^(\w|\-)+$/.test(value);
  }, "This field must contain only letters numbers, underscores, and dahses")
  
  $('#add_shipment').validate({
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        shippingname: {
            wwdash: true,
        }
    }
  })
})
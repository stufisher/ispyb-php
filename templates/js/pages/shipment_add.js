$(function() {
  $('input[name=shippingdate]').datepicker({ dateFormat: "dd-mm-yy" });
  $('input[name=deliverydate]').datepicker({ dateFormat: "dd-mm-yy" });
  
  $('#add_shipment').validate({ validClass: 'fvalid', errorClass: 'ferror' })
})
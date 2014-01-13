// Additional methods for jquery validate


// Word characters, dash, underscore and spaces
$.validator.addMethod('wwsdash', function(value, element) {
  return this.optional(element) || /^(\w|\s|\-)+$/.test(value);
}, "This field must contain only letters numbers, spaces, underscores, and dashes")


// Word characters, dash, underscore
$.validator.addMethod('wwdash', function(value, element) {
  return this.optional(element) || /^(\w|\-)+$/.test(value);
}, "This field must contain only letters numbers, underscores, and dashes")


// File extesion
$.validator.addMethod("extension", function(value, element, param) {
  param = typeof param === "string" ? param.replace(/,/g, '|') : "png|jpe?g|gif";
  return this.optional(element) || value.match(new RegExp(".(" + param + ")$", "i"));
}, "Please select a file with a valid extension.")


// Datetime 
$.validator.addMethod("datetime", function(value, element) {
  return this.optional(element) || /^\d\d-\d\d-\d\d\d\d \d\d:\d\d$/.test(value);
}, "Please specify a valid date and time");


// European date with dashes
$.validator.addMethod("edate", function(value, element) {
  return this.optional(element) || /^\d\d-\d\d-\d\d\d\d$/.test(value);
}, "Please specify a valid date");
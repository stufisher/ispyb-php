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
  
  /*
  $('.progress').progressbar({ value: 0 });
  $('#add_pdb').dialog({ title: 'Add PDB', autoOpen: false, buttons: { 'Add': function() { _add_pdb() }, 'Cancel': function() { $(this).dialog('close') } } });
  
  $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).click(function(i,e) { $('#add_pdb').dialog('open') })
  
  
  // Upload new pdb file
  function _add_pdb() {
    var n = $('#add_pdb input[name=name]').val()
    var file = $('input[name=pdb_file]')[0].files[0]
  
    if (file && n) {
      var fd = new FormData($('form#ap')[0])
      $.ajax({
        url: '/sample/ajax/addpdb',
        type: 'POST',
        data: fd,
        dataType: 'json',
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr()
          if(myXhr.upload) myXhr.upload.addEventListener('progress', _upload_progress, false)
          return myXhr;
        },
             
        cache: false,
        contentType: false,
        processData: false,
             
        success: function(json){
          $('#add_pdb').dialog('close')
          _get_pdbs()
        }
      })
    }
  }
  
  function _upload_progress(e) {
    var pc = (e.loaded / e.total)*100;
    $('.progress').progressbar({ value: pc });
  }*/
  
  //$('input[name=')
  
  // Get list of pdbs for proposal
  function _get_pdbs() {
    $.ajax({
      url: '/sample/ajax/pdbs',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = '<option value="">&nbsp;</option>'
        $.each(json, function(i,p) {
          pdb_out += '<option value="'+p['PDBID']+'">'+p['NAME']+'</option>'
        })
           
        $('select[name=pdb]').html(pdb_out)
      }
    })
  }
  _get_pdbs()
  
})

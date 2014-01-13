$(function() {  
  $('#add_protein').validate({
    errorElement: 'span',
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        name: {
            wwdash: true,
        },
                             
        acronym: {
            wwdash: true,
        },
                             
        mass: {
            number: true,
        }
    }
  })
  
  
  
  function _map_change() {
    $('input.new_pdb').unbind('change').change(function() { _file_change(this) })
    $('button.delete').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).unbind('click').click(function(e) {
      e.preventDefault()
      if ($('span.file').length > 1) $(this).parent('span').remove()
    })
  
    $('input.new_pdb').each(function() { $(this).rules('add', { extension: 'pdb' }) })
  }
  _map_change()

  function _file_change(el) {
    if ($(el).val()) {
      if ($('input.new_pdb').index($(el)) == $('input.new_pdb').length - 1) {
        $('<span class="file">'+
          '  <input type="file" class="new_pdb" name="new_pdb[]" />'+
          '  <button class="delete">Delete File</button>'+
          '</span>').appendTo('div.pdb')
        _map_change()
      }
    }
  }
  
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

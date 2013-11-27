$(function() {
  
  function _map_callbacks() {
      $('button.delete').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
                                                                                                        
      })


      $('button.view').button({ icons: { primary: 'ui-icon-search' } }).unbind('click').click(function() {
        window.location.href = '/sample/sid/'+$(this).parent('td').parent('tr').attr('sid')
      })
  
      $('button.edit').button({ icons: { primary: 'ui-icon-pencil' } }).unbind('click').click(function() {
        var r = $(this).parent('td').parent('tr')
        var sid = r.attr('sid')
        var p = r.children('td').eq(1).attr('pid')
        var sg = r.children('td').eq(3).html()
        r.children('td').eq(1).html('<select class="protein" name="p"></select>')
        r.find('[name=p]').combobox()
                                                                                              
        _get_proteins(function() {
            $(r).find('[name=p]').combobox('value', p)
        })
                                                  
        r.children('td').eq(2).html('<input type="text" name="n" value="'+r.children('td').eq(2).html()+'" />')
        r.children('td').eq(3).html('<select name="sg">'+sg_ops+'</select>')
        r.find('[name=sg]').val(sg)
                                                                                              
        r.children('td').eq(4).html('<input type="text" name="c" value="'+r.children('td').eq(4).html()+'" />')

        r.children('td').eq(6).html('<button class="save"></button>')
                                                                                              
        $('button.save', r).button({ icons: { primary: 'ui-icon-check' } }).click(function() {
            var r = $(this).parent('td').parent('tr')
            $.ajax({
                url: '/shipment/ajax/updates/cid/'+cid,
                type: 'POST',
                data: { sid: r.attr('sid'),
                   n: $('input[name=n]', r).val(),
                   p: $('select[name=p]', r).combobox('value'),
                   sg: $('select[name=sg]', r).val(),
                   c: $('input[name=c]', r).val(),
                   pos: r.children('td').eq(0).html(),
                         },
                dataType: 'json',
                timeout: 5000,
                success: function(status){
                    _get_samples()
                }
            })
        })
      })
  }
  
  // Get protein acronyms
  function _get_proteins(fn) {
    var old = $('select.protein').map(function(i,e) { return $(e).combobox('value') }).get()

    $.ajax({
      url: '/shipment/ajax/pro',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var opts = '<option value="-1"></option>'
        $.each(json, function(i,p) {
            opts += '<option value="'+p['PROTEINID']+'">'+p['ACRONYM']+'</option>'
        })
        
        $('select.protein').html(opts)
           
        $('select.protein').each(function(i,e) {
            if (old[i] > -1) $(e).combobox('value', old[i])
        })
           
        if (fn) fn()
      }
    })  
  
  }
  
  
  // Get sample list for container
  function _get_samples() {
    $.ajax({
      url: '/shipment/ajax/samples/cid/'+cid,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        $('.samples tbody').empty()
        $.each(json, function(i,s) {
          $('<tr sid="'+s['BLSAMPLEID']+'">'+
            '<td>'+s['LOCATION']+'</td>'+
            '<td pid="'+s['PROTEINID']+'">'+s['ACRONYM']+'</td>'+
            '<td>'+s['NAME']+'</td>'+
            '<td>'+(s['SPACEGROUP']?s['SPACEGROUP']:'')+'</td>'+
            '<td>'+(s['COMMENTS']?s['COMMENTS']:'')+'</td>'+
            '<td>'+(s['BLSAMPLEID'] ? (s['DCOUNT'] > 0 ? 'Yes' : 'No') : '')+'</td>'+
            '<td><button class="edit" title="Edit sample details"></button> '+(s['BLSAMPLEID'] ? '<button class="delete"></button> &nbsp; <button class="view small" title="View sample details"></button>' : '')+'</td>'+
            '</tr>').appendTo($('.samples tbody'))
        })
        _map_callbacks()
      }
    })
  }
           
  _get_samples()
  
})

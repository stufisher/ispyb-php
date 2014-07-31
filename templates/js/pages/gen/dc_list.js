$(function() {
  
  var refresh_time = 5000
  var auto_load = 1
  var auto_load_thread = null
  var first = true
  var nopgs = null
  //var search = ''
  //var type = ''
  
  // Hash of distl plots
  var distl = {}
  

  $('input[name=search]').focus()
  
  $('input.search-mobile').focus().keyup(function() {
    $('input[name=search]').val($(this).val()).trigger('keyup')
  }).parent('span').addClass('enable').addClass('split')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  var flt = ''
  $('.filter li').each(function(i,e) {
    flt += '<option value="'+$(e).attr('id')+'">'+$(e).html()+'</option>'
  })
  $('span.search-mobile').append('<select name="filter"><option value="">- Filter -</option>'+flt+'</select>')
  

  //if (active == 0) $('div.log').hide()
  
  // Time filters
  if (is_visit) {
    var out = ''
    for (var i = sh; i < (sh+len); i++) {
      var hr = i%24
      if (hr < 10) hr = '0'+hr
      out += '<li hr="'+hr+'"><a href="#'+hr+'">'+hr+':00</a></li>'
    }
    $('.times').html('<ul>'+out+'</ul>')
  }
  $('.times li[hr="'+h+'"]').addClass('selected')

  $('.times a').click(function() {
    if ($(this).parent('li').hasClass('selected')) h = ''
    else h = $(this).attr('href').replace('#', '')
                      
    $('.times li').removeClass('selected').filter('[hr="'+h+'"]').addClass('selected')
                      
    $('.data_collection').remove()
    $('.log ul li').remove()
    first = true
    distl = {}
    clearTimeout(auto_load_thread)
    load_datacollection()
                      
    url = window.location.pathname.replace(/\/h\/\d+/, '')+(h !== '' ? ('/h/'+h) : '')
    window.history.pushState({}, '', url)
    return false
  })
  

  if (search) {
    $('input[name=search]').val(search)
    auto_load = 0
  }
  
  
  
  // Search as you type
  var thread = null;
  
  $('input[name=search]').keyup(function() {
      clearTimeout(thread);
      $this = $(this);
      thread = setTimeout(function() {
            if ($this.val() != '') {
                clearTimeout(auto_load_thread)
                auto_load = 0
            } else auto_load = 1
            page = 1
            first = true
            search = $this.val()
                          
            url = window.location.pathname.replace(/\/s\/\w+/, '')+(search ? ('/s/'+search) : '')
            window.history.pushState({}, '', url)
                          
            load_datacollection();
      }, 800);
  });
  
  
  // Allow users to change number of data collections per page
  if (!pp) pp = $(window).width() <= 600 ? 5 : ($(window).width() <= 1024 ? 10 : 15)
  $('select[name=pp]').val(pp).change(function() {
    pp = $(this).val()
    $('select[name=pp]').val(pp)
    clearTimeout(auto_load_thread)
    page = 1
    first = true
    url = window.location.pathname.replace(/\/pp\/\d+/, '')+'/pp/'+pp
    window.history.pushState({}, '', url)
                                      
    $('.data_collection').remove()
    load_datacollection()
  })
  
  
  // Async load of data collections
  function load_datacollection() {
      var dcids = []
      $('.data_collection').each(function() {
          dcids.push($(this).attr('dcid'))
      })
  
      $.ajax({
             url: '/dc/ajax' + (is_visit ? ('/visit/' + visit) : '') + (page ? ('/page/' + page) : '') + (search ? ('/s/'+search) : '') + (type ? ('/t/'+type) : '') + ('/pp/'+pp) + (dcid ? ('/id/'+dcid) : '') + (h ? ('/h/'+h) : '') + (dmy ? ('/dmy/'+dmy) : ''),
             type: 'GET',
             dataType: 'json',
             timeout: 10000,
             success: function(json){
                if (json[0] != nopgs) {
                  var pgs = []
                  var pc = json[0] == 0 ? 1 : json[0]
                  for (var i = 0; i < pc; i++) pgs.push('<li><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
             
                  $('.pages').html('<ul>'+pgs.join('')+'</ul>')
                  nopgs = json[0]
                }
             
                $('.pages ul').each(function(i,e) {
                  $(this).children('li').removeClass('selected').eq(page-1).addClass('selected')
                })
             
                if (search) $('.data_collection').remove()
             
                $.each(json[1].reverse(), function(i,r) {
                    if ($.inArray(r['ID'], dcids) == -1) {
                       var vis_link = is_visit ? '' : ' [<a href="/dc/visit/'+prop+'-'+r['VN']+'">'+prop+'-'+r['VN']+'</a>]'
                       
                       // Data Collection
                       //if (r['TYPE'] == 'data') {
                       var load = 'Loading: <img width="16" height="16" src="/templates/images/ajax-loader.gif" alt="Loading..." />'
                       var f = r['COMMENTS'] ? (r['COMMENTS'].indexOf('_FLAG_') > -1 ? 'ui-state-highlight' : '') : ''
                       var state = r['RUNSTATUS'] == null ? 1 : (r['RUNSTATUS'].indexOf('Successful') > -1)
                       
                       $('<div class="data_collection" dcid="'+r['ID']+'" type="data">' +
                             '<h1>'+
                                '<button class="atp" ty="dc" iid="'+r['ID']+'" name="'+r['RUNDIRECTORY']+'.mrc">Add to Project</button> <button class="flag '+f+'" title="Click to add this data collection to the list of favourite data collections">Favourite</button>  <a href="/dc/visit/'+prop+'-'+r['VN']+'/id/'+r['ID']+'" class="perm">Permalink</a> '+
                                '<span class="date">'+vis_link+' '+r['ST']+'</span><span class="spacer"> - </span><span class="temp">'+r['FILETEMPLATE']+'</span>'+
                                
                             '</h1>'+
                             (state ?
                             ('<div class="distl"></div>'+
                             '<div class="snapshots">'+
                                '<a href="/image/id/'+r['ID']+'/f/1"><img dsrc="" alt="Image 1"/></a>'+
                             '</div>'+
                             '<div class="diffraction">'+
                                '<a href="/image/id/'+r['ID']+'/n/4/f/1"><img dsrc="" alt="Image 2" />' +
                             '</div>'+
                              
                             '<div class="links">'+
                               '<a href="/dc/view/id/'+r['ID']+'"><i class="fa fa-picture-o fa-2x"></i> Images</a> '+
                               '<a class="sn" href="#snapshots"><i class="fa fa-camera fa-2x"></i> Snapshots</a> '+
                               '<a class="dl" href="#distl"><i class="fa fa-bar-chart-o fa-2x"></i> DISTL</a> '+
                             '</div>') : '<div class="r aborted">Data Collection Stopped</div>')+
                        
                         
                             '<ul class="clearfix">'+
                                 '<li>No. Images: '+r['NUMIMG']+'</li>'+
                                 '<li>Resolution: '+r['RESOLUTION']+'&#197;</li>'+
                                 '<li>Wavelength: '+r['WAVELENGTH']+'&#197;</li>'+
                                 '<li>Exposure: '+r['EXPOSURETIME']+'s</li>'+
                                 '<li>Transmission: '+r['TRANSMISSION']+'%</li>'+
                                 '<li>Beamsize: '+r['BSX']+'x'+r['BSY']+'&mu;m</li>'+
                                 '<li class="comment" title="Click to edit the comment for this data collection">Comment: <span class="comment_edit">'+(r['COMMENTS']?r['COMMENTS']:'')+'</span></li>'+
                             '</ul>'+
                         
                             '<div class="holder">'+
                             (state ?
                                '<h1 title="Download Data">Data Files <span class="r"><i class="fa fa-download"></i> <a href="/dc/ajax/dl/id/'+r['ID']+'" title="Download files">Download</a></span></h1>': '')+
                             '</div>'+
                             '</div>').data('apr', r['AP']).data('nimg', r['NUMIMG']).hide().data('first', true).prependTo('.data_collections').slideDown(100)
                       
                            $('.data_collection[dcid="'+r['ID']+'"] .distl').data('plotted', false)
                       
                            if (!first)
                                log_message('New data collection', '<a href="#'+r['ID'] +'">' +r['DIR']+r['FILETEMPLATE'] + '</a>')
                       
                                              
                       //}

                       if ($('div.data_collection').length > json[1].length) {
                           var last_id = $('div.data_collection:last').attr('dcid')
                           $('div.data_collection:last').slideUp()
                           if (distl[last_id]) distl[last_id].destroy()
                           delete distl[last_id]
                           $('div.data_collection:last').removeData().remove()
                       }
                       
                   
                   } else {

                       
                   }

                    
                })
             
                if (json[1].length == 0) {
                    if ($('.data_collection[dcid="-1"]').length == 0)
                        $('<div class="data_collection" dcid="-1"><h1>No data collections '+(search?'found':'yet')+'</h2></div>').hide().prependTo('.data_collections').slideDown()
                }
             
                map_callbacks()
                first = false
             
                json = null
             
                  if (auto_load) {
                    auto_load_thread = setTimeout(function() {
                        load_datacollection()
                    }, 8000)
                  }
             },
             
             error: function() {
                  if (auto_load) {
                    auto_load_thread = setTimeout(function() {
                        load_datacollection()
                    }, 8000)
                  }
             }
      })
  

  }
  
  load_datacollection()
  

  
  function _show_images() {
    $('.lazy').not('.enabled').unveil(0,function() { $(this).load(function() {$(this).addClass('show')}) }).addClass('enabled')
  }
  
  
  // Update AP status
  function update_aps() {
    var ids = $('.data_collection[type=data]').map(function(i,e) { if (!$(e).attr('di') || !$(e).attr('sn') ) return $(e).attr('dcid') }).get()
    if (ids.length)
      $.ajax({
        url: '/dc/ajax/chi' + (is_visit ? ('/visit/'+visit) : ''),
        type: 'POST',
        data: { ids: ids },
        dataType: 'json',
        timeout: 20000,
          success: function(list) {
            $.each(list, function(i, r) {
               var id = r[0]
               var img = r[1]

               if (img[3]) {
                 $('div[dcid='+id+']').attr('di',1)
                 $('div[dcid='+id+'] .diffraction img').attr('data-src', '/image/n/4/id/'+id).addClass('lazy')
                 $('div[dcid='+id+'] .diffraction').magnificPopup({ delegate: 'a', type: 'image'})
               }
                   
               if (img[0]) {
                 $('div[dcid='+id+']').attr('sn',1)
                 $('div[dcid='+id+'] .snapshots img').attr('data-src', '/image/id/'+id).addClass('lazy')
                 
                 var sns = ''
                 for (var i = 1; i < 3; i++) {
                   if (img[i]) sns += ('<a href="/image/id/'+id+'/f/1/n/'+(i+1)+'"></a>')
                 }
                   
                 if ($('div[dcid='+id+'] .snapshots a').length == 1) $('div[dcid='+id+'] .snapshots').append(sns)
                    $('div[dcid='+id+'] .snapshots').magnificPopup({
                      delegate: 'a', type: 'image',
                      gallery: {
                        enabled: true,
                        navigateByImgClick: true,
                      }
                    })
               }
            })
             
            _show_images()
          }
      })
  }
  
  
  // Log messages
  function log_message(title, msg) {
    var now = new Date()
    var t = [now.getHours(), now.getMinutes(), now.getSeconds()]
    for (var i = 0; i < t.length; i++) {
        if (t[i] < 10) t[i] = '0'+t[i]
    }

    time = t[0] + ':' + t[1] + ':' + t[2]
    $('<li><span class="title">' +time+ ' - ' + title + '</span> ' + msg + '</li>').hide().prependTo($('.log ul')).slideDown()
  }

    
  
  // Plot image quality indicators
  function plot(div, success) {
      var id = $(div).parent('div').attr('dcid')
  
      $.ajax({
             url: '/dc/ajax/dat/id/' + id,
             type: 'GET',
             dataType: 'json',
             timeout: 15000,
             success: function(j){
                var data = [{
                            data: j,
                         }]
             
                 var options = {
                    grid: {
                        borderWidth: 0,
                    },
                    series: {
                        points: {
                            show: true,
                            radius: 1,
                        }
                    },
                    yaxis: {
                      //transform: function(v) { return v == 0 ? null : Math.log(v) }
                    }
                 }
             
               if (distl[id]) {
                 distl[id].setData(data);
                 distl[id].setupGrid();
                 distl[id].draw();
               } else distl[id] = $.plot($(div), data, options);
             
               if (success) success()
             }
        })
  }

  
  function map_callbacks() {
      update_aps()
      _show_images()
  
      $('.data_collection a.sn').unbind('click').click(function() {
        $(this).parent('div').siblings('.snapshots').children('a').eq(0).trigger('click')
      })

      $('.data_collection a.dl').unbind('click').click(function() {
        $(this).parent('div').siblings('.distl').trigger('click')
      })
  
      // Make sample snapshots lightboxed
      $('div[type=action] .snapshots').magnificPopup({ delegate: 'a', type: 'image' })
  
  
      // Page links
      $('.pages a').unbind('click').click(function() {
           page = parseInt($(this).attr('href').replace('#', ''))
           $('.data_collection').remove()
           $('.log ul li').remove()
           first = true
           clearTimeout(auto_load_thread)
           load_datacollection()
           url = window.location.pathname.replace(/\/page\/\d+/, '')+'/page/'+page
           window.history.pushState({}, '', url)
           return false
      })
  
      // Log links
      $('.log ul li a').unbind('click').click(function() {
            id = $(this).attr('href').replace('#', '')
            pos = $('.data_collection[dcid="'+id+'"]').offset().top
            $('body').animate({scrollTop: pos}, 300);
            return false
      })
  
      $('.data_collection .distl').unbind('click').bind('click', function() {
        
      }).each(function(i,pl) {
          if (!$(pl).data('plotted')) {
              var w = 0.175*$('.data_collection').width()
              $(this).height($(window).width() > 800 ? w : (w*1.65))
              $('.diffraction,.snapshots').height($(this).height())
              plot(pl, function() {
                $(pl).data('plotted', true)
              })
          }
      })
  
  
      // Make flagable data collections iconified
      $('.data_collection .flag').button({ icons: { primary: 'ui-icon-star' }, text: false  }).click(function() {
          var id = $(this).parent().parent('div').attr('dcid')
          var t = $(this).parent().parent('div').attr('type')
          var i = $(this)
          $.ajax({
                 url: '/dc/ajax/flag/t/'+t+'/id/' + id,
                 type: 'GET',
                 dataType: 'json',
                 timeout: 5000,
                 success: function(j){
                    j == 1 ? i.addClass('ui-state-highlight') : i.removeClass('ui-state-highlight')
                 }
          })
      })
  
      $('.data_collection .perm').button({ icons: { primary: 'ui-icon-link' }, text: false })
  
  
      // Make comment editable
      $('.data_collection .comment_edit').each(function(i,e) {
        var id = $(this).parent().parent().parent('div').attr('dcid')
        $(this).editable('/dc/ajax/comment/id/'+id, {
                                                width: '65%',
                                                height: '20px',
                                                type: 'text',
                                                submit: 'Ok',
                                                style: 'display: inline',
                                                }).addClass('editable');
      })
  }
  

    
});

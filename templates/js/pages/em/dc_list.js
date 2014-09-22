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
             url: '/dc/ajax/em' + (is_sample ? ('/sid/'+sid) : '') + (is_visit ? ('/visit/' + visit) : '') + (page ? ('/page/' + page) : '') + (search ? ('/s/'+search) : '') + (type ? ('/t/'+type) : '') + ('/pp/'+pp) + (dcid ? ('/id/'+dcid) : '') + (h ? ('/h/'+h) : '') + (dmy ? ('/dmy/'+dmy) : ''),
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
                                '<span class="date">'+vis_link+' '+r['ST']+'</span><span class="spacer"> - </span><span class="temp">'+r['MOVIEFILE']+'</span>'+
                                
                             '</h1>'+
                             (state ?
                             ('<div class="distl" title="Drift plot"></div>'+
                             '<div class="snapshots" title="Powerspectra">'+
                                '<a href="/image/fft/id/'+r['ID']+'/f/1" title="Powerspectrum 1"><img dsrc="" alt="Powerspectrum 1" /></a>'+
                             '</div>'+
                             '<div class="diffraction" title="EM Micrograph">'+
                                '<a href="/image/em/id/'+r['ID']+'/f/1" title="EM Micrograph"><img dsrc="" alt="EM Micrograph" />' +
                             '</div>'+
                              
                             '<div class="links">'+
                               '<a href="/dc/view/id/'+r['ID']+'"><i class="fa fa-picture-o fa-2x"></i> Images</a> '+
                               '<a class="sn" href="#snapshots"><i class="fa fa-camera fa-2x"></i> Snapshots</a> '+
                               '<a class="dl" href="#distl"><i class="fa fa-bar-chart-o fa-2x"></i> DISTL</a> '+
                             '</div>') : '<div class="r aborted">Data Collection Stopped</div>')+
                        
                         
                             '<ul class="clearfix">'+
                                 '<li>No. Images: '+r['NOIMAGES']+'</li>'+
                                 '<li>Microscope: '+r['MICROSCOPE']+'</li>'+
                                 '<li>Frame Length: '+r['FRAMELENGTH']+'s</li>'+
                                 '<li>Voltage: '+r['VOLTAGE']+'kV</li>'+
                                 '<li>Total Exposure: '+r['TOTALEXPOSURE']+'s</li>'+
                                 '<li>CS: '+r['CS']+'mm</li>'+
                                 '<li>Magnification: '+r['MAGNIFICATION']+' X</li>'+
                                 '<li>Detector Pix Size: '+r['DETECTORPIXELSIZE']+'&mu;m</li>'+
                                 '<li>Sample Pix Size: '+r['SAMPLEPIXSIZE']+' A/pix</li>'+
                                 '<li>C2 Aperture: '+r['C2APERTURE']+'&mu;m</li>'+
                                 '<li>Dose per Frame: '+r['DOSEPERFRAME']+'e-/A^2</li>'+
                                 '<li>Obj Aperture: '+r['OBJAPERTURE']+'&mu;m</li>'+
                                 '<li>Total Dose: '+r['TOTALDOSE']+'e-/A^2</li>'+
                                 '<li>C2 Lense: '+r['C2LENS']+'%</li>'+
                                 '<li class="comment" title="Click to edit the comment for this data collection">Comment: <span class="comment_edit">'+(r['COMMENTS']?r['COMMENTS']:'')+'</span></li>'+
                             '</ul>'+
                         
                             '<div class="holder">'+
                             (state ?
                                '<h1 title="Click to show autoprocessing results such as Fast_DP and XIA2">Auto Processing<span>'+load+'</span></h1>'+
                                 '<div class="autoproc"></div>' : '')+
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
    var ids = $('.data_collection[type=data]').map(function(i,e) { if (!$(e).attr('di') || !$(e).attr('sn') || ($(e).attr('ty') == 'grid' && !$(e).attr('gr'))) return $(e).attr('dcid') }).get()
    if (ids.length)
      $.ajax({
        url: '/dc/ajax/chiem' + (is_visit ? ('/visit/'+visit) : ''),
        type: 'POST',
        data: { ids: ids },
        dataType: 'json',
        timeout: 20000,
          success: function(list) {
            $.each(list, function(i, r) {
               var id = r[0]
               var img = r[1]

               if (img[0]) {
                 $('div[dcid='+id+']').attr('di',1)
                 $('div[dcid='+id+'] .diffraction img').attr('data-src', '/image/em/id/'+id).addClass('lazy')
                 $('div[dcid='+id+'] .diffraction').magnificPopup({                      delegate: 'a', type: 'image'})
               }
               if (img[1].length > 0) {
                 if (img[2]) {
                   $('div[dcid='+id+']').attr('sn',1)
                   $('div[dcid='+id+'] .snapshots img').attr('data-src', '/image/fft/id/'+id).addClass('lazy')
                 }
                 var sns = ''
                 for (var i = 1; i < img[1].length; i++) sns += ('<a href="/image/fft/id/'+id+'/f/1/n/'+(i+1)+'" title="Powerspectrum '+(i+1)+'"></a>')
                   
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
             url: '/dc/ajax/emp/id/' + id,
             type: 'GET',
             dataType: 'json',
             timeout: 15000,
             success: function(j){
                var data = [{
                            data: j,
                            //label: 'Drift',
                         }]
             
                 var options = {
                    xaxis: {
                        //minTickSize: 1,
                        //tickDecimals: 0,
                        //tickColor: 'transparent'
                        min: -20,
                        max: 20,
                    },
                    yaxis: {
                        min: -20,
                        max: 20,
                    },
                    grid: {
                        borderWidth: 0,
                    },
                    series: {
                        lines: { show: true },
                        points: {
                            show: true,
                            radius: 1,
                        }
                    },
                 }
             
               if (distl[id]) {
                 distl[id].setData(data);
                 distl[id].setupGrid();
                 distl[id].draw();
               } else distl[id] = $.plot($(div), data, options);
             
               var refresh_imq = true
               if (j[0].length > 0) {
                   var nimg = $('.data_collection[dcid="'+id+'"]').data('nimg')
                   if (nimg == $(j[0]).last()[0][0]) refresh_imq = false
             
                   var date = _date_to_unix($('.data_collection[dcid="'+id+'"]').find('span.date').html())
                   if ((new Date() - date) > (900*1000)) refresh_imq = false
               }
             
               if (refresh_imq) {
                   setTimeout(function() {
                       plot(div)
                   }, 10000)
               }
             
               if (success) success()
             }
        })
  }
  
  
  function _date_to_unix(strtime) {
    var dt = strtime.trim().split(' ')
    var dmy = dt[0].split('-')
    var hms = dt[1].split(':')
    var date = new Date(dmy[2], dmy[1]-1, dmy[0], hms[0], hms[1], hms[2], 0)
    return date
  }
  
  
  // Get samples for each data collection
  function _get_sample() {
    var ids = []
    $('.data_collection').each(function(i,dc) {
      if (!$(dc).attr('sample')) {
        ids.push($(dc).attr('dcid'))
      }
    })
  
    if (ids.length) {
      $.ajax({
        url: '/dc/ajax/sem' + (is_visit ? ('/visit/'+visit) : ''),
        type: 'POST',
        data: { ids: ids },
        dataType: 'json',
        timeout: 20000,
        success: function(list) {
          $.each(list, function(id,dc) {
            var d = $('.data_collection[dcid='+id+']')
            if (d.length) {
              if (dc['SID'] && !$(d).find('.sample').length) {
                $('<li class="sample"><span class="wrap">Sample: <a href="/sample/sid/'+dc['SID']+'/visit/'+prop+'">' + dc['SAN']+'</a></span></li>').prependTo($(d).children('ul'))
              
              }
              $(d).attr('sample', true)
            }
          })
             
          list = null
        }
      })
    }
  }
  
  
  function map_callbacks() {
      update_aps()
      _show_images()
      _get_sample()
  
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
        var t = $(this).parent().parent().parent('div').attr('type')
        $(this).editable('/dc/ajax/comment/t/'+t+'/id/'+id, {
                                                width: '65%',
                                                height: '20px',
                                                type: 'text',
                                                submit: 'Ok',
                                                style: 'display: inline',
                                                }).addClass('editable');
      })
  }
  
    
  
  // Status H1 toggles status visibility
  $('h1.status').click(function() {
    $('div.status').slideToggle()
                    
    if ($('div.status').is(':visible')) refresh_pvs()
                       
    $('.webcam img').each(function(i,w) {
      $(this).attr('src', $('div.status').is(':visible') ? ('/image/cam/bl/'+bl+'/n/'+i) : '')
    })
  })
  
  // Get PVs
  function refresh_pvs() {
    var t = new Date()
    $.ajax({
        url: '/status/ajax/bl/'+bl+'/t/'+t,
        type: 'GET',
        dataType: 'json',
           
        success: function(pvs){           
          $.each(pvs, function(k,v) {
            var c;
            if (k == 'Ring Current') c = v < 10 ? 'off' : 'on'
            else if (k == 'Ring State') c = v == 'User' ? 'on' : 'off'
            else if (k == 'Hutch') c = v == 'Locked' ? 'on' : 'off'
            else if (k == 'Refil') c = v == -1 ? 'off' : 'on'
            else c = v == 'Closed' ? 'off' : 'on'
                 
            if ($('.pv[pv="'+k+'"]').length) {
              $('.pv[pv="'+k+'"]').removeClass('on').removeClass('off').addClass(c).children('p').html(v)
            } else {
              $('<div class="pv" pv="'+k+'"><h1>'+k+'</h1><p>'+v+'</p></div>').addClass(c).hide().appendTo('.status .pvs').fadeIn()
            }
          })
        }
      })
  
      if ($('div.status').is(':visible')) {
        setTimeout(function() {
          refresh_pvs()
        }, 3000)
      }
  }
  
    
});

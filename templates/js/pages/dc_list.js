$(function() {
  
  var refresh_time = 5000
  var auto_load = 1
  var auto_load_thread = null
  var first = true
  var search = ''
  //var type = ''
  
  var dragImg = document.createElement('img');
  dragImg.src = '/templates/images/drag.png'

  
  // Filter by type
  $('.filter ul li[id='+type+']').addClass('current')
  $('.filter ul li').click(function() {
    if ($(this).hasClass('current')) {
        $(this).removeClass('current')
        type = ''
    } else {
        $('.filter ul li').removeClass('current')
        $(this).addClass('current')
        type = $(this).attr('id')
    }
    
                           
    $('.data_collection').remove()
    $('.log ul li').remove()
    first = true
    page = 1
                           
    url = window.location.pathname.replace(/\/t\/\w+/, '')+(type ? ('/t/'+type) : '')
    window.history.pushState({}, '', url)
                           
    clearTimeout(auto_load_thread)
    load_datacollection()
  })
  
  
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
            load_datacollection();
      }, 500);
  });
  
  
  // Async load of data collections
  function load_datacollection() {
      var dcids = []
      var apr = new Array()
      $('.data_collection').each(function() {
          dcids.push($(this).attr('dcid'))
          apr[$(this).attr('dcid')] = $(this).data('apr')
      })
  
      //console.log('fn '+new Date())
  
      $.ajax({
             url: '/ajax/dc/visit/' + visit + (page ? ('/page/' + page) : '') + (search ? ('/s/'+search) : '') + (type ? ('/t/'+type) : '') + ($(window).width() <= 600 ? '/pp/5' : ''),
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(json){
                //console.log('res '+new Date())
                var pgs = []
                for (var i = 0; i < json[0]; i++) pgs.push('<li'+(i+1==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
             
                $('.pages').html('<ul>'+pgs.join('')+'</ul>')
             
                if (search) $('.data_collection').remove()
             
                $.each(json[1].reverse(), function(i,r) {
                    if ($.inArray(r['ID'], dcids) == -1) {
                       
                       // Data Collection
                       if (r['TYPE'] == 'data') {
                           /*var sn = r['SN'] ? '/image/id/'+r['ID'] : ''
                           var di = r['DI'] ? '/image/diff/id/'+r['ID'] : ''
                       
                           sns = ''
                           for (var i = 1; i < r['X'].length; i++) sns += ('<a href="/image/id/'+r['ID']+'/f/1/n/'+(i+1)+'" rel="lightbox-'+r['ID']+'" title="Crystal Snapshot '+(i+1)+'"></a>')*/
                       
                           $('<div class="data_collection" dcid="'+r['ID']+'" type="data">' +
                             '<div class="distl"></div>'+
                             '<div class="snapshots">'+
                                '<a href="/image/id/'+r['ID']+'/f/1" rel="lightbox-'+r['ID']+'" title="Crystal Snapshot 1"><img dsrc="" alt="Crystal Snapshot 1" /></a>'+
                             '</div>'+
                             '<div class="diffraction">'+
                                '<a href="/dc/view/id/'+r['ID']+'"><img dsrc="" alt="Diffraction Image 1" /></a>' +
                             '</div>'+
                             '<h1>'+r['ST']+'</h1>'+
                             '<h2><a href="file:///dls/'+bl+'/data/'+year+'/'+visit+'/jpegs/'+r['DIR']+'">'+r['DIR']+r['FILETEMPLATE']+'</a></h2>'+

                             '<ul>'+
                                 (r['SAN'] != null ? ('<li>Sample: ' + r['SAN'] + ' (m' + r['SCON'] + 'p' + r['SPOS']+')') : '')+
                                 '<li>&Omega; Start: '+r['AXISSTART']+'&deg;</li>'+
                                 '<li>&Omega; Osc: '+r['AXISRANGE']+'&deg;</li>'+
                                 '<li>No. Images: '+r['NUMIMG']+'</li>'+
                                 '<li>Resolution: '+r['RESOLUTION']+'&#197;</li>'+
                                 '<li>Wavelength: '+r['WAVELENGTH']+'&#197;</li>'+
                                 '<li>Exposure: '+r['EXPOSURETIME']+'s</li>'+
                                 '<li>Transmission: '+r['TRANSMISSION']+'%</li>'+
                                 '<li class="comment">Comment: '+r['COMMENTS']+'</li>'+
                             '</ul>'+
                             '<div class="holder">'+
                             (r['NI'] < 10 ?
                                ('<span></span><h1>Strategies</h1>'+
                                 '<div class="strategies"></div>'):
                                ('<span></span><h1>Auto Processing</h1>'+
                                 '<div class="autoproc"></div>'+
                                 '<span></span><h1>Downstream Processing</h1>'+
                                 '<div class="downstream"></div>'))+
                             '</div>'+
                             '</div>').data('apr', r['AP']).data('nimg', r['NUMIMG']).hide().data('first', true).prependTo('.data_collections').slideDown()
                       
                            //plot($('.data_collection[dcid="'+r['ID']+'"] .distl'), r['ID'])
                            $('.data_collection[dcid="'+r['ID']+'"] .distl').data('plotted', false)
                       
                            if (!first)
                                log_message('New data collection', '<a href="#'+r['ID'] +'">' +r['DIR']+r['FILETEMPLATE'] + '</a>')
                       
                       
                       // Edge Scan
                       } else if (r['TYPE'] == 'edge') {
                           ev = 12398.4193
                           d = $('<div class="data_collection" dcid="'+r['ID']+'">' +
                             '<div class="edge"></div>'+
                             '<h1>'+r['ST']+'</h1>'+
                             '<h2>'+r['DIR']+' Edge Scan</h2>'+

                             '<ul>'+
                                 '<li>E(Peak): '+r['EPK']+'eV (' + (ev/r['EPK']).toFixed(4) + '&#197;)</li>'+
                                 '<li>f&rsquo;&rsquo;: '+r['AXISSTART']+' / f&rsquo;: '+r['RESOLUTION']+'e</li>'+
                                 '<li>E(Inf): '+r['EIN']+'eV (' + (ev/r['EIN']).toFixed(4) + '&#197;)</li>'+
                                 '<li>f&rsquo;&rsquo;: '+r['WAVELENGTH']+' / f&rsquo;: '+r['AXISRANGE']+'e</li>'+
                                 '<li>Exposure: '+r['EXPOSURETIME']+'s</li>'+
                                 '<li>Transmission: '+r['TRANSMISSION']+'%</li>'+
                                 //'<li>Comment: '+r['COMMENTS']+'</li>'+
                             '</ul>'+
                             '<!--<img src="/image/id/'+r['ID']+'" alt="" />-->'+
                             '<div class="clear"></div>'+
                             '</div>').data('apr', r['AP']).hide().prependTo('.data_collections').slideDown()
                       
                           plot_edge($('.data_collection[dcid="'+r['ID']+'"] .edge'), r['ID'])
                           if (!first) log_message('New edge scan', '<a href="#'+r['ID'] +'">' +r['DIR'] + ' Edge Scan</a>')
                       
                       
                       // MCA Scans
                       } else if (r['TYPE'] == 'mca') {
                           el = ''
                           for (var i = 0; i < r['ELEMENTS'].length;i++) {
                              el += '<tr><td>' + r['ELEMENTS'][i].split(' ').join('</td><td>') + '</td></tr>'
                           }
                           el = '<table>'+el+'</table>'
                       
                           if (r['ELEMENTS'].length == 0) el = '<p>PyMCA didnt run for this spectrum</p>'
                       
                       
                           d = $('<div class="data_collection" dcid="'+r['ID']+'">' +
                             '<div class="mca"></div>'+
                             '<div class="elements">'+el+'</div>'+
                             '<h1>'+r['ST']+'</h1>'+
                             '<h2>MCA Fluorescence Scan</h2>'+

                             '<ul>'+
                                 '<li>Energy: '+r['WAVELENGTH']+'eV</li>'+
                                 '<li>Exposure: '+r['EXPOSURETIME']+'s</li>'+
                                 '<li>Transmission: '+r['TRANSMISSION']+'%</li>'+
                             '</ul>'+
                             '<div class="clear"></div>'+
                             '</div>').data('apr', r['AP']).hide().prependTo('.data_collections').slideDown()
                       
                           plot_mca($('.data_collection[dcid="'+r['ID']+'"] .mca'), r['ID'])
                           if (!first) log_message('New MCA fluorescence spectrum', '<a href="#'+r['ID'] +'">' +r['DIR'] + ' Fluorescence Spectrum</a>')
                       
                       
                       
                       // Robot loads
                       } else if (r['TYPE'] == 'load') {
                         $('<div class="data_collection" dcid="'+r['ID']+'">' +
                           '<h1>'+r['ST']+' - Robot loaded puck ' + r['EXPOSURETIME'] +' pin ' + r['RESOLUTION'] + ' (Barcode: '+r['DIR']+')</h1>' +
                           '</div>').data('apr', r['AP']).hide().prependTo('.data_collections').slideDown()
                       
                         if (!first) log_message('New sample loaded', '<a href="#'+r['ID'] +'">Barcode: ' +r['DIR'] + '</a>')
                       
                       }
                       
                       //update_aps(r['ID'], false)
                       //update_jobs(r['ID'], r['AP'], false)
                       
                       if ($('div.data_collection').length > json[1].length) {
                           $('div.data_collection:last').slideUp().remove()
                       }
                       
                   
                   } else {
                       if (r['TYPE'] == 'data') {
                           $('.data_collection[dcid="'+r['ID']+'"]').data('first', false)
                           var sn = $('.data_collection[dcid="'+r['ID']+'"] .snapshots img')
                           var di = $('.data_collection[dcid="'+r['ID']+'"] .diffraction img')
                       
                           if (!$(sn).attr('dsrc') && r['SN']) $(sn).attr('dsrc', '/image/id/'+r['ID'])
                           if (!$(di).attr('dsrc') && r['DI']) $(di).attr('dsrc', '/image/diff/id/'+r['ID'])
                           //update_aps(r['ID'], true)
                       }
                    
                       
                       //if (!arrcmp(apr[r['ID']],r['AP'])) {
                           //update_jobs(r['ID'], r['AP'], true)
                           //$('.data_collection[dcid="'+r['ID']+'"]').data('apr', r['AP'])
                       //}
                       
                   }

                    
                })
             
                if (json[1].length == 0) {
                    if ($('.data_collection[dcid="-1"]').length == 0)
                        $('<div class="data_collection" dcid="-1"><h1>No data collections '+(search?'found':'yet')+'</h2></div>').hide().prependTo('.data_collections').slideDown()
                }
             
                map_callbacks()
                first = false
             }
      })
  
      if (auto_load) {
        auto_load_thread = setTimeout(function() {
            load_datacollection()
        }, 5000)
      }
  }
  
  load_datacollection()
  
  
  // Update AP status
  function update_aps() {  
    $.ajax({
        url: '/ajax/aps/visit/' + visit,
        type: 'POST',
        data: { ids: $('.data_collection[type=data]').map(function(i,e) { return $(e).attr('dcid') }).get() },
        dataType: 'json',
        timeout: 5000,
        success: function(list) {
         $.each(list, function(i, r) {
           var id = r[0]
           var res = r[1]
           var img = r[2]
                
           var md = $('div[dcid='+id+']')
           var div = $(md).children('.holder')
           var ld = $(md).data('apr')
                
           val = ['<img src="/templates/images/info.png" alt="N/A"/>',
                  '<img src="/templates/images/run.png" alt="Running"/>',
                  '<img src="/templates/images/ok.png" alt="Completed"/>',
                  '<img src="/templates/images/cancel.png" alt="Failed"/>']
           
           if (div.children('div').hasClass('autoproc')) {
               sp = div.children('span')
               $(sp[0]).html('Fast DP: ' + val[res[2]] +
                         ' Xia2: ' + val[res[3]] + ' ' +val[res[4]] + ' ' +val[res[5]])
               $(sp[1]).html('Fast EP: ' + val[res[6]] + ' Dimple: ' + val[res[7]])
               if (!$(md).data('first') && ((res[2] == 2 && res[2] != ld[2]) ||
                         (res[3] == 2 && res[3] != ld[3]) ||
                         (res[4] == 2 && res[4] != ld[4]) ||
                         (res[5] == 2 && res[5] != ld[5]) )) {
                   setTimeout(function() {
                      log_message('New auto processing for', '<a href="#'+id+'">' + $('div[dcid="' + id + '"] > h2').text() + '</a>')
                      load_autoproc(div.children('div.autoproc'), id)
                      }, 3000)
               }
           
           } else {
               sp = div.children('span')
               $(sp[0]).html('Mosflm: ' + val[res[0]] + ' EDNA: ' + val[res[1]])
           
               if (!$(md).data('first') && ((res[0] == 2 && res[0] != ld[0]) || (res[1] == 2 && res[1] != ld[1]))) {
                   setTimeout(function() {
                          log_message('New strategies for', '<a href="#'+id+'">' + $('div[dcid="' + id + '"] > h2').text() + '</a>')
                          load_strategy(div.children('div.strategies'), id)
                          }, 3000)
               }
           }
           $(md).data('apr', res)
                
           // Load images
           if (img[0]) $('div[dcid='+id+'] .diffraction img').attr('dsrc', '/image/diff/id/'+id)
           if (img[1].length > 0) {
             if (img[2]) $('div[dcid='+id+'] .snapshots img').attr('dsrc', '/image/id/'+id)
             sns = ''
             for (var i = 1; i < img[1].length; i++) sns += ('<a href="/image/id/'+id+'/f/1/n/'+(i+1)+'" rel="lightbox-'+id+'" title="Crystal Snapshot '+(i+1)+'"></a>')
             if ($('div[dcid='+id+'] .snapshots a').length == 1) $('div[dcid='+id+'] .snapshots').append(sns)
           }
          })
           
           
          // Fade in images
          $('.data_collection .diffraction img, .data_collection .snapshots img').each(function() {
            if (!$(this).attr('src') && $(this).attr('dsrc')) {
                $(this).attr('src', $(this).attr('dsrc')).load(function() {
                    $(this).fadeIn()
                })
            }
          })           
        }
    })
  }
  
  
  // Update job status
  function update_jobs(id, res, d) {
    div = $('div[dcid="'+id+'"] > .holder')
    val = ['<img src="/templates/images/info.png" alt="N/A"/>',
           '<img src="/templates/images/run.png" alt="Running"/>',
           '<img src="/templates/images/ok.png" alt="Completed"/>',
           '<img src="/templates/images/cancel.png" alt="Failed"/>']
  
    if (div.children('div').hasClass('autoproc')) {
        sp = div.children('span')
        $(sp[0]).html('Fast DP: ' + val[res[2]] +
                      ' Xia2: ' + val[res[3]] + ' ' +val[res[4]] + ' ' +val[res[5]])
        $(sp[1]).html('Fast EP: ' + val[res[6]] + ' Dimple: ' + val[res[7]])
        if (d && (res[2] == 2 || res[3] == 2 || res[4] == 2 || res[5] == 2)) {
            setTimeout(function() {
                log_message('New auto processing for', '<a href="#'+id+'">' + $('div[dcid="' + id + '"] > h2').text() + '</a>')
                load_autoproc(div.children('div.autoproc'), id)
            }, 3000)
        }
  
    } else {
        sp = div.children('span')
        $(sp[0]).html('Mosflm: ' + val[res[0]] + ' EDNA: ' + val[res[1]])
  
        if (d && (res[0] == 2 || res[1] == 2)) {
            setTimeout(function() {
                log_message('New strategies for', '<a href="#'+id+'">' + $('div[dcid="' + id + '"] > h2').text() + '</a>')
                load_strategy(div.children('div.strategies'), id)
            }, 3000)
        }
    }
  
  }
  
  
  // Log messages
  function log_message(title, msg) {
    now = new Date()
    t = [now.getHours(), now.getMinutes(), now.getSeconds()]
    for (i = 0; i < t.length; i++) {
        if (t[i] < 10) t[i] = '0'+t[i]
    }

    time = t[0] + ':' + t[1] + ':' + t[2]
    $('<li><span class="title">' +time+ ' - ' + title + '</span> ' + msg + '</li>').hide().prependTo($('.log ul')).slideDown()
  }

  
  
  // Plot edge scan
  function plot_edge(div, id) {
      $.ajax({
             url: '/ajax/ed/id/' + id,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(j){
                 var data = [{ data: j[0], color: 'rgb(100,100,100)' },
                         { data: j[1], label: 'f&rsquo;&rsquo;', yaxis: 2 },
                         { data: j[2], label: 'f&rsquo;', yaxis: 2 },]
                 //{ data: fpp,  label: 'Pk(f&rsquo;&rsquo;)'},
                 //{ data: fp,  label: 'Inf(f&rsquo;)'}]

                 $.plot(div, data, { grid: { borderWidth: 0 }, yaxes: [{}, { position: 'right' }] })
             
             }
      })
  }
  

  // Plot MCA scan
  function plot_mca(div, id) {
      $.ajax({
             url: '/ajax/mca/id/' + id,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(j){
                 var data = [{ data: j, color: 'rgb(100,100,100)' }]

                 $.plot(div, data, { grid: { borderWidth: 0 }, yaxes: [{}, { position: 'right' }] })
             
             }
      })
  }
  
  
  // Plot image quality indicators
  function plot(div) {
      var id = $(div).parent('div').attr('dcid')
  
      $.ajax({
             url: '/ajax/imq/id/' + id,
             type: 'GET',
             dataType: 'json',
             timeout: 5000,
             success: function(j){
                var data = [{
                            data: j[0],
                            //label: 'Spots',
                         }, {
                            data: j[1],
                            //label: 'Bragg',
                         }, {
                            data: j[2],
                            //label: 'Res',
                            yaxis: 2
                         }]
             
                 var options = {
                    xaxis: {
                        minTickSize: 1,
                        tickDecimals: 0,
                        //tickColor: 'transparent'
             
                    },
                    yaxes: [{}, { position: 'right' }],
                    grid: {
                        borderWidth: 0,
                    },
                    series: {
                        lines: { show: false },
                        points: {
                            show: true,
                            radius: 1,
                        }
                    },
                 }
             
               $.plot($(div), data, options);
             
               var refresh_imq = true
               if (j[0].length > 0) {
                   var nimg = $('.data_collection[dcid="'+id+'"]').data('nimg')
                   if (nimg == $(j[0]).last()[0][0]) refresh_imq = false
             
                   var strtime = $('.data_collection[dcid="'+id+'"]').children('h1').html()
                   var dt = strtime.split(' ')
                   var dmy = dt[0].split('-')
                   var hms = dt[1].split(':')
                   var date = new Date(dmy[2], dmy[1]-1, dmy[0], hms[0], hms[1], hms[2], 0)
                   var now = new Date()
                   if (now - date > 900*1000) refresh_imq = false
             
             
               }
             
               if (refresh_imq) {
                   setTimeout(function() {
                       plot(div)
                   }, 10000)
               }
             }
        })
  }
  
  function map_callbacks() {
      update_aps()
  
      // Enable tabs on all autoproc divs
      $('.data_collection .holder h1').each(function() {
        $(this).next().tabs();
      })
      
      // Map autoprocess/strategy divs to associated ajax requests
      $('.data_collection .holder h1').unbind('click').click(function() {
        id = $(this).parent('div').parent('div').attr('dcid')
        c = $(this).next()

        if (c.is(':visible')) {
            c.slideUp()
            //c.attr('refresh', 0)
        } else {
            //c.attr('refresh', 1)
            if (c.hasClass('autoproc')) load_autoproc(c, id);
            if (c.hasClass('strategies')) load_strategy(c, id);
            if (c.hasClass('downstream')) load_downstream(c, id);
        }
        
      });
  
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
  
      // Make strategies draggable into gda
      $('.strategies table tbody tr').on('dragstart', function(e) {
        e.originalEvent.dataTransfer.effectAllowed='move';
        e.originalEvent.dataTransfer.setData('Text', format_xml(e.currentTarget));
        e.originalEvent.dataTransfer.setDragImage(dragImg,0,0);
        $(this).addClass('dragged')
        return true
      })
  
      $('.strategies table tbody tr').on('dragend', function(e) {
        $(this).removeClass('dragged')
      })
  
      // Load IQI's
      $('.data_collection .distl').each(function() {
          if (!$(this).data('plotted')) {
              var w = 0.15*$('.data_collection').width()
              $(this).height($(window).width() > 600 ? w : (w*2))
              $('.diffraction,.snapshots').height($(this).height())
                                        
              plot($(this))
              $(this).data('plotted', true)
          }
      })
  }
  
  
  // Create xml for gda
  function format_xml(el) {
    d = $(el).data('strat')

    return '<?xml version="1.0" ?>'+
    '<ExtendedCollectRequests>'+
        '<usingDna>false</usingDna>'+
        '<extendedCollectRequest>'+
            '<collect_request>'+
                '<fileinfo>'+
                    '<directory>'+d['IMD']+'data</directory>'+
                    '<prefix>'+d['IMP']+'</prefix>'+
                '</fileinfo>'+
                '<oscillation_sequence>'+
                    '<start>'+d['ST']+'</start>'+
                    '<range>'+d['OSCRAN']+'</range>'+
                    '<number_of_images>'+d['NIMG']+'</number_of_images>'+
                    '<overlap>0.0</overlap>'+
                    '<exposure_time>'+d['NEXP']+'</exposure_time>'+
                    '<start_image_number>1</start_image_number>'+
                    '<number_of_passes>1</number_of_passes>'+
                '</oscillation_sequence>'+
                '<wavelength>'+d['LAM']+'</wavelength>'+
                '<resolution>'+
                    '<upper>'+d['RES']+'</upper>'+
                '</resolution>'+
                '<sample_reference>'+
                    '<container_reference>'+(d['SCON'] ? d['SCON'] : null)+'</container_reference>'+
                    '<sample_location>'+(d['SPOS'] ? d['SPOS'] : null)+'</sample_location>'+
                    '<blSampleId>'+(d['SID'] ? d['SID'] : null)+'</blSampleId>'+
                '</sample_reference>'+
            '</collect_request>'+
            '<runNumber>0</runNumber>'+
            '<sampleDetectorDistanceInMM>'+d['DIST']+'</sampleDetectorDistanceInMM>'+
            '<transmissionInPerCent>'+d['NTRAN']+'</transmissionInPerCent>'+
            '<sampleName>'+(d['SN'] ? d['SN'] : null)+'</sampleName>'+
            '<visitPath>'+d['VPATH']+'</visitPath>'+
            '<comment></comment>'+
            '<suffix>cbf</suffix>'+
            '<totalNumberOfImages>'+d['NIMG']+'</totalNumberOfImages>'+
            '<fileNameTemplate>'+d['IMD']+d['IMP']+'_0_%04d.cbf</fileNameTemplate>'+
            '<hasBeamSize>false</hasBeamSize>'+
            '<hasTransmission>true</hasTransmission>'+
            '<aperturePosition>'+d['AP']+'</aperturePosition>'+
        '</extendedCollectRequest>'+
    '</ExtendedCollectRequests>'
  }

  
  // Async load of auto processing integration results
  function load_autoproc(d, id) {
    var ty = {'Fast DP':'fast_dp', 'XIA2 3da':'3d', 'XIA2 2da':'2d', 'XIA2 3daii':'3dii'}
  
    $.ajax({
        url: '/ajax/ap/id/' + id,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           var types = new Array()
           for (var k in ty) types[k] = new Array()
           
            $.each(json, function(i,r) {
                types[r['TYPE']].push('<tr>' +
                    '<td>'+r['SHELL']+'</td>' +
                    '<td>'+r['SG']+'</td>' +
                    '<td>'+r['CELL_A']+'</td>' +
                    '<td>'+r['CELL_B']+'</td>' +
                    '<td>'+r['CELL_C']+'</td>' +
                    '<td>'+r['CELL_AL']+'</td>' +
                    '<td>'+r['CELL_BE']+'</td>' +
                    '<td>'+r['CELL_GA']+'</td>' +
                    '<td>'+r['NTOBS']+'</td>' +
                    '<td>'+r['NUOBS']+'</td>' +
                    '<td>'+r['RHIGH']+' - '+r['RLOW']+'</td>' +
                    '<td>'+r['RMERGE']+'</td>' +
                    '<td>'+r['ISIGI']+'</td>' +
                    '<td>'+r['COMPLETENESS']+'</td>' +
                    '<td>'+r['MULTIPLICITY']+'</td>' +
                '</tr>')                  
            })
           
            var thead = '<thead><tr>' +
                    '<th>Shell</th>' +
                    '<th>SG</th>' +
                    '<th>A</th>' +
                    '<th>B</th>' +
                    '<th>C</th>' +
                    '<th>&alpha;</th>' +
                    '<th>&beta;</th>' +
                    '<th>&gamma;</th>' +
                    '<th>Obs</th>' +
                    '<th>Unique</th>' +
                    '<th>Resolution</th>' +
                    '<th>Rmerge</th>' +
                    '<th>I/sig(I)</th>' +           
                    '<th>Compl</th>' +
                    '<th>Multi</th>' +
                '</tr></thead>'         
           
           var out = ''
           var tab = ''
           for (k in ty) {
                t = ty[k]
                if (types[k].length > 0) {
                    out += '<div id="' + t + '"><table>'+thead+types[k].join(' ')+'</table></div>'
                    tab += '<li><a href="#' + t + '">'+k+'</a></li>'
                }
           }
           
           if (json.length > 0) out = '<ul>' + tab + '</ul>' + out
           else out = '<p>No auto processing results found for this data collection</p>'

           d.html(out)
           d.tabs('refresh')
           d.tabs('option', 'active', 0)
           d.slideDown();           
        }
           
    })
  }
  
  // Async load of EDNA/Mosflm strategies
  function load_strategy(d, id) {
    $.ajax({
        url: '/ajax/strat/id/' + id,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
            var out = ''
           
            $.each(json, function(i,r) {
                exp = r['TIME']
                trn = r['ATRAN']
                   
                if (exp != r['NEXP'] || trn != r['NTRAN']) {
                    exp += ' (' + r['NEXP'] + ')'
                    trn += ' (' + r['NTRAN'] + ')'
                }
                                         
                out += '<tr draggable="true" sid="'+i+'">' +
                    '<td>'+r['COM']+'</td>' +

                    '<td>'+r['SG']+'</td>' +
                    '<td>'+r['A']+'</td>' +
                    '<td>'+r['B']+'</td>' +
                    '<td>'+r['C']+'</td>' +
                    '<td>'+r['AL']+'</td>' +
                    '<td>'+r['BE']+'</td>' +
                    '<td>'+r['GA']+'</td>' +
                   
                    '<td>'+r['ST']+'</td>' +
                    '<td>'+r['OSCRAN']+'</td>' +
                    '<td>'+r['RES']+'</td>' +
                    '<td>'+r['TRAN']+'</td>' +
                    '<td>'+trn+'</td>' +
                    '<td>'+exp+'</td>' +
                    '<td>'+r['NIMG']+'</td>' +
                '</tr>'                  
            })
           
            var thead = '<thead><tr>' +
                    '<th>Strategy</th>' +
                    '<th>SG</th>' +
                    '<th>A</th>' +
                    '<th>B</th>' +
                    '<th>C</th>' +
                    '<th>&alpha;</th>' +
                    '<th>&beta;</th>' +
                    '<th>&gamma;</th>' +
                    '<th>&Omega; Start</th>' +
                    '<th>&Omega; Osc</th>' +
                    '<th>Res (&#197;)</th>' +
                    '<th>Rel Trn (%)</th>' +
                    '<th>Abs Trn (%)</th>' +
                    '<th>Exposure (s)</th>' +
                    '<th>No. Images</th>' +
                '</tr></thead>'
  
           if (json.length > 0) out = '<table>' + thead + out + '</table>'
           else out = '<p>No strategies found for this data collection</p>'

           d.html(out)
           $.each(json, function(i,r) {
               r['DCID'] = id;
               $('.data_collection[dcid="'+id+'"] tr[sid='+i+']').data('strat', r)
           })
           
           d.slideDown();
           
        }
           
    })  
  }
  
  
  // Async load downstream processing results
  function load_downstream(d, id) {
    var ty = {'Fast EP':'fast_ep', 'Dimple': 'dimple'}  
  
    $.ajax({
        url: '/ajax/dp/id/' + id,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           var types = new Array()
           for (var k in ty) types[k] = new Array()
           
           $.each(json, function(i,r) {
                // Fast DP
                if (r['TYPE'] == 'Fast EP') {
                
                  var table = ''
                  for(var i = 0; i < r['ATOMS'].length; i++) {
                    table += '<tr>' +
                            '<td>'+r['ATOMS'][i][0]+'</td>' +
                            '<td>'+r['ATOMS'][i][1]+'</td>' +
                            '<td>'+r['ATOMS'][i][2]+'</td>' +
                            '<td>'+r['ATOMS'][i][3]+'</td>' +
                            '<td>'+r['ATOMS'][i][4]+'</td>' +
                           '</tr>'
                  }
                
                  var thead = '<thead><tr>' +
                                '<th>#</th>' +
                                '<th>X</th>' +
                                '<th>Y</th>' +
                                '<th>Z</th>' +
                                '<th>Occ</th>' +
                               '</tr></thead>'

                  types[r['TYPE']].push('<div class="plot_fastep"></div>')
                  types[r['TYPE']].push('<ul><li>Figure of Merit: '+r['FOM']+'</li><li>Pseudo-free CC: '+r['CC']+'</li></ul>')
                  types[r['TYPE']].push('<table class="atoms">'+thead+table+'</table>')

                  
                  // Dimple
                  } else if (r['TYPE'] == 'Dimple') {
                    var stats = ['','','']
                    var repl = {'R factor': 'R', 'R free': 'Rfree', 'Rms BondLength': 'RMS Bonds', 'Rms BondAngle': 'RMS Angles'}
                    for (i=0; i<r['STATS'].length-1;i++) {
                        v = r['STATS'][i]
                        if (i == 0) {
                            stats[0] += '<td></td>'
                            stats[1] += '<td>'+v[1]+'</td>'
                            stats[2] += '<td>'+v[2]+'</td>'
                            //stats += '<thead><tr><th>' + v[0] + '</th><th>' + v[1] + '</th><th>' + v[2] + '</th></tr></thead>'
                        } else {
                            stats[0] += '<td>'+repl[v[0]]+'</td>'
                            stats[1] += '<td>'+v[1]+'</td>'
                            stats[2] += '<td>'+v[2]+'</td>'
                            //stats += '<tr><td>' + v[0] + '</td><td>' + v[1] + '</td><td>' + v[2] + '</td></tr>'
                        }
                    }
                  
                    blobs = ''
                    for (var i = 0; i < r['BLOBS']; i++) {
                        var bi = i == 0 ? '<img src="/image/dimp/id/'+id+'" alt="Dimple Blob 1" />' : ''
                        blobs += '<a href="/image/dimp/id/'+id+'/n/'+(i+1)+'" rel="lightbox-d'+id+'" title="Dimple Blob '+(i+1)+'">'+bi+'</a>'
                    }
                  
                    types[r['TYPE']].push('<div class="blobs">'+blobs+'</div>' +
                                          '<table class="rstats"><tr>' + stats.join('</tr><tr>') + '</tr></table>' +
                                          '<div class="plot_dimple"></div>')
                  }
            })
  
            var out = ''
            var tab = ''
            for (k in ty) {
                t = ty[k]
                if (types[k].length > 0) {
                    out += '<div id="' + t + '">'+types[k].join(' ')+'</div>'
                    tab += '<li><a href="#' + t + '">'+k+'</a></li>'
                }
            }
           
            if (json.length > 0) out = '<ul>' + tab + '</ul>' + out
            else out = '<p>No downstream processing found for this data collection</p>'

            d.html(out)

            $.each(json, function(i, r) {
                if (r['TYPE'] == 'Fast EP') {
                   pl_div = $('.data_collection[dcid="'+id+'"] .plot_fastep')
                   if ($(window).width() <= 400) $(pl_div).width(0.97*($(pl_div).parent().parent().parent().width()-14))
                   else $(pl_div).width(0.47*($(pl_div).parent().parent().parent().width()-14))
                   
                    var data = [{ data: r['PLOTS']['FOM'], label: 'FOM' },
                           { data: r['PLOTS']['CC'], label: 'mapCC' }]
                    var options = { grid: { borderWidth: 0 } }

                    $.plot($(pl_div), data, options)
                } else if (r['TYPE'] == 'Dimple') {
                   pl_div = $('.data_collection[dcid="'+id+'"] .plot_dimple')
                   
                   console.log(0.97*($(pl_div).parent().parent().parent().width()-14))
                   if ($(window).width() <= 400) $(pl_div).width(0.93*($(pl_div).parent().parent().parent().width()-14))
                   else { $(pl_div).width(0.47*($(pl_div).parent().parent().parent().width()-14))
                      $(pl_div).height($(pl_div).width()-80)
                   }
                   
                   
                   var data = [{ data: r['PLOTS']['FVC'], label: 'Rfree vs. Cycle' },
                               { data: r['PLOTS']['RVC'], label: 'R vs. Cycle' }]
                   var options = { grid: { borderWidth: 0 } }
                   
                   $.plot($(pl_div), data, options)
                   
                }
            })
    
            d.tabs('refresh')
            d.tabs('option', 'active', 0)
            d.slideDown();
        }
    })
  
  }
  
  
  function arrcmp(a, b) {
    var i = a.length;
    if (i != b.length) return false;
        while (i--) {
            if (a[i] !== b[i]) return false;
        }
        return true;
  }
  
});

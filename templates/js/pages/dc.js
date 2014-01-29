$(function() {
  var lp = 0
  var lp2 = 0
  
  
  var d1 = null
  var d2 = null
  var last = 0
  var lastd = 0
  
  var disablem = true
  var disabled = true
  
  var thread1 = null
  var thread2 = null
  
  // Scroll details based on day
  $('.calendar_days').scroll(function(e) {
    if (disabled) return

    $('li',this).each(function(i,d) {
      var cdp = $(d).offset().left
      if (Math.abs(cdp) < 15) {
        if (!$(d).hasClass('no_event')) {
          d1 = $(d).attr('day')
        }
      }
    })
                             
    clearTimeout(thread1)
    thread1 = setTimeout(function() {
        console.log('fired cal days scroll')
        disablem = true

        if (d1 != last) {
          var dp = $('.calendar_main li[day='+d1+']').offset().top
          $('.calendar_main').animate({scrollTop: dp-$('.calendar_main').offset().top+$('.calendar_main').scrollTop() })
        }
        last = d1;
         
        setTimeout(function() {
          disablem = false
        }, 1000)
    },100)
  })
  
  
  // Jump to day on press
  $('.calendar_days li').click(function(e) {
    if (!$(this).hasClass('no_event')) {
      var day = $(this).attr('day')
      var dp = $('.calendar_main li[day='+day+']').offset().top
      $('.calendar_main').animate({scrollTop: dp-$('.calendar_main').offset().top+$('.calendar_main').scrollTop() })
    }
  })
  
  
  // Scroll day based on details
  $('.calendar_main').scroll(function(e) {
    if (disablem) return
             
    $('li[day]:not(.no_event)',this).each(function(i,d) {
      var cdp = $(d).offset().top - $('.calendar_main').offset().top
      if (Math.abs(cdp) < 5) {
        d2 = $(d).attr('day')
      }
    })
                             
    clearTimeout(thread2)
    thread2 = setTimeout(function() {
      disabled = true
                             
      if (d2 != lastd) {
        var dp = $('.calendar_days li[day='+d2+']').offset().left
        $('.calendar_days').animate({scrollLeft: dp-$('.calendar_days').offset().left+$('.calendar_days').scrollLeft() })
      }
      lastd = d2;
                             
      setTimeout(function() {
        disabled = false
      },1000)
    },100)
  })
  
  
  // Scroll to today
  function _goto_today() {
    if ($('.calendar_days li.today').length) {
  
    var dp = $('.calendar_days li.today').offset().left
    $('.calendar_days').animate({scrollLeft: dp-$('.calendar_days').offset().left+$('.calendar_days').scrollLeft() }, function() {
  
      var dp = $('.calendar_main li.today').offset().top
        $('.calendar_main').animate({scrollTop: dp-$('.calendar_main').offset().top+$('.calendar_main').scrollTop() })
    })
    }
  
    setTimeout(function() {
      disabled = false
      disablem = false
    },1000)
  }
  _goto_today()
  
})
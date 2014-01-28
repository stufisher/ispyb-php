$(function() {
  var lp = 0
  var lp2 = 0
  
  var last = 0
  var lastd = 0
  var disablem = true
  var disabled = true
  
  // Scroll details based on day
  $('.calendar_days').scroll(function(e) {
    if (disabled) return
    disablem = true

    $('li',this).each(function(i,d) {
      var cdp = $(d).offset().left
      if (Math.abs(cdp) < 5) {
        var day = $(d).attr('day')
        if (day != last) {
          if (!$(d).hasClass('no_event')) {
            var dp = $('.calendar_main li[day='+day+']').offset().top
            $('.calendar_main').animate({scrollTop: dp-$('.calendar_main').offset().top+$('.calendar_main').scrollTop() })
          }
        }
        last = day;
      }
    })
    setTimeout(function() {
      disablem = false
    }, 1000)
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
    disabled = true
                             
    $('li[day]:not(.no_event)',this).each(function(i,d) {
      var cdp = $(d).offset().top - $('.calendar_main').offset().top
      if (Math.abs(cdp) < 5) {
        var day = $(d).attr('day')
        if (day != lastd) {
          var dp = $('.calendar_days li[day='+day+']').offset().left
          $('.calendar_days').animate({scrollLeft: dp-$('.calendar_days').offset().left+$('.calendar_days').scrollLeft() })
        }
        lastd = day;
      }
    })
                             
    setTimeout(function() {
      disabled = false
    },1000)
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
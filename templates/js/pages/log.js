$(function() {
  $('ul.full > li').click(function() {
    window.location = $('a', this).eq(0).attr('href')
  })

})
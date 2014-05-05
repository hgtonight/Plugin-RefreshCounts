/* Copyright 2014 Zachary Doll */
jQuery(document).ready(function($) {
  if($('.FilterMenu').length > 0) {
    $('#RefreshCountsButton').clone().appendTo('.FilterMenu');
  }
  else {
    $('#RefreshCountsButton').clone().appendTo('.Wrap:first').addClass('Button').removeClass('SmallButton');
  }
  
  $('#RefreshCounts').remove();
});

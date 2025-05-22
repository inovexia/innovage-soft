jQuery(document).ready(function($) {
  var prevButton = $('.testimonial-slider .elementor-swiper-button-prev');
  var nextButton = $('.testimonial-slider .elementor-swiper-button-next');

  // Check if both buttons exist and aren't already wrapped
  if (prevButton.length && nextButton.length && !prevButton.parent().hasClass('custom-nav-wrapper')) {
    // Wrap both in a new parent div
    prevButton.add(nextButton).wrapAll('<div class="custom-nav-wrapper"></div>');
  }
});





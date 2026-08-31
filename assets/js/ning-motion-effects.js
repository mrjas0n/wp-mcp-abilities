(function($){
  'use strict';
  function initMotion($scope){
    $scope.find('[data-ning-scroll="yes"]').each(function(){
      var $el = $(this);
      var speed = parseFloat($el.attr('data-ning-scroll-speed')) || 0.5;
      var ticking = false;
      var onScroll = function(){
        if(ticking) return;
        ticking = true;
        requestAnimationFrame(function(){
          var rect = $el[0].getBoundingClientRect();
          var vh = window.innerHeight;
          // Only when in viewport
          if(rect.bottom < 0 || rect.top > vh) { ticking=false; return; }
          var progress = (vh - rect.top) / (vh + rect.height);
          var offset = (progress - 0.5) * 100 * speed;
          $el.css('transform', 'translateY('+offset+'px)');
          ticking = false;
        });
      };
      $(window).on('scroll.ningMotion resize.ningMotion', onScroll);
      onScroll();
    });
    $scope.find('[data-ning-mouse="yes"]').each(function(){
      var $el = $(this);
      var intensity = parseFloat($el.attr('data-ning-mouse-intensity')) || 20;
      $el.on('mousemove.ningMotion', function(e){
        var rect = this.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;
        $el.css('transform', 'translate('+(x*intensity)+'px,'+(y*intensity)+'px)');
      });
      $el.on('mouseleave.ningMotion', function(){ $el.css('transform','translate(0,0)'); });
    });
    $scope.find('[data-ning-sticky="top"]').each(function(){
      var $el = $(this);
      var offset = parseInt($el.attr('data-ning-sticky-offset')||'0',10);
      $el.css({position:'sticky', top: offset+'px', zIndex:99});
    });
  }
  $(window).on('elementor/frontend/init', function(){
    if(window.elementorFrontend && elementorFrontend.hooks){
      elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope){ initMotion($scope); });
      // Also for each custom widget
      ['ning-banner','ning-features','ning-cta-banner','ning-testimonials','ning-stats','ning-newsletter','ning-marquee','ning-divider','ning-gallery','ning-product-cards'].forEach(function(w){
        elementorFrontend.hooks.addAction('frontend/element_ready/'+w+'.default', function($scope){ initMotion($scope); });
      });
    }
  });
  // Fallback for non-Elementor frontend
  $(function(){ initMotion($(document.body)); });
})(jQuery);

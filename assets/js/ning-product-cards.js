(function($){
  'use strict';
  var init = function($scope){
    var $root = $scope.find('.wpmp-products');
    if (!$root.length) return;
    var n = parseInt($root.attr('data-count')||'4',10);
    var source = $root.attr('data-source')||'latest';
    if (source === 'manual') return; // manual is server-rendered, no fetch needed
    var cats = $root.attr('data-cats')||'';
    var url = window.location.origin + '/wp-json/wc/store/v1/products?per_page='+n;
    if (cats) url += '&category='+encodeURIComponent(cats);
    var orderby = $root.attr('data-orderby')||'';
    if (orderby) url += '&orderby='+encodeURIComponent(orderby);
    fetch(url, {credentials:'same-origin'}).then(function(r){
      if (!r.ok) throw new Error('bad status '+r.status);
      var ct = r.headers.get('content-type')||'';
      if (ct.indexOf('application/json') === -1) throw new Error('not json');
      return r.json();
    }).then(function(items){
      if(!items||!items.length){ $root.html('<div style="grid-column:1/-1;text-align:center;color:#8A7A6A;padding:30px 0;">No products yet.</div>'); return; }
      $root.empty();
      items.forEach(function(p){
        var img=p.images&&p.images[0]?p.images[0].src:'';
        var price=p.prices&&p.prices.price?(parseInt(p.prices.price,10)/Math.pow(10,p.prices.currency_decimals||2)).toFixed(2):null;
        var cur=p.prices&&p.prices.currency_symbol?p.prices.currency_symbol:'$';
        var card=document.createElement('a'); card.className='wpmp-card'; card.href=p.permalink||'#';
        var im=document.createElement('img'); im.loading='lazy'; im.alt=p.name||''; im.src=img||$root.attr('data-ph')||'https://placehold.co/600x600/FDF6EE/A67C52?text=Product';
        card.appendChild(im);
        var body=document.createElement('div'); body.className='wpmp-body';
        var h=document.createElement('h3'); h.textContent=p.name||'';
        var pr=document.createElement('span'); pr.className='wpmp-price'; pr.textContent=price!==null?cur+price:'';
        body.appendChild(h); body.appendChild(pr); card.appendChild(body);
        $root[0].appendChild(card);
      });
    }).catch(function(e){
      // Fallback to server-rendered noscript if any, otherwise show error
      var fallback = $root.attr('data-fallback');
      if (fallback && fallback.length > 20) { $root.html(fallback); return; }
      $root.html('<div style="grid-column:1/-1;text-align:center;color:#8A7A6A;padding:30px 0;">Could not load products.</div>');
      if (window.console) console.warn('[ning-product-cards] fetch failed', e);
    });
  };
  $(window).on('elementor/frontend/init', function(){
    if (window.elementorFrontend && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction('frontend/element_ready/ning-product-cards.default', init);
    }
  });
  // Fallback for non-Elementor frontend (e.g., preview via shortcode)
  $(function(){ $('.wpmp-products').each(function(){ init($(this).closest('.elementor-widget')); }); });
})(jQuery);

// placeholder for javascript

(function( $, wp, wp_intel) {
  var $document = $(document);

  wp = wp || {};

  wp_intel = wp_intel || {};

  wp_intel.gf_intel = {};

  wp_intel.gf_intel.init = function() {
    var $add_button = $('.intel-add-button');
    if ($add_button.length > 0) {
      $add_button.insertAfter('#trackingEventName');
    }
  };

  wp_intel.gf_intel.init();

})( jQuery, window.wp, window.wp_intel);
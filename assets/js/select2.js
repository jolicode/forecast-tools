import jQuery from 'jquery';
import 'select2';

jQuery(function() {
  jQuery('.select2').select2();
  jQuery(document).on('select2:open', function(e) {
    document.querySelector(`[aria-controls="select2-${e.target.id}-results"]`).focus();
  });
});

import Chart from 'chart.js';
import 'moment/moment.js';
import jQuery from 'jquery';
import 'daterangepicker/daterangepicker.js';

jQuery(document).ready(function() {
  jQuery(function() {
    jQuery('input[name="daterange"]').daterangepicker({
      opens: 'left',
      locale: {
        format: 'DD/MM/YYYY',
        firstDay: 1
      }
    }, function(start, end, label) {
      document.location = baseUrl + '/' + start.format('YYYY-MM-DD') + '/' + end.format('YYYY-MM-DD');
    });
  });
});

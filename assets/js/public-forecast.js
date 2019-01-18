import jQuery from 'jquery';
import 'select2';

let accountData = {};

jQuery(document).ready(function() {
  // reload account data when account changes
  jQuery('#public_forecast_forecastAccount').change((e) => {
    // reset multiple selectors
    const projectsSelector = jQuery('.projects-selector');
    projectsSelector.val(null).trigger("change");
    projectsSelector.html('');
    const clientsSelector = jQuery('.clients-selector');
    clientsSelector.val(null).trigger("change");
    clientsSelector.html('');

    loadData(e.target.value, (data) => {
      projectsSelector.select2({ data: data.projects, multiple: true });
      clientsSelector.select2({ data: data.clients, multiple: true });
      accountData = data;
    });
  });

  // initialize select2 components
  jQuery('#public_forecast_forecastAccount').select2();
  jQuery('.projects-selector').select2({ multiple: true });
  jQuery('.clients-selector').select2({ multiple: true });

  loadData(jQuery('#public_forecast_forecastAccount')[0].value, (data) => {
    accountData = data;

    if (jQuery('.projects-selector').children().length === 0) {
      jQuery('.projects-selector').select2({ data: data.projects, multiple: true });
    }
    if (jQuery('.clients-selector').children().length === 0) {
      jQuery('.clients-selector').select2({ data: data.clients, multiple: true });
    }
  });
});

function loadData(accountId, callback) {
  jQuery.ajax({
    url: '/data/' + accountId,
    dataType: 'json',
    success: callback
  });
}

import 'bootstrap/js/dist/util';
import 'bootstrap/js/dist/tab';
import jQuery from 'jquery';
import 'select2';

const projectOverrideSelector = 'select[name^="forecast_alert[projectOverrides]"]';
const clientOverrideSelector = 'select[name^="forecast_alert[clientOverrides]"]';
let accountData = {};

jQuery(document).ready(function() {
  jQuery('.overrides-container').each((index, container) => {
    container = jQuery(container);

    let $addItemButton = jQuery('<button type="button" class="add_item_link btn btn-secondary">Add a new item</button>');
    $addItemButton.on('click', function(e) {
      addItemForm(container, $newLinkDiv);
    });
    let $newLinkDiv = jQuery('<div class="add-new-item"></div>').append($addItemButton);
    container.append($newLinkDiv);

    // add a delete link to all of the existing item form li elements
    container.children('div[class!="add-new-item"]').each(function() {
      addItemFormDeleteLink(jQuery(this));
    });

    // count the current form inputs we have (e.g. 2), use that as the new
    // index when inserting a new item (e.g. 2)
    container.data('index', container.find(':input').length);
  });

  // reload account data when account changes
  jQuery('#forecast_alert_forecastAccount').change((e) => {
    // reset multiple selectors
    const timeoffSelector = jQuery('.timeoff-projects-selector');
    timeoffSelector.val(null).trigger("change");
    timeoffSelector.html('');
    const onlyUsersSelector = jQuery('.users-selector');
    onlyUsersSelector.val(null).trigger("change");
    onlyUsersSelector.html('');

    loadData(e.target.value, (data) => {
      timeoffSelector.select2({ data: data.projects, multiple: true });
      onlyUsersSelector.select2({ data: data.users, multiple: true });
      accountData = data;
      jQuery('.overrides-container').children('div[class!="add-new-item"]').remove();
    });
  });

  // initialize select2 components
  jQuery('#forecast_alert_forecastAccount').select2();
  jQuery('.timeoff-projects-selector').select2({ multiple: true });
  jQuery('.users-selector').select2({ multiple: true });
  jQuery(clientOverrideSelector).select2();
  jQuery(projectOverrideSelector).select2();

  loadData(jQuery('#forecast_alert_forecastAccount')[0].value, (data) => {
    accountData = data;

    if (jQuery('.timeoff-projects-selector').children().length === 0) {
      jQuery('.timeoff-projects-selector').select2({ data: data.projects, multiple: true });
    }
    if (jQuery('.users-selector').children().length === 0) {
      jQuery('.users-selector').select2({ data: data.users, multiple: true });
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

function addItemForm(collectionHolder, $newLinkDiv) {
  // Get the data-prototype explained earlier
  let prototype = collectionHolder.data('prototype');

  // get the new index
  let index = collectionHolder.data('index');

  // Replace '$$name$$' in the prototype's HTML to
  // instead be a number based on how many items we have
  const newForm = jQuery(prototype.replace(/__name__/g, index));
  const projectsSelector = newForm.find(projectOverrideSelector);
  const clientsSelector = newForm.find(clientOverrideSelector);
  projectsSelector.html('');
  clientsSelector.html('');
  projectsSelector.select2({ data: accountData.projects });
  clientsSelector.select2({ data: accountData.clients });

  // increase the index with one for the next item
  collectionHolder.data('index', index + 1);

  // add a delete link to the new form
  addItemFormDeleteLink(newForm);

  $newLinkDiv.before(newForm);
}

function addItemFormDeleteLink($itemFormDiv) {
  let $removeFormButton = jQuery('<button type="button" class="btn btn-danger">Delete this item</button>');
  $itemFormDiv.append($removeFormButton);

  $removeFormButton.on('click', function(e) {
    // remove the div for the item form
    $itemFormDiv.remove();
  });
}

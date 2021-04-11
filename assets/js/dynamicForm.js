import 'bootstrap/js/dist/util';
import 'bootstrap/js/dist/tab';
import jQuery from 'jquery';
import 'select2';

export default(label = 'Add a new item') => {
  jQuery(() => {
    jQuery('.dynamic-form-container').each((index, container) => {
      container = jQuery(container);

      let $addItemButton = jQuery('<button type="button" class="add_item_link btn btn-secondary">' + label + '</button>');
      $addItemButton.on('click', function (e) {
        addItemForm(container, $newLinkDiv);
      });
      let $newLinkDiv = jQuery('<div class="add-new-item"></div>').append($addItemButton);
      container.append($newLinkDiv);

      // add a delete link to all of the existing item form li elements
      container.children('div[class!="add-new-item"]').each(function () {
        addItemFormDeleteLink(jQuery(this));
      });

      // count the current form inputs we have (e.g. 2), use that as the new
      // index when inserting a new item (e.g. 2)
      container.data('index', container.find(':input').length);
    });

    // initialize select2 components
    jQuery('.dynamic-form-container .select2').select2();
  });

  function addItemForm(collectionHolder, $newLinkDiv) {
    // Get the data-prototype explained earlier
    let prototype = collectionHolder.data('prototype');

    // get the new index
    let index = collectionHolder.data('index');

    // Replace '$$name$$' in the prototype's HTML to
    // instead be a number based on how many items we have
    const newForm = jQuery(prototype.replace(/__name__/g, index));
    newForm.find('.select2').select2();

    // increase the index with one for the next item
    collectionHolder.data('index', index + 1);

    // add a delete link to the new form
    addItemFormDeleteLink(newForm);

    $newLinkDiv.before(newForm);
  }

  function addItemFormDeleteLink($itemFormDiv) {
    let $removeFormButton = jQuery('<button type="button" class="btn btn-danger">Delete</button>');
    let $removeButtonDiv = jQuery('<div class="col-md-2 p-0 text-right"></div>').append($removeFormButton);
    $itemFormDiv.append($removeButtonDiv);

    $removeFormButton.on('click', function (e) {
      // remove the div for the item form
      $itemFormDiv.remove();
    });
  }
}

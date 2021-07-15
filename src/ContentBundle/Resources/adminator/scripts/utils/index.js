import * as $ from 'jquery';

export default (function () {
    $(document).on('click', '.rabble-collection .collection-add', function (e) {
        e.preventDefault();
        let prototypeName = $(this).data('prototype-name');
        let collection = $(this).closest('.rabble-collection');
        let i = parseInt(collection.data('items'));
        let items = collection.find('*[data-prototype]');
        let prototype = items.data('prototype').replace(new RegExp(prototypeName, 'g'), i);
        let item = $(prototype);
        item.appendTo(items);
        collection.data('items', i + 1);
    });

    $(document).on('click', '.rabble-content-blocks .content-block-add', function (e) {
        e.preventDefault();
        let prototypeName = $(this).data('prototype-name');
        let collection = $(this).closest('.rabble-content-blocks');
        let contentBlockType = $(this).data('content-block');
        let i = parseInt(collection.data('items'));
        let items = collection.find('*[data-prototype-'  + contentBlockType + ']');
        let prototype = items.data('prototype-' + contentBlockType).replace(new RegExp(prototypeName, 'g'), i);
        let item = $(prototype);
        item.appendTo(items);
        collection.data('items', i + 1);
        let length = collection.find('.collection-item').length;
        if(items.data('max-size') > 0 && length >= items.data('max-size')) {
            $(this).closest('.dropdown').hide();
        }
    });

    $(document).on('click', '.rabble-collection .collection-remove, .rabble-content-blocks .collection-remove', function (e) {
        e.preventDefault();
        let collection = $(this).closest('.rabble-collection, .rabble-content-blocks');
        let length = collection.find('.collection-item').length;
        let items = collection.find('*[data-max-size]');
        if(0 !== items.length && items.data('max-size') > 0 && length - 1 < items.data('max-size')) {
            items.parent().find('.content-block-add').closest('.dropdown').show();
        }
        $(this).closest('.collection-item').remove();
    });

    $(document).on('click', '.reload-slug', function (e) {
        e.preventDefault();
        let title = $($(this).data('title')).val();
        let field = $('#' + $(this).data('field'));
        let icon = $(this).find('.fa');
        icon.addClass('fa-spin');
        $.ajax({
            'url': $(this).data('resolver') + '?title=' + title,
            'success': function (result) {
                field.val(result.value);
                icon.removeClass('fa-spin');
            }
        });
    });
});

import Sortable from 'sortablejs';
import * as $ from 'jquery';
import MutationObserver from 'mutation-observer';

export default (function () {
    let observer = new MutationObserver(function() {
        let sortableElements = $('.sortable');
        sortableElements.each(function() {
            if($(this).data('sortable')) {
                return;
            }
            $(this).data('sortable', true);
            Sortable.create(this, {
                group: $(this).data('group'),
                direction: 'vertical',
                animation: 250,
                handle: ".sort-handle",
                forceFallback: true,
                onEnd: function (evt) {
                    let from = $(evt.from);
                    from.children().each(function (index) {
                        $(this).find('.sort_order').val(index);
                    })
                },
            });
        });
    });
    observer.observe(document, {
        subtree: true,
        childList: true,
        attributes: false
    });
});
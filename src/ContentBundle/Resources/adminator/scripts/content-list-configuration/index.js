import ContentListConfiguration from './content-list-configuration';
import ReactDOM from "react-dom";
import React from 'react';
import MutationObserver from 'mutation-observer';


let observer = new MutationObserver(function () {
    let items = document.getElementsByClassName("content-list-configure");

    for (let i = 0; i < items.length; i++) {
        let item = items.item(i);
        item.className = '';
        ReactDOM.render((
            <ContentListConfiguration form={item.dataset.form}
                                      label={item.dataset.label}
                                      id={item.dataset.id}
                                      save={item.dataset.save}
                                      configure={item.dataset.configure}/>), item);
    }
});

observer.observe(document, {
    subtree: true,
    childList: true,
    attributes: false
});
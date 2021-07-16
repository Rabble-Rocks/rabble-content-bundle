import SlugReloader from './reload-slug';
import ReactDOM from "react-dom";
import React from 'react';
let items = document.getElementsByClassName("reload-slug");

for (let i = 0; i < items.length; i++) {
    let item = items.item(i);
    ReactDOM.render((<SlugReloader content={item.dataset.content} title={item.dataset.title} resolver={item.dataset.resolver} field={item.dataset.field}/>), item);
}
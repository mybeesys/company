import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import ProductComponent from './product/ProductComponent';
import CategoryTree from './product/CategoryTree';
import Modifiertree from './modifier/modifiertree';
import Attributetree from './attributes/attributetree';
import CustomMenuTable from './custommenu/CustomMenuTable';
import CustomMenuDetail from './custommenu/CustomMenuDetail';

var htmlElement = document.querySelector("html");
const dir =   htmlElement.getAttribute('dir');

if(dir == 'ltr')
  await import('./style.scss');
else
 await import('./style.rtl.scss');

const Element1 = document.getElementById('category-root');

if (Element1) {
  const root = ReactDOM.createRoot(Element1);
  root.render(<CategoryTree />);
}

const Element2 = document.getElementById('product-root');
 
if (Element2) {
  const root = ReactDOM.createRoot(Element2);
  root.render(<ProductComponent />);
}

const Element3 = document.getElementById('modifier-root');
 
if (Element3) {
  const root = ReactDOM.createRoot(Element3);
  root.render(<Modifiertree />);
}
const Element4 = document.getElementById('attribute-root');
 
if (Element4) {
  const root = ReactDOM.createRoot(Element4);
  root.render(<Attributetree />);
}

const Element5 = document.getElementById('custommenu-root');
 
if (Element5) {
  const root = ReactDOM.createRoot(Element5);
  root.render(<CustomMenuTable dir={dir}/>);
}

const Element6 = document.getElementById('custommenuedit-root');
 
if (Element6) {
  const root = ReactDOM.createRoot(Element6);
  root.render(<CustomMenuDetail dir={dir} />);
}
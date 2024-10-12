import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import ProductComponent from './product/ProductComponent';
import CategoryTree from './product/CategoryTree';
import Modifiertree from './modifier/modifiertree';

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


import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import ProductComponent from './ProductComponent';
import ProductTree from './ProductTree2';
import Modifiertree from './modifier/modifiertree';


const Element1 = document.getElementById('react-root');

if (Element1) {
const dir = Element1.getAttribute('dir');
if(dir == 'ltr')
     await import('./style.scss');
else
    await import('./style.rtl.scss');
  const root = ReactDOM.createRoot(Element1);
  root.render(<ProductTree />);
}

const Element2 = document.getElementById('product-root');
 

if (Element2) {
  const  dir = Element2.getAttribute('dir');
  if(dir == 'ltr')
    await import('./style.scss');
else
   await import('./style.rtl.scss');
  const root = ReactDOM.createRoot(Element2);
  root.render(<ProductComponent />);
}

const Element3 = document.getElementById('modifier-root');

if (Element3) {
const dir = Element3.getAttribute('dir');
if(dir == 'ltr')
     await import('./style.scss');
else
    await import('./style.rtl.scss');
  const root = ReactDOM.createRoot(Element3);
  root.render(<Modifiertree />);
}
import React from 'react';
import ReactDOM from 'react-dom/client';
import TreeTableModifier from './TreeTableModifier';


const App = () => {
    const rootElement = document.getElementById('modifier-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableModifier urlList = {urlList}
        rootElement ={rootElement}
          />
      </div>
    );
  };
  
ReactDOM.createRoot(document.getElementById('modifier-root')).render(<App />);
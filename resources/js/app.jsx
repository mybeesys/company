import React from 'react';
import ReactDOM from 'react-dom/client';
import TreeTableProduct from "./components/TreeTableProduct";


const App = () => {
    const rootElement = document.getElementById('react-root');
    const userData = JSON.parse(rootElement.getAttribute('data-user'));
  
    return (
      <div>
        <TreeTableProduct initialData = {userData} />
      </div>
    );
  };
  
ReactDOM.createRoot(document.getElementById('react-root')).render(<App />);
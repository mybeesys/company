import React , { useState, useCallback  } from 'react';
import axios from 'axios';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column'; 
import { Button } from 'primereact/button';
import { Dropdown, Form } from 'react-bootstrap';
import SweetAlert2 from 'react-sweetalert2';

const ingredient = ({translations}) => 
    {
        const rootElement = document.getElementById('root');
        const urlList = JSON.parse(rootElement.getAttribute('list-url'));


    };        
export default ingredient;
 
import React , { useState, useEffect  } from 'react';
import axios from 'axios';
import { Column } from 'primereact/column'; 
import SweetAlert2 from 'react-sweetalert2';
import DeleteModal from '../product/DeleteModal';
import { TreeTable } from 'primereact/treetable';

const UnitTree = ({translations, nodes, handleSubmit ,actionTemplate ,renderCell}) => 
    {

          return (   
                <form  id="treeForm" noValidate validated={true} class="needs-validation" onSubmit={handleSubmit}>
                    <TreeTable  value={nodes}  tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"}>
                        <Column header={translations.name_en} body={(node) => (renderCell(node, 'name_en', true,[] , "Text"))} sortable></Column>
                        <Column header={translations.name_ar} body={(node) => (renderCell(node, 'name_ar' , false , [] ,"Text"))} sortable></Column>
                        <Column  body={(node) => (actionTemplate(node))} />
                    </TreeTable>
                </form>
        );
    };        
export default UnitTree;
 
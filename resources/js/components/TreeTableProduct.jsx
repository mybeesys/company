import React, { useState, useEffect } from 'react';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { InputText } from 'primereact/inputtext';
import '@/assets/css/style.bundle.css';
import './style.scss'; 
        

const TreeTableProduct = ({ initialData }) => {
    const [nodes, setNodes] = useState([]);

    useEffect(() => {
        setNodes(initialData);
    }, []);

    const actionTemplate = (id) => {
        return (
         
            <div className="flex flex-wrap gap-2">
                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			  <i class="ki-outline ki-switch fs-2"></i>
			</a>
			<a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
				<i class="ki-outline ki-pencil fs-2"></i>
			</a>
			<a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
				<i class="ki-outline ki-trash fs-2"></i>
			</a>
            </div>
        );
    };

    const onEditorValueChange = (props, value) => {
        let updatedNodes = [...nodes];
        props.node.data[props.field] = value; // Update the specific field of the node
        setNodes(updatedNodes); // Update the state with the modified nodes
    };

    // Input editor for editing cells
    const inputTextEditor = (props) => {
        return (
            <InputText 
                type="text" 
                value={props.node.data[props.field]} 
                onChange={(e) => onEditorValueChange(props, e.target.value)} 
            />
        );
    };

    return (
    
            <TreeTable value={nodes} tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"} editable>
                <Column field="name_en" header="En Name" editor={(props) => inputTextEditor(props)} expander></Column>
                <Column field="name_ar" header="Ar Name"></Column>
                <Column field="price" header="Price"></Column>
                <Column field="cost" header="Cost"></Column>
                <Column field="order" header="Order"></Column>
                <Column header="Active" body={(node) => (
                <div>
                  <input type="checkbox"  checked={node.data.active} class="form-check-input" data-kt-check={node.data.active} data-kt-check-target=".widget-10-check" />
                </div>
                )} />
                <Column body={(node) => (actionTemplate(node.data.id))} headerClassName="w-10rem" />
            </TreeTable>
      
    );
};

export default TreeTableProduct;
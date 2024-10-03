import React, { useState, useEffect } from 'react';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { InputText } from 'primereact/inputtext';
import AddModal from './AddModal';
import axios from 'axios';
import '@/assets/css/style.bundle.css';
import './style.scss'; 
        

const TreeTableProduct = ({ urlList , categoryurl ,subcategoryurl, producturl }) => {
    const [nodes, setNodes] = useState([]);
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [parent_id, setParentId] = useState(null);
    const [category_id, setCategoryId] = useState(null);
    const [url , setUrl] = useState('');
    const [type , setType] = useState('');

    const handleProductAdded = (newProduct) => {  
        setIsModalVisible(false);
        refreshTree();   
    };

    const handleClose = () =>{
        setIsModalVisible(false);
    }

    const refreshTree =() =>
    {
        try{
            const response = axios.get(urlList).then(res => {
                console.log(res.data)
                setNodes(res.data);
              });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    useEffect(() => {
        refreshTree();
    }, []);

    const openAddCategory = ()=>
    {
        setUrl(categoryurl);
        setType("Category");
        setIsModalVisible(true);
    }

    const openAddModel = (data , type) => {
        if (data.type == "Category")
        {
            setUrl(subcategoryurl);
            setCategoryId(data.id);
            setType("SubCategory");
        }
        else if(data.type == "SubCategory" && type == "SubCategory")
        {
            setUrl(subcategoryurl);
            setParentId(data.id);
            setCategoryId(data.category_id);
            setType("SubCategory");
        }
        else
        {
            setUrl(producturl);
            setParentId(data.id);
            setCategoryId(data.category_id);
            setType("Product");
        }
      
        setIsModalVisible(true);
    };


    const actionTemplate = (data) => {
        return (
            <div className="flex flex-wrap gap-2">
            
            {(data.type == "Category") ? (<><a href="#"  onClick={() => openAddModel(data , "Category")} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-plus fs-2"></i>
			   </a></>):(<></>)}

            {(data.type == "SubCategory") ?
               (<><a href="#"  onClick={() => openAddModel(data , "SubCategory")} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-switch fs-2"></i>
			   </a>
               <a href="#"  onClick={() => openAddModel(data , "Product")} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-plus fs-2"></i>
			   </a></>) : <></>}
    

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
<div class="card mb-5 mb-xl-8">    
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Category List</span>
                <span class="text-muted mt-1 fw-semibold fs-7">Product List</span>
            </h3>
            <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
        <a href="#" class="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold" 
         onClick={() => openAddCategory()}>Add</a>
        <AddModal
                visible={isModalVisible}
                onHide={() => setIsModalVisible(false)}
                onProductAdded={handleProductAdded}
                onClose = {handleClose}
                type={type}
                url={url}
                category_id = {category_id}
                parent_id ={parent_id}
                row={{active:true}}
            />
        </div>
        </div>
        </div>
        <div class="card-body">
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
                <Column body={(node) => (actionTemplate(node.data))} headerClassName="w-10rem" />
            </TreeTable>
        </div>

</div>
    );
};

export default TreeTableProduct;
import React, { useState, useEffect } from 'react';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { InputText } from 'primereact/inputtext';
import AddModal from './AddModal';
import DeleteModal from './DeleteModal';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import '@/assets/css/style.bundle.css';
import './style.scss'; 
        

const TreeTableProduct = ({ urlList , categoryurl ,subcategoryurl, producturl }) => {
    const [nodes, setNodes] = useState([]);
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [parent_id, setParentId] = useState(null);
    const [category_id, setCategoryId] = useState(null);
    const [url , setUrl] = useState('');
    const [type , setType] = useState('');
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState('-1');
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});

    const handleProductAdded = (message) => {  
        setIsModalVisible(false);
        if(message != "Done")
        {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: message ,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
               }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
              });
        }
       
        refreshTree();   
    };

    const handleDelete = (message) => {  
        setIsDeleteModalVisible(false);
        if(message != "Done")
        {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: message ,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
               }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
              });
        }
        refreshTree();   
    };

    const handleClose = () =>{
        setIsModalVisible(false);
        setIsDeleteModalVisible(false);
    }

    const refreshTree =() =>
    {
        try{
            const response = axios.get(urlList).then(res => {
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

    const editRow = (data, key) =>{
        setCurrentKey(key);
        setEditingRow({...data});
    }

    const handleEditorChange = (value, key) =>{
        editingRow[key] = value;
        setEditingRow({...editingRow})
    }
    const updateRow =async () => {
        
        let editedNode = findNodeByKey(nodes, currentKey);
        for (var key in editedNode.data) {
            editedNode.data[key]= editingRow[key];
        }

        setCurrentKey('-1');

        let url = categoryurl;

        if(editedNode.data.type == "SubCategory")
        {
             url = subcategoryurl;
        }

        if(editedNode.data.type != "Product")
        {
            const response = await axios.post(url, editedNode.data);
            refreshTree();
            if(response.data.message != "Done")
            {  
                setShowAlert(true);
                Swal.fire({
                    show: showAlert,
                    title: 'Error',
                    text: response.data.message ,
                    icon: "error",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false,
                }).then(() => {
                    setShowAlert(false); // Reset the state after alert is dismissed
                });
            }
            
        }
        //var index = nodes.findIndex((node) =>  node.data.id == currentId);
        //nodes[index].data = {...editingRow}
        //setNodes([...nodes]);

    }

    const findNodeByKey = (nodes, key) => {
        let path;
        key = (key).toString();
        path = key.split('-');
    
        console.log(key);
        let node;

        while (path.length) {
            let list = node ? node.children : nodes;

            node = list[parseInt(path[0], 10)];
            console.log(parseInt(path[0], 10))
            path.shift();
        }

        return node;
    };

    const renderTextCell = (node, key, autoFocus) =>{
        const indent= (node.key).toString().split('-').length;
        return (
        node.key == currentKey ?  
            <input type="text" class={`form-control text-editor text-indent-${indent}`} style={{width: `${100-(10*indent)}%!important`}}
            defaultValue={node.data[key]}
            onChange={(e) => handleEditorChange(e.target.value, key)}
            autoFocus = {!!autoFocus}
            onKeyDown={(e) => e.stopPropagation()} />
        : 
            <span>{node.data[key]}</span>);
    }

    const renderCheckCell = (node, key, autoFocus) => {
        return (
            node.key == currentKey ?  
            <div>
                <input type="checkbox" defaultChecked={false}  checked={node.data[key]} 
                    class="form-check-input" data-kt-check={node.data[key]} 
                    data-kt-check-target=".widget-10-check" 
                    onChange={(e) => handleEditorChange(e.target.checked, key)}
                    />
            </div>
            :
            <div>
                <input type="checkbox" defaultChecked={false}  checked={node.data[key]} 
                    class="form-check-input" data-kt-check={node.data[key]} 
                    data-kt-check-target=".widget-10-check" disabled/>
            </div>
        )    
    }

    const renderNumberCell = (node, key, autoFocus) =>{
        const indent= (node.key).toString().split('-').length;
        return (
        node.key == currentKey ?  
            <input type="number"  min="0" class={`form-control text-editor number-indent-${indent}`}
            defaultValue={node.data[key]}
            onChange={(e) => handleEditorChange(e.target.value, key)}
            autoFocus = {!!autoFocus}
            onKeyDown={(e) => e.stopPropagation()} />
        : 
            <span>{node.data[key]}</span>);
    }

    const openDeleteModel=(data) =>
    {
        setCurrentNode(data);
        if (data.type == "Category")
        {
            setUrl(categoryurl);
        }
        else if(data.type == "SubCategory")
        {
            setUrl(subcategoryurl);
        }
        else
        {
            setUrl(producturl);
        }
      
        setIsDeleteModalVisible(true);
    }

    const actionTemplate = (node) => {
        const data= node.data;
        return (
            <div className="flex flex-wrap gap-2">
            
            {(data.type == "Category") ? (
                <>
                <a href="#"  onClick={() => openAddModel(data , "Category")} title="Add Subcategory"  class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-plus fs-2"></i>
			    </a>

                { ((currentKey =='-1') || (currentKey !='-1' && node.key == currentKey))?
                <a href="#"  onClick={() => {
                    if(currentKey =='-1') 
                        editRow(data , node.key)
                    else{
                        updateRow()
                    }
                }
                } title="Edit"  class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class={(currentKey !='-1' &&  node.key == currentKey) ?"ki-outline ki-check fs-2":"ki-outline ki-pencil fs-2"}></i>
			    </a> : <></>}
               </>):(<></>)}

            {(data.type == "SubCategory") ?
               (<><a href="#"  onClick={() => openAddModel(data , "SubCategory")} title="Add Subcategory" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-plus fs-2"></i>
			   </a>
               <a href="#"  onClick={() => openAddModel(data , "Product")} title="Add Product"  class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			      <i class="ki-outline ki-plus-square fs-2"></i>
			   </a>

               { ((currentKey =='-1') || (currentKey !='-1' && node.key == currentKey))?
               <a href="#"  onClick={() => {
                    if(currentKey =='-1') 
                        editRow(data , node.key)
                    else{
                        updateRow();
                    }
                }}
                title="Edit"  class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
			        <i class={(currentKey !='-1' &&  node.key == currentKey) ? "ki-outline ki-check fs-2":"ki-outline ki-pencil fs-2"}></i>
			    </a> :<></>}
                </>) : <></>}
    
			<a href="#"  onClick={() => openDeleteModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
				<i class="ki-outline ki-trash fs-2"></i>
			</a>
            </div>
        );
    };

    return (
<div class="card mb-5 mb-xl-8">
     <SweetAlert2 />   

        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Category List</span>
                <span class="text-muted mt-1 fw-semibold fs-7">Product List</span>
            </h3>
            <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
        <a href="#" class="btn btn-primary" 
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
            <DeleteModal
                visible={isDeleteModalVisible}
                onClose={handleClose}
                onDelete={handleDelete}
                url={url}
                row={currentNode}
            />


        </div>
        </div>
        </div>
        <div class="card-body">
            <TreeTable value={nodes} tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"}>
                <Column header="En Name" body={(node) => (renderTextCell(node, 'name_en', true))} expander></Column>
                <Column header="Ar Name" body={(node) => (renderTextCell(node, 'name_ar'))}></Column>
                <Column field="price" header="Price"></Column>
                <Column field="cost" header="Cost"></Column>
                <Column header="Order" body={(node) => (renderNumberCell(node, 'order'))} ></Column>
                <Column header="Active" body={(node) => (renderCheckCell(node, 'active'))}> </Column>
                <Column body={(node) => (actionTemplate(node))} />
            </TreeTable>
        </div>

</div>
    );
};

export default TreeTableProduct;
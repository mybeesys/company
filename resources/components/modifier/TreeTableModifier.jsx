import React, { useState, useEffect } from 'react';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { InputText } from 'primereact/inputtext';
import AddModal from '../AddModal';
import DeleteModal from '../DeleteModal';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import '@/assets/css/style.bundle.css';
import '../style.scss';


const TreeTableModifier = ({ urlList, rootElement }) => {
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState('');
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState('-1');
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});
    const [validated, setValidated] = useState(false);
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    const [translations, setTranslations] = useState({});

    const handleProductAdded = (message) => {
        setIsModalVisible(false);
        if (message != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: message,
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
        if (message != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: message,
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

    const handleClose = () => {
        setIsModalVisible(false);
        setIsDeleteModalVisible(false);
    }

    const refreshTree = () => {
        try {
            const response = axios.get(urlList).then(response => {
                let result = response.data;
                setNodes(result);
            });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    useEffect(() => {
        axios.get(localizationurl)
        .then(response => {
          setTranslations(response.data);
        })
        .catch(error => {
          console.error('Error fetching translations', error);
        });
        refreshTree();
    }, []);

  

    const editRow = (data, key) => {
        setCurrentKey(key);
        setEditingRow({ ...data });
    }

    const handleEditorChange = (value, key) => {
        editingRow[key] = value;
        setEditingRow({ ...editingRow })
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
		event.stopPropagation();
		const form = event.currentTarget;
		if (form.checkValidity() === false) {

			setValidated(true);
            form.classList.add('was-validated');
			return;
		}
        let editedNode = findNodeByKey(nodes, currentKey);
        for (var key in editingRow) {
            editedNode.data[key] = editingRow[key];
        }

        setCurrentKey('-1');

        let url = JSON.parse(rootElement.getAttribute(`${editedNode.data.type}-url`));
        let parentNode = getParentNode(editedNode.key)
        if(!!parentNode)
            editedNode.data[editedNode.data.parentKey] = parentNode.data.id;
        const response = await axios.post(url, editedNode.data);
        refreshTree();
        if (response.data.message != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: response.data.message,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
        }
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

    const getParentNode = (key) => {
        key = (key).toString();
        let seg = key.split('-');
        let parentKey = seg.length == 1 ? null : seg[0];
        for (let index = 1; index < seg.length - 1; index++) {
            parentKey = parentKey + '-' + seg[index];
        }
        if (!!!parentKey)
            return null;
        else
            return findNodeByKey(nodes, parentKey);
    }

    const addInline = (key, type, parentKeyName) => {
        let node = findNodeByKey(nodes, key);
        key = (key).toString();
        let seg = key.split('-');
        let parentKey = seg.length == 1 ? null : seg[0];
        for (let index = 1; index < seg.length - 1; index++) {
            parentKey = parentKey + '-' + seg[index];
        }
        node.data.empty = null;
        let newNode = {
            key: !!!parentKey ? Number(seg[0]) + 1 : parentKey + '-' + (Number(seg[seg.length - 1]) + 1),
            data: { type: type, parentKey: parentKeyName,  empty: 'Y' }
        }
        if (!!!parentKey)
            nodes.push(newNode);
        else {
            let parentNode = findNodeByKey(nodes, parentKey);
            parentNode.children.push(newNode);
        }
        setCurrentKey(key);
        setNodes([...nodes]);
    }

    const renderTextCell = (node, key, autoFocus) => {
        const indent = (node.key).toString().split('-').length;
        if (key == 'name_en' && !!node.data.empty) {
            return <a href='#' onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`Add New ${node.data.type}`}</a>
        }
        else {
            return (
                node.key == currentKey ?
                    <input type="text" class={`form-control text-editor text-indent-${indent}`} style={{ width: `${100 - (10 * indent)}%!important` }}
                        defaultValue={node.data[key]}
                        onChange={(e) => handleEditorChange(e.target.value, key)}
                        autoFocus={!!autoFocus}
                        onKeyDown={(e) => e.stopPropagation()}
                        required/>
                    :
                    <span>{node.data[key]}</span>);
        }

    }

    const renderCheckCell = (node, key, autoFocus) => {
        return (
            node.key == currentKey ?
                <div>
                    <input type="checkbox" defaultChecked={false} checked={node.data[key]}
                        class="form-check-input" data-kt-check={node.data[key]}
                        data-kt-check-target=".widget-10-check"
                        onChange={(e) => handleEditorChange(e.target.checked, key)}
                    />
                </div>
                :
                <div>
                    <input type="checkbox" defaultChecked={false} checked={node.data[key]}
                        class="form-check-input" data-kt-check={node.data[key]}
                        data-kt-check-target=".widget-10-check" disabled />
                </div>
        )
    }

    const renderNumberCell = (node, key, autoFocus) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <input type="number" min="0" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node.data[key]}
                    onChange={(e) => handleEditorChange(e.target.value, key)}
                    autoFocus={!!autoFocus}
                    onKeyDown={(e) => e.stopPropagation()} 
                    required/>
                :
                <span>{node.data[key]}</span>);
    }

    const renderDecimalCell = (node, key, autoFocus) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <input type="number" min="0" step=".01" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node.data[key]}
                    onChange={(e) => handleEditorChange(e.target.value, key)}
                    autoFocus={!!autoFocus}
                    onKeyDown={(e) => e.stopPropagation()} 
                    required/>
                :
                <span>{node.data[key]}</span>);
    }

    const openDeleteModel = (data) => {
        setCurrentNode(data);
      //  setUrl(categoryurl);

        setIsDeleteModalVisible(true);
    }

    const actionTemplate = (node) => {
        const data = node.data;
        return (
            (!!!node.data.empty && node.data.empty != 'Y') ?
                <div className="flex flex-wrap gap-2">

                    {((currentKey == '-1') || (currentKey != '-1' && node.key == currentKey)) ?
                        <a href="#" onClick={() => {
                            if (currentKey == '-1')
                                editRow(data, node.key)
                            else {
                                let btnSubmit = document.getElementById("btnSubmit");
                                btnSubmit.click();
                            }
                        }
                        } title="Edit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                            <i class={(currentKey != '-1' && node.key == currentKey) ? "ki-outline ki-check fs-2" : "ki-outline ki-pencil fs-2"}></i>
                        </a> : <></>}
                    <a href="#" onClick={() => openDeleteModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-outline ki-trash fs-2"></i>
                    </a>
                    <button id="btnSubmit" type="submit" style={{display:"none"}}></button>
                </div> : <></>
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
                        <DeleteModal
                            visible={isDeleteModalVisible}
                            onClose={handleClose}
                            onDelete={handleDelete}
                            url={url}
                            row={currentNode}
                            translations ={translations}
                        />
                    </div>
                </div>
            </div>
            <div class="card-body">
            <form  id="treeForm" noValidate validated={true} class="needs-validation" onSubmit={handleSubmit}>
                <TreeTable  value={nodes} tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"}>
                    <Column header="En Name" body={(node) => (renderTextCell(node, 'name_en', true))} sortable expander></Column>
                    <Column header="Ar Name" body={(node) => (renderTextCell(node, 'name_ar'))} sortable></Column>
                    <Column header="Price" body={(node) => node.data.type == "modifier" ? renderDecimalCell(node, 'price') : <></>} sortable></Column>
                    <Column header="Cost" body={(node) => node.data.type == "modifier" ? renderDecimalCell(node, 'cost'): <></>} sortable></Column>
                    <Column header="Order" body={(node) => (renderNumberCell(node, 'order'))} sortable></Column>
                    <Column header="Active" body={(node) => (renderCheckCell(node, 'active'))} sortable> </Column>
                    <Column body={(node) => (actionTemplate(node))} />
                </TreeTable>
            </form>
            </div>

        </div>
    );
};

export default TreeTableModifier;
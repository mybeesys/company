import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import DeleteModal from '../product/DeleteModal';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { getName, getRowName, toDate } from '../lang/Utils';
import { Calendar } from 'primereact/calendar';

const TreeTableComponent = ({ translations, dir, urlList, editUrl, addUrl, canAddInline, 
                            cols, title, canDelete, canEditRow, expander, defaultValue }) => {

    const formRef = useRef(null);

    const rootElement = document.getElementById('root');
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState('');
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState('-1');
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});
    const [expandedKeys, setExpandedKeys] = useState([]);

    useEffect(() => {
        const fetchData = async () => {
            refreshTree();
        }
        fetchData().catch(console.error);

    }, [urlList]);

    const triggerSubmit = () => {
        handleSubmit(formRef.current);
    };

    const editRow = (data, key) => {
        if (!!!editUrl) {
            setCurrentKey(key);
            setEditingRow({ ...data });
        }
        else {
            window.location.href = editUrl.replace('%', data.id);
        }
    }

    const openAdd = () => {
        window.location.href = addUrl;
    }

    const openDeleteModel = (data) => {
        setUrl(JSON.parse(rootElement.getAttribute(`${data.type}-url`)));
        setCurrentNode(data);
        setIsDeleteModalVisible(true);
    }

    const handleSubmit = async (form) => {
        //event.preventDefault();
        //event.stopPropagation();
        //const form = event.currentTarget;
        if (form.checkValidity() === false) {

            setValidated(true);
            form.classList.add('was-validated');
            return;
        }

        let editedNode = findNodeByKey(nodes, currentKey);
        for (var key in editingRow) {
            editedNode.data[key] = editingRow[key];
        }

        let url = JSON.parse(rootElement.getAttribute(`${editedNode.data.type}-url`));
        let parentNode = getParentNode(editedNode.key)
        if (!!parentNode)
            editedNode.data[editedNode.data.parentKey] = parentNode.data.id;
        const response = await axios.post(url, editedNode.data);
        if (response.data.message != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: translations[response.data.message],
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            return;
        }
        setCurrentKey('-1');
        setEditingRow({});
        refreshTree();
    }

    const handleDelete = (message) => {
        if (message != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: translations[message],
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            return;
        }
        setIsDeleteModalVisible(false);
        refreshTree();
    };

    const handleClose = () => {
        setIsDeleteModalVisible(false);
    }

    const handleEditorChange = (value, key) => {
        editingRow[key] = value;
        setEditingRow({ ...editingRow })
    }


    const cancelEdit = (key) => {
        if (!!!editingRow.id || editingRow.id == 0) {
            let parentNode = getParentNode(key);
            let currentNodes = !!parentNode ? parentNode.children : nodes;
            for (let index = 0; index < currentNodes.length; index++) {
                const node = currentNodes[index];
                if (node.key == key) {
                    if (!!parentNode){
                        parentNode.children[parentNode.children.length-1].key = key;
                        parentNode.children.splice(index, 1);
                    }
                    else{
                        nodes[nodes.length-1].key = key;
                        nodes.splice(index, 1);
                    }
                    break;
                }
            }
        }
        setCurrentKey('-1');
        setEditingRow({});
    }

    const renderCell = (node, col, index) => {
        if (!!col.customCell && !!!node.data.empty) {
            return col.customCell(node.data, col.key, node.key == currentKey, col.editable, refreshTree);
        }
        if (col.type == "Text")
            return renderTextCell(node, col, index);
        else if (col.type == "Number")
            return renderNumberCell(node, col, index);
        else if (col.type == "Decimal")
            return renderDecimalCell(node, col, index);
        else if (col.type == "Check")
            return renderCheckCell(node, col, index);
        else if (col.type == "DropDown")
            return renderDropDownCell(node, col, index);
        else if (col.type == "AsyncDropDown")
            return renderAsyncDropDownCell(node, col, index)
        else if (col.type == "Date")
            return renderDateCell(node, col, index)
    }

    const renderTextCell = (node, col, index) => {
        const indent = (node.key).toString().split('-').length;
        if (col.key == 'name_en' && !!node.data.empty) {
            if (!!canAddInline)
                return <a href='#' onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else {
            return (
                node.key == currentKey ?
                    <input type="text" class={`form-control text-editor text-indent-${indent}`} style={{ width: `${100 - (10 * indent)}%!important` }}
                        defaultValue={node.data[col.key]}
                        onChange={(e) => handleEditorChange(e.target.value, col.key)}
                        autoFocus={!!col.autoFocus}
                        onKeyDown={(e) => e.stopPropagation()}
                        required={!!col.required} />
                    :
                    <span>{node.data[col.key]}</span>);
        }

    }

    const renderNumberCell = (node, col, index) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <input type="number" min="0" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node.data[col.key]}
                    onChange={(e) => handleEditorChange(e.target.value, col.key)}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required ={!!col.required} />
                :
                <span>{node.data[col.key]}</span>);
    }

    const renderDecimalCell = (node, col, index) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <input type="number" min="0" step=".01" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node.data[col.key]}
                    onChange={(e) => handleEditorChange(e.target.value, col.key)}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required} />
                :
                <span>{node.data[col.key]}</span>);
    }

    const renderDateCell = (node, col, index) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <Calendar type="number" min="0" step=".01" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={toDate(node.data[col.key], 'D') }
                    onChange={(e) => {
                        let value = !!e.value ? `${e.value.getFullYear()}-${(e.value.getMonth()+1).toString().padStart(2, '0')}-${e.value.getDate().toString().padStart(2, '0')}` : null;
                        handleEditorChange(value, col.key)
                    }}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required}
                    value={toDate(editingRow[col.key], 'D') } />
                :
                <span>{!!node.data[col.key] ? `${new Date(node.data[col.key]).getFullYear()}-${(new Date(node.data[col.key]).getMonth()+1).toString().padStart(2, '0')}-${new Date(node.data[col.key]).getDate().toString().padStart(2, '0')}` : null}</span>);
    }

    const renderCheckCell = (node, col, index) => {
        return (
            node.key == currentKey ?
                <div>
                    <input type="checkbox" checked={editingRow[col.key] == 1 ? true : false}
                        class="form-check-input" data-kt-check={node.data[col.key]}
                        data-kt-check-target=".widget-10-check"
                        onChange={(e) => {
                            handleEditorChange(e.target.checked ? 1 : 0, col.key)
                        }
                        }
                    />
                </div>
                :
                <div>
                    <input type="checkbox" defaultChecked={false} checked={node.data[col.key]}
                        class="form-check-input" data-kt-check={node.data[col.key]}
                        data-kt-check-target=".widget-10-check" disabled />
                </div>
        )
    }

    const renderDropDownCell = (node, col, index) => {
        const val = col.options.find(x => x.value == node.data[col.key])
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey ?
                <select class={`form-control number-indent-${indent}`}
                    defaultValue={node.data[col.key]}
                    onChange={(e) => handleEditorChange(e.target.value, col.key)}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required}>
                    {options.map((option) => (
                        <option value={option.value}>{option.name}</option>
                    ))
                    }
                </select>
                :
                <span>{!!val ? val.name : ''}</span>);
    }

    const renderAsyncDropDownCell = (node, col, index)=>{//key, autoFocus, options, editable, required, firstCell) => {
        const indent = (node.key).toString().split('-').length;
        if ((index==0) && !!node.data.empty) {
            return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else {
            return  node.key == currentKey && !!col.editable ?
                <AsyncSelectComponent
                    field={col.key}
                    dir={dir}
                    searchUrl={col.searchUrl}
                    currentObject={editingRow[col.key]}
                    onBasicChange={(field, val) => {
                        handleEditorChange(val, col.key);
                        if(!!col.onChangeValue){
                            col.onChangeValue(editingRow, col.key, val, (row)=>{
                                setEditingRow({...row});
                            });
                        }
                    }} />
                :
                <span>{!!node.data[col.key] ? getRowName(node.data[col.key], dir) : ''}</span>;
        }
    }

    const clearAddRow = (nodesData) =>{
        for (let index = 0; index < nodesData.length; index++) {
            if(!!nodesData[index].children)
                nodesData[index].children = clearAddRow(nodesData[index].children);
            if(!!nodesData[index].data.empty && nodesData[index].data.empty == 'Y'){
                nodesData.splice(index, 1);
                break;
            }
        }
        return nodesData;
    }

    const refreshTree = () => {
        try {
            const response = axios.get(urlList).then(response => {
                let result = response.data;
                if(!!!canAddInline)
                    result = clearAddRow(result);
                console.log(result);
                setNodes(result);
                setExpandedKeys(getExpandedKeys(result));
            });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    // Generate the expandedKeys object to expand all nodes by default
    const getExpandedKeys = (nodes) => {
        let expandedKeys = {};
        const expandAll = (nodes) => {
        nodes.forEach((node) => {
            expandedKeys[node.key] = true; // Mark this node as expanded
            if (node.children) {
            expandAll(node.children); // Recursively expand children
            }
        });
        };
        expandAll(nodes);
        return expandedKeys;
    };


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

    const addInline = (nodeKey, type, parentKeyName) => {
        let node = findNodeByKey(nodes, nodeKey);
        nodeKey = (nodeKey).toString();
        let seg = nodeKey.split('-');
        let parentKey = seg.length == 1 ? null : seg[0];
        for (let index = 1; index < seg.length - 1; index++) {
            parentKey = parentKey + '-' + seg[index];
        }
        node.data.empty = null;
        if(!!defaultValue)
        for (var key in defaultValue) {
            node.data[key] = defaultValue[key];
        }
        let newNode = {
            key: !!!parentKey ? Number(seg[0]) + 1 : parentKey + '-' + (Number(seg[seg.length - 1]) + 1),
            data: { type: type, parentKey: parentKeyName, empty: 'Y' }
        }
        if (!!!parentKey)
            nodes.push(newNode);
        else {
            let parentNode = findNodeByKey(nodes, parentKey);
            parentNode.children.push(newNode);
        }
        setCurrentKey(nodeKey);
        setNodes([...nodes]);
        setEditingRow({ ...node.data });
    }

    const actionTemplate = (node) => {
        const data = node.data;
        return (
            (!!!node.data.empty && node.data.empty != 'Y') ?
                <div className="flex flex-wrap gap-2">

                    {((currentKey == '-1') || (currentKey != '-1' && node.key == currentKey)) &&
                     (!!!canEditRow || canEditRow(node.data)) ?
                        <a href="#" onClick={() => {
                            if (currentKey == '-1')
                                editRow(data, node.key)
                            else {
                                triggerSubmit();
                            }
                        }
                        } title="Edit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                            <i class={(currentKey != '-1' && node.key == currentKey) ? "ki-outline ki-check fs-2" : "ki-outline ki-pencil fs-2"}></i>
                        </a> : <></>}
                    {currentKey != '-1' ? <a href="#" onClick={(e) => cancelEdit(currentKey)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </a> : null}
                    {!!canDelete ? <a href="#" onClick={() => openDeleteModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-outline ki-trash fs-2"></i>
                    </a> : <></>}
                    
                </div> : <></>
        );
    };


    
    return (
        <div class="card mb-5 mb-xl-8">
            <SweetAlert2 />
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">{translations[title]}</span>
                </h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        {!!addUrl ?
                            <a href="#" class="btn btn-primary"
                                onClick={() => openAdd()}>{translations.Add}</a>
                            : <></>}
                        <DeleteModal
                            visible={isDeleteModalVisible}
                            onClose={handleClose}
                            onDelete={handleDelete}
                            url={url}
                            row={currentNode}
                            translations={translations}
                        />
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="treeFormLocal" ref={formRef} noValidate validated={true} class="needs-validation">
                <TreeTable value={nodes} tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"} expandedKeys={expandedKeys} onToggle={(e) => setExpandedKeys(e.value)}>
                    {cols.map((col, index) =>
                        <Column
                            header={!!col.title ? translations[col.title] : translations[col.key]} 
                            body={(node) => (
                                renderCell(node, col, index)
                            )} expander={index == 0 && !!expander} />
                    )}
                    <Column body={(node) => (actionTemplate(node))} />
                </TreeTable>
                <button id="btnSubmit" onClick={triggerSubmit} style={{ display: "none" }}></button>
                </form>
            </div>

        </div>
    );
};
export default TreeTableComponent;

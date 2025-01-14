import React, { useState, useEffect, useRef } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import DeleteModalLocal from './DeleteModalLocal';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import AsyncSelectComponent from './AsyncSelectComponent';
import { formatDecimal, getName, getRowName } from '../lang/Utils';

const animatedComponents = makeAnimated();
const TreeTableComponentLocal = ({ translations, dir, header, cols, 
                                    actions, type, title, currentNodes, defaultValue, onUpdate, 
                                    onDelete, addNewRow}) => {

    const formRef = useRef(null);

    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState('');
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState('-1');
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});
    const [validated, setValidated] = useState(false);

    useEffect(() => {
        let t = currentNodes.map((item, index) => {
            return { key: index  + "", data: item }
        });
        let index = currentNodes.length > 0 ? currentNodes.length  + "" : "0";
        if(!!addNewRow){
            t.push({
                key : index,
                data : {
                    "id": null,
                    "type": type,
                    "parentKey": null,
                    "parentKey1": null,
                    "type1": null,
                    "empty": "Y"
                }
            });
        }
        setNodes([...t]);
    }, [currentNodes]);

    const editRow = (data, key) => {
        setCurrentKey(key);
        setEditingRow({ ...data });
    }

    const openDeleteModel = (data) => {
        setUrl(JSON.parse(rootElement.getAttribute(`${data.type}-url`)));
        setCurrentNode(data);
        setIsDeleteModalVisible(true);
    }

    const triggerSubmit = () => {
        handleSubmit(formRef.current);
    };

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
        editedNode.key = currentKey;
        let parentNode = getParentNode(editedNode.key)
        if (!!parentNode){
            editedNode.data[editedNode.data.parentKey] = parentNode.data.id;
            for (let index = 0; index < parentNode.children.length; index++) {
                if(parentNode.children[index].key == editedNode.key){
                    parentNode.children[index] = editedNode;
                    continue;
                }
            }
        }
        else{
            for (let index = 0; index < nodes.length; index++) {
                if(nodes.key == editedNode.key){
                    nodes[index] = editedNode;
                    continue;
                }
            }
        }
        
        const response = onUpdate(nodes.slice(0, nodes.length-1).map(x=> {
            return { id : !!x.data.id ? x.data.id : x.key,
                     ...x.data
                }
          }));
        if (response.message != "Done") {
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
        setNodes([...nodes]);
    }

    const handleDelete = (row) => {
        const response = onDelete(row);
        if (response.message  != "Done") {
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
                    if (!!parentNode)
                        parentNode.children.splice(index, 1);
                    else
                        nodes.splice(index, 1);
                    break;
                }
            }
        }
        setCurrentKey('-1');
        setEditingRow({});
        onUpdate(currentNodes);
    }

    const renderCell = (node, col, index) => {//key, autoFocus, options, type, editable, required, index, customCell) => {
        if(!!col.customCell && !!!node.data.empty){
            return col.customCell(node.data, col.key, node.key == currentKey, col.editable, handleEditorChange);
        }
        let firstCell = index == "0" ? true: false; 
        if (col.type == "Text")
            return renderTextCell(node, col, firstCell);//key, autoFocus, editable, required, firstCell);
        else if (col.type == "Number")
            return renderNumberCell(node, col, firstCell);//key, autoFocus, editable, required, firstCell);
        else if (col.type == "Decimal")
            return renderDecimalCell(node, col, firstCell);//key, autoFocus, editable, required, firstCell);
        else if (col.type == "Check")
            return renderCheckCell(node, col, firstCell);//key, autoFocus, editable, required, firstCell);
        else if (col.type == "DropDown"){
            return renderDropDownCell(node, col, firstCell);//key, autoFocus, options, editable, required, firstCell);
            
        }
        else if (col.type == "AsyncDropDown")
            return renderAsyncDropDownCell(node, col, firstCell);//key, autoFocus, options, editable, required, firstCell);
        else if (col.type == "MultiDropDown")
            return renderMultiDropDownCell(node, col, firstCell);//key, autoFocus, options, editable, required, firstCell);
    }

    const renderTextCell = (node, col, firstCell) =>{//key, autoFocus, editable, required, firstCell) => {
        const indent = (node.key).toString().split('-').length;
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else {
            return (
                node.key == currentKey && !!col.editable ?
                    <input type="text" class={`form-control form-control-solid custom-height text-indent-${indent}`} style={{ width: `${100 - (10 * indent)}%!important` }}
                        defaultValue={node.data[col.key]}
                        onChange={(e) => handleEditorChange(e.target.value, col.key)}
                        autoFocus={!!col.autoFocus}
                        onKeyDown={(e) => e.stopPropagation()}
                        required={!!col.required}
                        value={editingRow[col.key]} />
                    :
                    node.key == currentKey && !!!col.editable ? 
                        <apan>{editingRow[col.key]}</apan>:
                        <span>{node.data[col.key]}</span>);
        }

    }

    const renderNumberCell = (node, col, firstCell) =>{//key, autoFocus, editable, required, firstCell) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey && !!col.editable ?
                <input type="number" min="0" class={`form-control form-control-solid custom-height number-indent-${indent}`}
                    defaultValue={node.data[col.key]}
                    onChange={(e) => handleEditorChange(e.target.value, col.key)}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required} />
                :
                <span>{node.data[col.key]}</span>);
    }

    const renderDecimalCell = (node, col, firstCell)=>{//key, autoFocus, editable, required) => {
        const indent = (node.key).toString().split('-').length;
        return (
            node.key == currentKey && !!col.editable?
                <input type="number" min="0" step=".01" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node.data[col.key]? formatDecimal(node.data[col.key]) : ''}
                    onChange={(e) => {
                        handleEditorChange(e.target.value, col.key);
                        if(!!col.onChangeValue){
                            col.onChangeValue(editingRow, col.key, e.target.value, (row)=>{
                                setEditingRow({...row});
                            });
                        }
                    }}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required}
                    value={editingRow[col.key] ?? ''} />
                :
                <span>{node.data[col.key]}</span>);
    }

    const renderCheckCell = (node, col, firstCell)=> {//key, autoFocus, editable) => {
        return (
            node.key == currentKey && !!col.editable ?
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

    const renderDropDownCell = (node, col, firstCell)=>{//key, autoFocus, options, editable, required, firstCell) => {
        const val = col.options.find(x => x.value == node.data[col.key])
        const indent = (node.key).toString().split('-').length;
        if (!!firstCell && !!node.data.empty) {
            return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else {
            return  node.key == currentKey && !!col.editable ?
                //  <select class={`form-control number-indent-${indent}`}
                //     defaultValue={node.data[col.key]}
                //     onChange={(e) => handleEditorChange(e.target.value, col.key)}
                //     autoFocus={!!col.autoFocus}
                //     onKeyDown={(e) => e.stopPropagation()}
                //     style={{ width: '100%' }}
                //     required={!!col.required}>
                //     {col.options.map((option) => (
                //         <option value={option.value}>{option.label}</option>
                //     ))
                //     }
                //     </select>
                <Select
                    id="col.key"
                    isMulti={false}
                    options={col.options}
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    defaultValue={val}
                    onChange={(val) => handleEditorChange(val.value, col.key)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
                :
                <span>{!!val ? val.label : ''}</span>;
        }
    }

    const get = (row, key) =>{
        const keys = key.split('.'); // Split the key on '.'
        let value = row;
        
        for (const k of keys) {
          // Check if the key exists and its value is not null
          if (value[k] === null || value[k] === undefined) {
            value = null; // Set value to null if any key in the chain is null/undefined
            break;
          }
          value = value[k];
        }
        return value;
    }

    const renderAsyncDropDownCell = (node, col, firstCell)=>{//key, autoFocus, options, editable, required, firstCell) => {
        const indent = (node.key).toString().split('-').length;
        let url = !!col.relatedTo ? `${col.searchUrl}?${col.relatedTo.key}=${
                !!editingRow && !!get(editingRow, col.relatedTo.relatedKey) ? get(editingRow, col.relatedTo.relatedKey) : -1}`
                :col.searchUrl;
        if (!!firstCell && !!node.data.empty) {
            return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else {
            return  node.key == currentKey && !!col.editable ?
                <AsyncSelectComponent
                    field={col.key}
                    dir={dir}
                    searchUrl={url}
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

    const renderMultiDropDownCell = (node, col, firstCell)=>{//key, autoFocus, options, editable) => {
        if(!!!node.data[col.key]) node.data[col.key] =[];
        const val = col.options.filter(x => node.data[col.key].includes(x.value))
        return (
            node.key == currentKey && !!col.editable?
                <Select
                    id={col.key}
                    isMulti={true}
                    options={col.options}
                    closeMenuOnSelect={false}
                    components={animatedComponents}
                    defaultValue={val}
                    onChange={val => handleEditorChange(val.map(x=> { return  x.value }), col.key)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
                :
                    <div class="product__item__text">
                        <ul>
                            {val.map((v) => (
                                <li>{v.label}</li>
                            ))}
                        </ul>
                    </div>
            );
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

    const actionTemplate = (node, actions) => {
        const data = node.data;
        return (
            (!!!node.data.empty && node.data.empty != 'Y' && !!onUpdate) ?
                <div className="flex flex-wrap gap-2">

                    {((currentKey == '-1') || (currentKey != '-1' && node.key == currentKey)) ?
                        <a href="javascript:void(0);" onClick={(e) => {
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
                    {!!onDelete ? 
                    <a href="#" onClick={() => openDeleteModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-outline ki-trash fs-2"></i>
                    </a> :<></>}
                    {actions.map(action => 
                        <a href="javascript:void(0);" onClick={() => action.execute(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                            <i class={`ki-outline ${action.icon} fs-2`}></i>
                        </a>
                     )}
                </div> : <></>
        );
    };
    return (
        <div class="card mb-5 mb-xl-8">
            <SweetAlert2 />
            {!!header ?
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">{title}</span>
                </h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <DeleteModalLocal
                            visible={isDeleteModalVisible}
                            onClose={handleClose}
                            onDelete={handleDelete}
                            row={currentNode}
                            translations={translations}
                        />
                    </div>
                </div>
            </div>
            :<></>}
            <div class="card-body">
                <form id="treeFormLocal" ref={formRef} noValidate validated={true} class="needs-validation">
                    <TreeTable value={nodes}  className={"custom-tree-table"}>
                        {cols.map((col, index) =>
                            <Column 
                            style={{ width: !!!col.width ? '10%' : col.width }} 
                            header={!!col.title ? translations[col.title] : translations[col.key]} 
                            body={(node) => (
                                renderCell(node, col, index))} 
                            />
                        )}
                        <Column body={(node) => (actionTemplate(node, actions))} />
                    </TreeTable>
                    <button id="btnSubmit1" onClick={triggerSubmit} style={{ display: "none" }}></button>
                </form>
            </div>

        </div>
    );
};
export default TreeTableComponentLocal;

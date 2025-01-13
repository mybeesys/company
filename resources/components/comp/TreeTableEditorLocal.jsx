import React, { useState, useEffect, useRef } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import DeleteModalLocal from './DeleteModalLocal';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import AsyncSelectComponent from './AsyncSelectComponent';
import { formatDecimal, getName, getRowName } from '../lang/Utils';
import MultiDropDown from './MultiDropDown';

const animatedComponents = makeAnimated();
const TreeTableEditorLocal = ({ translations, dir, header, cols, 
                                    actions, type, title, currentNodes, defaultValue, onUpdate, onDelete, addNewRow, rowTitle}) => {

    const formRef = useRef(null);

    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState('');
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

    const openDeleteModel = (data) => {
        setUrl(JSON.parse(rootElement.getAttribute(`${data.type}-url`)));
        setCurrentNode(data);
        setIsDeleteModalVisible(true);
    }

    const triggerSubmit = () => {
        handleSubmit(formRef.current);
    };

    const handleDelete = (row) => {
        const response = onDelete(row);
        if (response.message  != "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                text: translations[response.message],
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

    const updateBasicData = (nodes) => {
        const response = onUpdate(nodes.filter(x => x.data.empty =="Y").length > 0 ? nodes.slice(0, nodes.length-1).map(x=> {
            return { ...x.data, id : !!x.data.id ? x.data.id : x.key
                }
        }) : nodes.map(x=> {
            return { ...x.data,  id : !!x.data.id ? x.data.id : x.key
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
    }

    const handleEditorChange = (value, key, rowKey, onChangeValue) => {
        nodes[rowKey].data[key] = value;
        setNodes([...nodes]);
        if(!!onChangeValue){
            onChangeValue(nodes, key, value, rowKey, (nodes, updateOriginalData)=>{
                setNodes([...nodes]);
                if(!!updateOriginalData)
                    updateBasicData(nodes);
            });
        }
        updateBasicData(nodes);
    }

    const renderCell = (node, col, index) => {//key, autoFocus, options, type, editable, required, index, customCell) => {
        if(!!col.customCell && !!!node.data.empty){
            return col.customCell(node.data, col.key, col.editable, handleEditorChange);
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
                 !!col.editable ?
                    <input type="text" class={`form-control form-control-solid custom-height text-indent-${indent}`} style={{ width: `${100 - (10 * indent)}%!important` }}
                        value={node.data[col.key]}
                        onChange={(e) => handleEditorChange(e.target.value, col.key, node.key, col.onChangeValue)}
                        autoFocus={!!col.autoFocus}
                        onKeyDown={(e) => e.stopPropagation()}
                        required={!!col.required}
                        />
                    :
                    <apan>{node.data[col.key]}</apan>);
        }

    }

    const renderNumberCell = (node, col, firstCell) =>{//key, autoFocus, editable, required, firstCell) => {
        const indent = (node.key).toString().split('-').length;
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else{
            return (
            !!col.editable ?
                <input type="number" min="0" class={`form-control form-control-solid custom-height number-indent-${indent}`}
                    value={node.data[col.key]}
                    onChange={(e) => handleEditorChange(e.target.value, col.key, node.key, col.onChangeValue)}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required} />
                :
                <span>{node.data[col.key]}</span>
            );
        }
    }

    const renderDecimalCell = (node, col, firstCell)=>{//key, autoFocus, editable, required) => {
        const indent = (node.key).toString().split('-').length;
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else{
            return (
            !!col.editable?
                <input type="number" min="0" step=".01" class={`form-control form-control-solid custom-height number-indent-${indent}`}
                    value={node.data[col.key] ?? ''}
                    onChange={(e) => {
                        handleEditorChange(e.target.value, col.key, node.key, col.onChangeValue);
                    }}
                    autoFocus={!!col.autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    style={{ width: '100%' }}
                    required={!!col.required}/>
                :
                <span>{node.data[col.key]}</span>
            );
        }
    }

    const renderCheckCell = (node, col, firstCell)=> {//key, autoFocus, editable) => {
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else{
            return (
            !!col.editable ?
                <div>
                    <input type="checkbox" checked={node.data[col.key] == 1 ? true : false}
                        class="form-check-input" data-kt-check={node.data[col.key]}
                        data-kt-check-target=".widget-10-check"
                        onChange={(e) => handleEditorChange(e.target.checked ? 1 : 0, col.key, node.key, col.onChangeValue)}
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
    }

    const renderDropDownCell = (node, col, firstCell)=>{//key, autoFocus, options, editable, required, firstCell) => {
        const val = col.options.find(x => x.value == node.data[col.key])
        const indent = (node.key).toString().split('-').length;
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else{
            return (
            !!col.editable ?
                <Select
                    id="col.key"
                    isMulti={false}
                    options={col.options}
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    defaultValue={val}
                    value={val}
                    onChange={(val) => handleEditorChange(val.value, col.key, node.key, col.onChangeValue)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
                :
                <span>{!!val ? val.label : ''}</span>
            );
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
                get(node.data, col.relatedTo.relatedKey) ? get(node.data, col.relatedTo.relatedKey) : -1}`
                :col.searchUrl;
        if (!!node.data.empty) {
            if(!!firstCell)
                return <a href="javascript:void(0);" onClick={e => addInline(node.key, node.data.type, node.data.parentKey)}>{`${translations.Add} ${translations[node.data.type]}`}</a>
        }
        else{
            return  !!col.editable ?
                <AsyncSelectComponent
                    field={col.key}
                    dir={dir}
                    searchUrl={url}
                    isMulti={col.isMulti}
                    currentObject={node.data[col.key]}
                    onBasicChange={(field, val) => {
                        handleEditorChange(val, col.key, node.key, col.onChangeValue);
                    }} />
                :
                <span>{!!node.data[col.key] ? getRowName(node.data[col.key], dir) : ''}</span>;
        }
    }

    const renderMultiDropDownCell = (node, col, firstCell)=>{//key, autoFocus, options, editable) => {
        if (!node.data[col.key]) node.data[col.key] = [];

        const val = col.options.filter((x) => node.data[col.key].includes(x.value));

        if (node.data.empty) {
            if (firstCell)
                return (
                    <a
                        href="javascript:void(0);"
                        onClick={() => addInline(node.key, node.data.type, node.data.parentKey)}
                    >
                        {`${translations.Add} ${translations[node.data.type]}`}
                    </a>
                );
        } else {
            return col.editable ? (
                <MultiDropDown
                    key={col.key}
                    options={col.options}
                    value={val}
                    onChange={(newVal) => {
                        const updatedValues = newVal.map((item) => item.value); // Extract values
                        handleEditorChange(updatedValues, col.key, node.key, col.onChangeValue);
                    }}
                />
            ) : (
                <div className="product__item__text">
                    <ul>
                        {val.map((v) => (
                            <li key={v.value}>{v.label}</li>
                        ))}
                    </ul>
                </div>
            );
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
        let mm = Math.max(...nodes.map(item => item.id));
        newNode.data["id"] =  !!!mm ? 1 : mm + 1; 
        if (!!!parentKey)
            nodes.push(newNode);
        else {
            let parentNode = findNodeByKey(nodes, parentKey);
            parentNode.children.push(newNode);
        }
        setNodes([...nodes]);
    }

    const actionTemplate = (node, actions) => {
        const data = node.data;
        return (
            (!!!node.data.empty && node.data.empty != 'Y') ?
                <div className="flex flex-wrap gap-2">
                    {!!onDelete ? 
                    <a href="javascript:void(0)" onClick={() => openDeleteModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-outline ki-trash fs-2"></i>
                    </a> :<></>}
                    {actions.map(action => 
                        <a href="javascript:void(0);" onClick={() => action.execute(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                            {action.customRender ? action.customRender(node.data) : <i class={`ki-outline ${action.icon} fs-2`}></i>}
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
            </div>
            :<></>}
            <div class="card-toolbar">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <DeleteModalLocal
                        visible={isDeleteModalVisible}
                        onClose={handleClose}
                        onDelete={handleDelete}
                        row={currentNode}
                        rowTitle = {rowTitle}
                        translations={translations}
                    />
                </div>
            </div>
            <div class="card-body">
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
            </div>

        </div>
    );
};
export default TreeTableEditorLocal;

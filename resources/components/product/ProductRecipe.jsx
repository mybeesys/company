import React , { useState, useCallback  } from 'react';
import axios from 'axios';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column'; 

const ProductRecipe = ({translations , product, productRecipe, ingredientTree, parentHandleRecipe ,dir}) => {
  

  React.useEffect(() => {
  }, []);
      
const cancelEdit = (key) => {
    if (!!!editingRow.id || editingRow.id == 0) {
        let parentNode = getParentNode(key);
        let currentNodes = !!parentNode ? parentNode.children : nodes;
        for (let index = 0; index < currentNodes.length; index++) {
            const node = currentNodes[index];
            if (node.id == key) {
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
}

const handleEditorChange = (value, key) => {
    editingRow[key] = value;
    setEditingRow({ ...editingRow })
}

const editRow = (data, key) => {
    setCurrentKey(key);
    setEditingRow({ ...data });
}

const deleteAddObject = ( id) =>
{
    onActiveDeactiveMatrix(id);
}

const handleSubmit = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    onChange(currentKey, editingRow);
    setCurrentKey('-1');
}

const actionTemplate = (node) => {
    return (
        <>
        {((currentKey == '-1') || (currentKey != '-1' && node.id == currentKey)) ?
         
                <i class={(currentKey != '-1' && node.id == currentKey) ? "ki-outline ki-check fs-2" : "ki-outline ki-pencil fs-2"}
                onClick={() => {
                    if (currentKey == '-1')
                        editRow(node, node.id)
                    else {
                        let btnSubmit = document.getElementById("btnSubmitinner");
                        btnSubmit.click();
                    }
                }
                }></i>
             : <></>}
            {currentKey != '-1' ? 
            <i class="ki-outline ki-cross fs-2" onClick={(e) => cancelEdit(currentKey)}></i>
                 : null}
           
            <i className={node.deleted == 1?"ki-outline ki-plus fs-2":"ki-outline ki-trash fs-2"}  onClick={() => deleteAddObject(node.id)}></i>

        <button id="btnSubmitinner" onClick={handleSubmit} type="submit" style={{display:"none"}}></button>
    </>
    );
}

const renderDecimalCell = (node, key, autoFocus) => {
    const indent = node.id;
    return (
        node.id == currentKey ?
            <input type="number" min="0" step=".01" class={`form-control text-editor number-indent-${indent}`}
                defaultValue={node[key]}
                onChange={(e) => handleEditorChange(e.target.value, key)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()} 
                style={{ width: '100%' }}
                required/>
            :
            <span style={node.deleted == 1? {color:"#DCDCDC"}: {color:"black"}}>{node[key]}</span>);
}

const renderDropDownCell = (node, key, autoFocus, options) => {
  const val = options.find(x => x.value == node.data[key])
  const indent = (node.key).toString().split('-').length;
  return (
      node.key == currentKey ?
          <select class={`form-control number-indent-${indent}`}
              defaultValue={node.data[key]}
              onChange={(e) => handleEditorChange(e.target.value, key)}
              autoFocus={!!autoFocus}
              onKeyDown={(e) => e.stopPropagation()} 
              style={{ width: '100%' }}
              required>
              {options.map( (option) => (
                  <option value={option.value}>{option.name}</option>
              ))
              }
          </select>
          :
          <span>{!!val ? val.name : ''}</span>);
}

 return (
      <>
            <div class="card-body" dir={dir}>
            <div>
            <DataTable value={productRecipe} tableStyle={{ minWidth: '20rem' }} noValidate validated={true} className={"custom-tree-table"} onSubmit={handleSubmit}>
                <Column field="name_ar" style={{ width: '30%' }}  header={translations.ingredient} body={(node) => (renderDropDownCell(node, dir=="rtl"? 'name_ar':'name_en' , false , ingredientTree))}></Column>
                <Column field="quantity" style={{ width: '30%' }}  header={translations.quantity} body={(node) => (renderDecimalCell(node, 'quantity' , true ))}></Column>
                <Column field="cost" style={{ width: '10%' }}  header={translations.cost} body={(node) => (node.data.quantity * node.data.quantity)}></Column>
                <Column style={{ width: '20%' }}  body={(node) => (actionTemplate(node))}></Column>
            </DataTable>
            </div>
        </div>      
     </>

    );
  };

    
  export default ProductRecipe;
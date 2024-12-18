import React, { useState, useCallback } from 'react';
import axios from 'axios';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

const animatedComponents = makeAnimated();

const ProductRecipe = ({ translations, productRecipe, product, ingredientTree, parentHandleRecipe, handleChange, dir }) => {
  const [currentObject, setcurrentObject] = useState(product);
  const [currentKey, setCurrentKey] = useState('-1');
  const [nodes, setNodes] = useState(productRecipe);
  const [editingRow, setEditingRow] = useState({});
  const [id, setid] = useState(0);

  React.useEffect(() => {
    setNodes(productRecipe);
  }, [productRecipe]);


  const cancelEdit = (node) => {
    let r = [...nodes];
    r = r.filter(function (item) {
      return item.id !== node.id
    });
    setNodes(r);
    setCurrentKey('-1');
    setEditingRow({});
    parentHandleRecipe(r);
  }

  const handleSubmit = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    onCahnge();
    setCurrentKey('-1');
  }

  const onChangeProduct = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({ ...currentObject });
    handleChange(currentObject);
  }

  const onCahnge = () => {
    let editedNode = nodes.find((e) => { return e.id == '-1'; });
    editedNode.newid = editingRow.newid;
    editedNode.quantity = editingRow.quantity;
    editedNode.id = id + 1;
    setid(id + 1);
    setNodes(nodes);
    parentHandleRecipe(nodes);
  }

  const handleEditorChange = (value, key) => {
    editingRow[key] = value;
    setEditingRow({ ...editingRow });
  }

  const actionTemplate = (node) => {
    return (
      <>
        {node.id == currentKey ?
          <i class="ki-outline ki-check fs-2" onClick={handleSubmit}></i>
          : <></>}
        {(node.id == -100) ? "" :
          <>
            <i className={node.deleted == 1 ? "ki-outline ki-plus fs-2" : "ki-outline ki-trash fs-2"} onClick={() => cancelEdit(node)}></i>
          </>
        }
      </>
    );
  }

  const renderDecimalCell = (node, key, autoFocus) => {
    const indent = node.id;
    return (
      node.id == currentKey ?
        <input type="number" min="0" step=".01" class={`form-control form-control-solid custom-height number-indent-${indent}`}
          defaultValue={node[key]}
          onChange={(e) => handleEditorChange(e.target.value, key, node)}
          autoFocus={!!autoFocus}
          onKeyDown={(e) => e.stopPropagation()}
          style={{ width: '100%' }}
          required />
        :
        <span>{node[key]}</span>);
  }

  const renderDropDownCell = (node, key, autoFocus) => {
    if (node.id == -100) {
      return <a href='#' onClick={e => addInline(node)}>{`${translations.Add} ${translations["Ingredient"]}`}</a>
    }
    else {
      const val = ingredientTree.find(x => x.value == node[key]);
      return (
        node.id == currentKey ?
          <Select
            options={ingredientTree}
            defaultValue={node[key]}
            onChange={(e) => handleEditorChange(e.value, key, node)}
            autoFocus={!!autoFocus}
            menuPortalTarget={document.body}
            styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
          />

          :
          <span>{!!val ? val.label : ''}</span>);
    }
  }

  const renderCostCell = (node) => {
    let cost = calculateCost(node.newid, node.quantity);
    return (<span>{cost}</span>)
  }

  const addInline = (node) => {
    node.id = '-1';
    let newNode = {
      id: -100, quantity: null, cost: null, newid: null
    }
    nodes.push(newNode);
    setCurrentKey('-1');
    setNodes([...nodes]);
    setEditingRow(node);
  }

  const calculateCost = (newid, quantity) => {
    if (newid) {
      let cost = ingredientTree.find(e => e.value == newid).cost;
      return cost * quantity;
    }
    return "";
  }

  return (
    <>
      <div class="card-body pt-10" dir={dir}>
        <div>
          <DataTable value={nodes} tableStyle={{ minWidth: '20rem' }} noValidate validated={true} className={"custom-tree-table"} onSubmit={handleSubmit}>
            <Column field="newid" style={{ width: '30%' }} header={translations.Ingredient} body={(node) => (renderDropDownCell(node, 'newid', true, ingredientTree))}></Column>
            <Column field="quantity" style={{ width: '30%' }} header={translations.quantity} body={(node) => (node.id == -100 ? "" : renderDecimalCell(node, 'quantity', false))}></Column>
            <Column field="cost" style={{ width: '10%' }} header={translations.cost} body={(node) => (node.id == -100 ? "" : node.id == currentKey ? renderCostCell(editingRow) : renderCostCell(node))}></Column>
            <Column style={{ width: '20%' }} body={(node) => (actionTemplate(node))}></Column>
          </DataTable>
        </div>
        <div class="row" style={{ paddingtop: '20px' }}>
          <div class="col-6">
            <label for="recipe_yield" class="col-form-label">{translations.recipe_yield}</label>
            <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="recipe_yield" value={!!currentObject.recipe_yield ? currentObject.recipe_yield : ''}
              onChange={(e) => onChangeProduct('recipe_yield', e.target.value)}
              ></input>
          </div>
        </div>
          <div class="d-flex  align-items-center pt-3">
            <label class="fs-6 fw-semibold mb-2 me-3 "
              style={{ width: "150px" }}>{translations.prep_recipe}</label>
            <div class="form-check">
              <input type="checkbox" style={{ border: "1px solid #9f9f9f" }}
                class="form-check-input my-2"
                id="prep_recipe" checked={currentObject.prep_recipe}
                onChange={(e) => onChangeProduct('prep_recipe', e.target.checked)} />
            </div>
          </div>
      </div>
    </>

  );
};


export default ProductRecipe;
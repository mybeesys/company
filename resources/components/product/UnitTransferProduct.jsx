import React , { useState, useCallback, useRef  } from 'react';
import axios from 'axios';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column'; 

const animatedComponents = makeAnimated();

const UnitTransferProduct = ({translations , unitTransfer, unitTree, parentHandle , handleMainUnit , productUnit ,dir}) => {

    const formRef = useRef(null);

    const [currentKey, setCurrentKey] = useState('-1');
    const [nodes, setNodes] = useState(unitTransfer);
    const [editingRow, setEditingRow] = useState({});
    const [id, setid] = useState(-2);
    const [globalId, setGlobalId] = useState(-2);
    const [innerUnits, setUnits] = useState(unitTree);
    const [mainUnit, setMainUnit] = useState(productUnit);
    const [validated, setValidated] = useState(false);

    React.useEffect(() => {
        setNodes(unitTransfer);
  }, [unitTransfer]);

      
const cancelEdit = (node) => {
    let r = [...nodes];
    r =  r.filter(function(item) { 
            return item.id !== node.id
        });
    setNodes(r);
    setCurrentKey('-1');
    setEditingRow({});
    parentHandle(r);
    innerUnits.filter((object) => object.unit2 != editingRow.unit2);
    setUnits(innerUnits);
}

const triggerSubmit = (event) => {
    event.preventDefault();
    event.stopPropagation();  
    handleSubmit(formRef.current);
};

const handleSubmit = async (form) => {
    if (form.checkValidity() === false) {

        setValidated(true);
        form.classList.add('was-validated');
        return;
    }
    onCahnge();
    setCurrentKey('-1');

}

const onCahnge = () =>
{
    let editedNode = nodes.find((e)=>{return  e.id == '-1';});
    editedNode.newid = editingRow.newid;
    editedNode.primary = editingRow.primary;
    editedNode.unit1 = editingRow.unit1;
    editedNode.unit2 = editingRow.unit2;
    editedNode.transfer = editingRow.transfer;

    let idd = globalId-1;
    editedNode.id = idd;
   
    innerUnits.push({label: editingRow.unit1 , value: globalId-1})
    setUnits(innerUnits);

    setGlobalId(idd);
    setid(idd);
    setNodes(nodes);
    parentHandle(nodes);
}

const handleEditorChange = (value, key) =>{
    editingRow[key] = value;
    setEditingRow({...editingRow});
}

const actionTemplate = (node) => {
    return (
    <>
      { node.id == currentKey ?
             <i class="ki-outline ki-check fs-2" onClick={ () =>{
                 let btnSubmit = document.getElementById("btnSubmit");
                btnSubmit.click();}}
                ></i>
          : <></>}
        { (node.id == -100)? "":
                <>
             <i className={node.deleted == 1?"ki-outline ki-plus fs-2":"ki-outline ki-trash fs-2"}  onClick={() => cancelEdit(node)}></i>
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
                onChange={(e) => handleEditorChange(e.target.value, key , node)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()} 
                style={{ width: '100%' }}
                required/>
            :
            <span>{node[key]}</span>);
}

const renderDropDownCell = (node, key, autoFocus) => {

    return (
      node.id == currentKey ?
      <Select
      options={innerUnits}
      defaultValue={node[key]} 
      onChange={(e) => handleEditorChange(e.value, key , node)}
      autoFocus={!!autoFocus}  
      menuPortalTarget={document.body} 
      styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
      />
          :
          <span>{!!innerUnits.find(x => x.value == node[key]) ? innerUnits.find(x => x.value == node[key]).label : ''}</span>);
}


const renderTextCell = (node, key, autoFocus) =>{
  if (node.id == -100) {
    return <a href='#' onClick={e => addInline(node)}>{`${translations.Add}`}</a>
  }
  else
    return (
        node.id == currentKey ?  
        <input type="text" class='form-control form-control-solid custom-height'
        defaultValue={node[key]}
        onChange={(e) => handleEditorChange(e.target.value, key)}
        autoFocus = {!!autoFocus}
        onKeyDown={(e) => e.stopPropagation()} required />
        : 
        <span>{node[key]}</span>);
}

const renderCheckCell = (node, key, autoFocus) => {
  return (
      node.id == currentKey ?  
      <div>
          <input type="checkbox" defaultChecked={false}  checked={node[key]} 
              class="form-check-input" data-kt-check={node[key]} 
              data-kt-check-target=".widget-10-check" 
              onChange={(e) => handleEditorChange(e.target.checked, key)}
              />
      </div>
      :
      <div>
          <input type="checkbox" defaultChecked={false}  checked={node[key]}
              class="form-check-input" data-kt-check={node[key]} 
              data-kt-check-target=".widget-10-check" disabled/>
      </div>
  )    
}

const addInline = (node) => {
    node.id ='-1';
    let newNode = {
        id: -100, quantity: null, cost: null , newid :null
    }
    nodes.push(newNode);
    setCurrentKey('-1');
    setNodes([...nodes]);
    setEditingRow(node);
}

const handleChange =(value) =>
{
    let main = {...mainUnit}
    main.unit1 = value;
    setMainUnit(main);
    handleMainUnit(main);

    let found = innerUnits.find((object) => object.value == 0 );
    
    let newUnits = [...innerUnits]
    if (found)
         newUnits = innerUnits.map((object) => object.value == 0 ? {label : value , value : 0}  :  {label : object.label , value : object.value});
    else 
         newUnits.push({label: value , value: 0});

    setUnits(newUnits);
}

 return (
      <>
            <div class="card-body"  dir={dir}>
            <div class="form-group" style={{marginBottom:"14px"}}>
            <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.Unit}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={!!mainUnit ? mainUnit.unit1 : ''}
                onChange={(e) => handleChange(e.target.value)} required></input>
            </div>
            </div>
            </div>
            <div>
            <form  id="treeForm" ref={formRef} noValidate validated={true} class="needs-validation">
            <DataTable value={nodes} tableStyle={{ minWidth: '20rem' }} noValidate validated={true} className={"custom-tree-table"}>
                  <Column field="unit2" style={{ width: '20%' }}  header={translations.Unit} body={(node) => (node.id==-100 ? "" : renderDropDownCell(node, 'unit2' , false))}></Column>
                  <Column field="transfer" style={{ width: '20%' }}  header={translations.transfer} body={(node) => (node.id==-100 ? "" :renderDecimalCell(node, 'transfer' , false ))}></Column>
                  <Column field="unit1" style={{ width: '20%' }}  header={translations.newUnit} body={(node) => (renderTextCell(node, 'unit1' , false))}></Column>
    
                <Column field="primiry" style={{ width: '20%' }}  header={translations.primary} body={(node) => (node.id==-100 ? "" : renderCheckCell(node, 'primary' , false))}></Column>
                <Column style={{ width: '20%' }}  body={(node) => (actionTemplate(node))}></Column>
            </DataTable>
            </form>
            <button id="btnSubmit" onClick={triggerSubmit} style={{display:"none"}}/>
          
            </div>
        </div>  
     </>

    );
  };

    
  export default UnitTransferProduct;
import React , { useState, useCallback  } from 'react';
import axios from 'axios';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column'; 
import { Button } from 'primereact/button';
import { Dropdown, Form } from 'react-bootstrap';
import ConfirmationModal from './ConfirmationModal';
import SweetAlert2 from 'react-sweetalert2';

const ProductAttribute = ({translations, onChange, onActiveDeactiveMatrix, onGenerate, product, productMatrix, AttributesTree, parentHandlematrix}) => {
    const rootElement = document.getElementById('product-root');
    let dir = rootElement.getAttribute('dir');
    const [Attributes1, setAttributes1] = useState([]); 
    const [Attributes2, setAttributes2] = useState([]); 
    const [AttributeClass1, setAttributeClass1] = useState(-1); 
    const [AttributeClass2, setAttributeClass2] = useState(-1); 
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState('-1');
    const [disableButton , setdisableButton] = useState(true);
    const [disableAttributeClass2 , setdisableAttributeClass2] = useState(false);
    setdisableAttributeClass2
    const [showAlert, setShowAlert] = useState(false);
    const [autoObject, setAutoObject] = useState({
        'price': false ,
        'SKU': false ,
        'barcode' : false,
        'starting' :0 ,
        'startingCheck' :false
    });
    const [showConfirmation, setshowConfirmation] =useState(false);

    const onCloseConfirm =(event)=>
    {
        event.preventDefault();
        setshowConfirmation(false);
    }

    const onConfirm =(evt) =>
    {
        evt.preventDefault();
        var newMstrix = [];
        var id = 0;
        if(AttributeClass2 != -1)
       {
        var Children1 = findChildrenById(AttributeClass1);
        var Children2 = findChildrenById(AttributeClass2);
        Children1.forEach(element1 => {
            Children2.forEach(element2 => {
                let newObject = {};
                if (element1.data.empty != "Y"  && element2.data.empty != "Y")
                {
                newObject.name_ar =product.name_ar+" " +element1.data.name_ar +" " + element2.data.name_ar;
                newObject.name_en =product.name_en+" " +element1.data.name_en +" " + element2.data.name_en;
                newObject.attribute1 = {};
                newObject.attribute2 = {};
                newObject.attribute1.name_ar = element1.data.name_ar;
                newObject.attribute1.name_en = element1.data.name_en;
                newObject.attribute1.parent_id = AttributeClass1;
                newObject.attribute2.name_ar = element2.data.name_ar;
                newObject.attribute2.name_en = element2.data.name_en;
                newObject.attribute2.parent_id = AttributeClass2;
                newObject.attribute1.id = element1.data.id;
                newObject.attribute2.id = element2.data.id;
                newObject.price = autoObject.price? product.price : 0;
                newObject.barcode = autoObject.barcode? product.barcode : '';
                newObject.SKU = autoObject.SKU? product.SKU : '';
                newObject.starting = autoObject.startingCheck? autoObject.starting : 0;
                newObject.id= id+1;
                id = id+1;
                newMstrix.push(newObject);
                }
            });
        });
       }
       else
       {
        var Children1 = findChildrenById(AttributeClass1);
        Children1.forEach(element1 => {
                let newObject = {};
                if (element1.data.empty != "Y")
                {
                newObject.name_ar =product.name_ar+" " +element1.data.name_ar ;
                newObject.name_en =product.name_en+" " +element1.data.name_en ;
                newObject.attribute1 = {};
                newObject.attribute2 = {};
                newObject.attribute1.name_ar = element1.data.name_ar;
                newObject.attribute1.name_en = element1.data.name_en;
                newObject.attribute1.parent_id = AttributeClass1;
                newObject.attribute1.id = element1.data.id;
                newObject.price = autoObject.price? product.price : 0;
                newObject.barcode = autoObject.barcode? product.barcode : '';
                newObject.SKU = autoObject.SKU? product.SKU : '';
                newObject.starting = autoObject.startingCheck? autoObject.starting : 0;
                newObject.id= id+1;
                id = id+1;
                newMstrix.push(newObject);
                }
            });
    }
    
        onGenerate(newMstrix);
        setshowConfirmation(false);
    }

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

   const handleChange = async (key , value) =>
    {
      if(key =="AttributeClass1")
      { 
         setAttributeClass1(value);
         setAttributes1(findChildrenById(value));
      }
      else
       { 
        setAttributeClass2(value);
        setAttributes2(findChildrenById(value));
       }
      if (key =="AttributeClass1" && value != -1)
      {  
        setdisableButton(false);
        setdisableAttributeClass2(false);
      }
      else if(key =="AttributeClass1" && value == -1)
      {
        setdisableButton(true);
        setAttributeClass2('-1');
        setdisableAttributeClass2(true);
        setAttributes2([]);
        onGenerate([]);
      }
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

    const renderTextCell = (node, key, autoFocus) => {
        const indent = node.id;

            return (
                node.id == currentKey ?
                    <input type="text" class={`form-control text-editor text-indent-${indent}`} style={{ width: `${100 - (10 * indent)}%!important` }}
                        defaultValue={node[key]}
                        onChange={(e) => handleEditorChange(e.target.value, key)}
                        autoFocus={!!autoFocus}
                        onKeyDown={(e) => e.stopPropagation()}
                        required/>
                    :
                    <span style={node.deleted == 1? {color:"#DCDCDC"}: {color:"black"}}>{node[key]}</span>);

    }

    const renderNumberCell = (node, key, autoFocus) => {
        const indent = node.id;
        return (
            node.id == currentKey ?
                <input type="number" min="0" class={`form-control text-editor number-indent-${indent}`}
                    defaultValue={node[key]}
                    onChange={(e) => handleEditorChange(e.target.value, key)}
                    autoFocus={!!autoFocus}
                    onKeyDown={(e) => e.stopPropagation()} 
                    style={{ width: '100%' }}
                    required/>
                :
                <span style={node.deleted == 1? {color:"#DCDCDC"}: {color:"black"}}>{node[key]}</span>);
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

    const renderStatic =(node, key1,  key) =>
    {
        if(node[key1])
         return(<span style={node.deleted == 1? {color:"#DCDCDC"}: {color:"black"}}>{node[key1][key]}</span>);
    }
// Recursive function to find children by data id
    const findChildrenById = (id) => {
    for (const item of AttributesTree) {
      if (item.data.id == id) {
        return item.children; // Return children if the id matches
      }
    }
    return [];
   }
   
   const handleAutoChange =(key , value) =>
   {
     autoObject[key] = value;
     setAutoObject({ ...autoObject })
   }

   const generateNewMatrix =(evt) =>
   {
    evt.preventDefault()
    if(AttributeClass1 !=AttributeClass2 )
    {
        setshowConfirmation(true);
    }     
   else
   {
    setShowAlert(true);
    Swal.fire({
        show: showAlert,
        title: 'Error',
        text: translations.att1andatt2 ,
        icon: "error",
        timer: 2000,
        showCancelButton: false,
        showConfirmButton: false,
       }).then(() => {
        setShowAlert(false); // Reset the state after alert is dismissed
      });
   }
}

  React.useEffect(() => {
    productMatrix[0]? !!productMatrix[0].attribute1? setAttributes1(findChildrenById(productMatrix[0].attribute1.parent_id)) :setAttributes1([]):setAttributes1([]);
    productMatrix[0]? !!productMatrix[0].attribute2? setAttributes2(findChildrenById(productMatrix[0].attribute2.parent_id)) :setAttributes2([]):setAttributes2([]);
    productMatrix[0]? (!!productMatrix[0].attribute1? setAttributeClass1(productMatrix[0].attribute1.parent_id):setAttributeClass1(AttributesTree[0].data.id)):setAttributeClass1(-1);
    productMatrix[0]? (!!productMatrix[0].attribute2? setAttributeClass2(productMatrix[0].attribute2.parent_id):setAttributeClass2(AttributesTree[1].data.id)):setAttributeClass2(-1);
  }, []);
      
    return (
      <>
      <SweetAlert2 />
      <ConfirmationModal
      visible={showConfirmation}
      onClose={onCloseConfirm}
      onConfirm={onConfirm}
      message={translations.MatrixConfirmation}
      translations={translations}
      >
      </ConfirmationModal>
       
            <div class="card-body" dir={dir}>
              <form>
              <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="attributeSet" class="col-form-label">{translations.attr_set1}</label>
                            <select class="form-control selectpicker" value={AttributeClass1} onChange={(e) => handleChange('AttributeClass1', e.target.value)} >
                                    <option key='-1' value='-1'>
                                        {translations.nodata }
                                    </option>
                                    {AttributesTree.map((option) => (
                                        (option.data.id)?
                                        <option key={option.key} value={option.data.id}>
                                        {dir=="rtl"? option.data.name_ar : option.data.name_en }
                                       </option>
                                      :<></>
                                        ))}
                          </select>
                        </div>
                        <div class="col-6">
                            <label for="AttrebuteSet" class="col-form-label">{translations.attr_set2_value}</label>
                            <div class='parent'>
                            {Attributes1.map((option) => (
                                <p class='child'> {dir=="rtl"? option.data.name_ar : option.data.name_en }</p>
                            ))
                            }
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label for="attributeSet" class="col-form-label">{translations.attr_set2}</label>
                            <select class="form-control selectpicker" disabled={disableAttributeClass2} value={AttributeClass2}  onChange={(e) => handleChange('AttributeClass2', e.target.value)} >
                                    <option key='-1' value='-1'>
                                        {translations.nodata }
                                    </option>
                                    {AttributesTree.map((option) => (
                                         (option.data.id)?
                                        <option key={option.key} value={option.data.id}>
                                           {dir=="rtl"? option.data.name_ar : option.data.name_en }
                                        </option>:
                                        <></>
                                        ))}
                          </select>
                        </div>
                        <div class="col-6">
                            <label for="AttrebuteSet" class="col-form-label">{translations.attr_set2_value}</label>
                            <div class='parent'>
                            {Attributes2.map((option) => (
                                <p class='child'> {dir=="rtl"? option.data.name_ar : option.data.name_en }</p>
                            ))
                            }
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div>
            <div class="row" style={{padding:"5px"}}>
             <div class="col-6"></div>
             <div class="col-6">
                <div class="row">
                  <div class="col-8">
                    <Button className="GenaerateButton btn btn-primary" 
                    variant="primary" 
                    disabled={disableButton} onClick={(e) =>generateNewMatrix(e)}>
                        {translations.generateMatrix}
                    </Button>
                   </div>
                <div class="col-4">
                    <Dropdown>
                    <Dropdown.Toggle disabled={disableButton} className="GenaerateButton"  variant="sec" id="dropdown-basic">
                        {translations.autofill}
                    </Dropdown.Toggle>
                    <Dropdown.Menu style={{ padding: '10px' }}>
                            <Form.Group className="mb-3" controlId="formBasicCheckbox">
                            <Form.Check type="checkbox" label={translations.price} 
                            checked={!!autoObject.price ? autoObject.price  : false }
                            onChange={(e)=>handleAutoChange('price', e.target.checked)}/>
                            </Form.Group>
                            <Dropdown.Divider />
                            <Form.Group className="mb-3" controlId="formBasicCheckbox">
                            <Form.Check type="checkbox" label={translations.barcode} 
                            checked={!!autoObject.barcode ? autoObject.barcode  : false }
                            onChange={(e)=>handleAutoChange('barcode', e.target.checked)}/>
                            </Form.Group>
                            <Dropdown.Divider />
                            <Form.Group className="mb-3" controlId="formBasicCheckbox">
                            <Form.Check type="checkbox" label={translations.SKU} 
                            checked={!!autoObject.SKU ? autoObject.SKU  : false }
                            onChange={(e)=>handleAutoChange('SKU', e.target.checked)} />
                            </Form.Group>
                            <Dropdown.Divider />
                            <Form.Group className="mb-3" controlId="formBasicCheckbox">
                            <Form.Check type="checkbox" label={translations.inventory} 
                            checked={!!autoObject.startingCheck ? autoObject.startingCheck  : false }
                            onChange={(e)=>handleAutoChange('startingCheck', e.target.checked)} /> 
                                <Form.Control type="number" min="0" 
                                value={autoObject.starting}
                                onChange={(e)=>handleAutoChange('starting', e.target.value)} />
                            </Form.Group>
                        </Dropdown.Menu>
                    </Dropdown>
                </div>
                </div>
             </div>
            </div>
            <DataTable value={productMatrix} tableStyle={{ minWidth: '20rem' }} noValidate validated={true} className={"custom-tree-table"} onSubmit={handleSubmit}>
                <Column field={dir=="rtl"?"attribute1.name_ar":"attribute1.name_en"} header={translations.attr_value1}  body={(node) => (renderStatic(node, "attribute1" , dir=="rtl"?"name_ar":"name_en"))}></Column>
                <Column field={dir=="rtl"?"attribute2.name_ar":"attribute2.name_en"} header={translations.attr_value2}  body={(node) => (renderStatic(node, "attribute2" , dir=="rtl"?"name_ar":"name_en"))}></Column>
                <Column field="name_ar" style={{ width: '20%' }}  header={translations.name_ar} body={(node) => (renderTextCell(node, 'name_ar'))}></Column>
                <Column field="name_en" style={{ width: '20%' }}  header={translations.name_en} body={(node) => (renderTextCell(node, 'name_en'))}></Column>
                <Column field="price" style={{ width: '10%' }}  header={translations.price} body={(node) => (renderDecimalCell(node, 'price' , true))}></Column>
                <Column field="barcode" style={{ width: '10%' }}  header={translations.barcode} body={(node) => (renderTextCell(node, 'barcode'))}></Column>
                <Column field="SKU" style={{ width: '10%' }}  header={translations.SKU} body={(node) => (renderTextCell(node, 'SKU'))}></Column>
                <Column field="starting" style={{ width: '5%' }}  header={translations.inventory} body={(node) => (renderNumberCell(node, 'starting'))}></Column>
                <Column style={{ width: '10%' }}  body={(node) => (actionTemplate(node))}></Column>
            </DataTable>
            </div>
        </div>      
     </>

    );
  };

    
  export default ProductAttribute;
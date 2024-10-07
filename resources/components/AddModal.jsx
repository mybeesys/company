import React, { useState, useEffect } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';
import axios from 'axios';

const AddModal = ({ visible, onHide, onProductAdded, type, onClose, url , category_id, parent_id, row}) => {
    
    const [currentRow, setCurrentRow] = useState({});
    const [FormErrors, setFormErrors] = useState([]);
    const [validated, setValidated] = useState(false);
    const handleSubmit = async () => {
        
        console.log(validateForm());
        try {
            let r = {...currentRow};
            if(type == "Category")
            {
                if(validateForm())
                    {
                        r["boolactive"]? r["active"] = 1 : r["active"] = 0;
                        const response = await axios.post(url, r);
                        onProductAdded(response.data.message);
                    }
            }
            else if(type == "SubCategory")
            { 
                if(validateForm())
                {
                    r["boolactive"]? r["active"] = 1 : r["active"] = 0;
                    r["category_id"] = category_id;
                    r["parent_id"] = parent_id;
                    const response = await axios.post(url, r);
                    onProductAdded(response.data.message);
                }
            }         
            else
            {
                if(validateForm())
                {
                    r["boolactive"]? r["active"] = 1 : r["active"] = 0;
                    r["category_id"] = category_id;
                    r["subcategory_id"] = parent_id;
                    const response = await axios.post(url, r);
                    onProductAdded(response.data.message);
                }
            }
            
        } catch (error) {
            console.error('There was an error adding the product!', error);
        }
        onHide();
        setCurrentRow({boolactive:true});
    };

    const handleChange = (key, value) => {
        let r = {...currentRow};
        r[key] = value;
        setCurrentRow({...r});
    }

    const handleClose = () => {
        setCurrentRow({boolactive:true});
        onClose();
    }

    const validateForm = () => {
        const errors = {};
    
        if (!currentRow.name_ar) {
          errors.name_ar = 'Arabic Name is required';
        }
      
        if (!currentRow.name_en) {
            errors.name_en = 'English Name is required';
          }
    
        if (!currentRow.order) {
          errors.order = 'Order is required';
        } else if (isNaN(currentRow.order) || currentRow.order <= 0) {
          errors.order = 'Order must be a positive number';
        }
    
        setFormErrors(errors);
    
        // Return true if there are no errors
        return Object.keys(errors).length === 0;
      };

    return (
      <div className="modal" style={{ display: `${visible? 'block' : 'none'}` }}>
        <Modal.Dialog>
            <Modal.Header>
                <Modal.Title>{"Add " + type}</Modal.Title>
            </Modal.Header>
            <form class="needs-validation" novalidate validated={validated} onSubmit={handleSubmit}>
        <Modal.Body>
        <div class="container">
         <div class="row">
           <div class="column">{!!FormErrors.name_ar? FormErrors.name_ar:'' }</div>
         </div>
         <div class="row">
           <div class="column">{!!FormErrors.name_en? FormErrors.name_en:'' }</div>
         </div>
         <div class="row">
           <div class="column">{!!FormErrors.order? FormErrors.order:'' }</div>
         </div>
        </div>
        
                <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">Arabic Name:</label>
                        <input type="text" class="form-control" id="name_ar" value={!!currentRow.name_ar ? currentRow.name_ar : ''} 
                                    onChange={(e) => handleChange('name_ar', e.target.value)} required></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">English Name:</label>
                        <input type="text" class="form-control" id="name_en" value={!!currentRow.name_en ? currentRow.name_en : ''} 
                                    onChange={(e) => handleChange('name_en', e.target.value)} required></input>
                    </div>
                </div>
                </div>
                {type == "Product"? 
                <>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="price" class="col-form-label">Price:</label>
                    <input type="number"  min="0" class="form-control" id="price" value={!!currentRow.price ? currentRow.price : ''} 
                                onChange={(e) => handleChange('price', e.target.value)} required></input>
                   </div>
                   <div class="col-6">
                    <label for="cost" class="col-form-label">Cost:</label>
                    <input type="number"  min="0" class="form-control" id="cost" value={!!currentRow.cost ? currentRow.cost : ''} 
                                onChange={(e) => handleChange('cost', e.target.value)} required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="SKU" class="col-form-label">SKU:</label>
                    <input type="text" class="form-control" id="SKU" value={!!currentRow.SKU ? currentRow.SKU : ''} 
                                onChange={(e) => handleChange('SKU', e.target.value)} required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">Barcode:</label>
                    <input type="text" class="form-control" id="barcode" value={!!currentRow.barcode ? currentRow.barcode : ''} 
                                onChange={(e) => handleChange('barcode', e.target.value)} required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="description_ar" class="col-form-label">Arabic Description:</label>
                    <textarea class="form-control" id="description_ar" value={!!currentRow.description_ar ? currentRow.description_ar : ''} 
                                onChange={(e) => handleChange('description_ar', e.target.value)} required />
                   </div>
                   <div class="col-6">
                    <label for="description_en" class="col-form-label">English Description:</label>
                    <textarea  class="form-control" id="description_en" value={!!currentRow.description_en ? currentRow.description_en : ''} 
                                onChange={(e) => handleChange('description_en', e.target.value)} required />
                   </div>
                </div>
                </div>
                </>:<></>}
                <div class="form-group">
                    <div class="row">
                      <div class="col">
                      <label for="order" class="col-form-label">Order:</label>
                      <input type="number"  min="0" class="form-control" id="order" value={!!currentRow.order ? currentRow.order : ''} 
                                onChange={(e) => handleChange('order', e.target.value)} required></input>
                      </div>   
                      {type == "Product"? 
                        <> 
                      <div class="col">
                      <label for="class" class="col-form-label">Class:</label>
                      <input type="text" class="form-control" id="class" value={!!currentRow.class ? currentRow.class : ''} 
                                onChange={(e) => handleChange('class', e.target.value)} required></input>
                      </div>
                      </>:<></>}
                 </div>
                </div>

                <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col">
                      <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="boolactive" checked={!!currentRow.boolactive ? currentRow.boolactive : false }
                                onChange={(e) => handleChange('boolactive', e.target.checked)} />
                        </div>
                        <div class=" container col-8">Active</div>
                    </div>
                     </label>
                 </div>
                 </div>
        </Modal.Body>

        <Modal.Footer>
            <Button variant="secondary"  className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
            onClick={e => handleClose()}>Close</Button>
            <Button type="submit" variant="primary" className="btn btn-primary">Save changes</Button>
        </Modal.Footer>
        </form>	
        </Modal.Dialog>
   </div>
    );
};

export default AddModal;

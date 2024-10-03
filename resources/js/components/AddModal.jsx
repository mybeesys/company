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
                        onProductAdded(response.data);
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
                    onProductAdded(response.data);
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
                    onProductAdded(response.data);
                }
            }
            
        } catch (error) {
            console.error('There was an error adding the product!', error);
        }
        onHide();
        setCurrentRow({boolactive:true});
    };

    const handleChange = (key, value) => {
       console.log(key);
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
                    <label for="name_ar" class="col-form-label">Arabic Name:</label>
                    <input type="text" class="form-control" id="name_ar" value={!!currentRow.name_ar ? currentRow.name_ar : ''} 
                                onChange={(e) => handleChange('name_ar', e.target.value)} required></input>
                </div>
                <div class="form-group">
                    <label for="name_en" class="col-form-label">English Name:</label>
                    <input type="text" class="form-control" id="name_en" value={!!currentRow.name_en ? currentRow.name_en : ''} 
                                onChange={(e) => handleChange('name_en', e.target.value)} required></input>
                </div>
                <div class="form-group">
                    <label for="order" class="col-form-label">Order:</label>
                    <input type="number" class="form-control" id="order" value={!!currentRow.order ? currentRow.order : ''} 
                                onChange={(e) => handleChange('order', e.target.value)} required></input>
                </div>
                <div class="form-group" style={{paddingtop: '5px'}}>
                    
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
                
        </Modal.Body>

        <Modal.Footer>
            <Button variant="secondary"  className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
            onClick={e => handleClose()}>Close</Button>
            <Button type="submit" variant="primary" className="btn btn-flex btn-outline btn-color-blue-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold">Save changes</Button>
        </Modal.Footer>
        </form>	
        </Modal.Dialog>
   </div>
    );
};

export default AddModal;

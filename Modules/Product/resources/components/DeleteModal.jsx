import React, { useState, useEffect } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';
import axios from 'axios';

const DeleteModal = ({ visible, onClose , onDelete, name, url , row}) => {

  const handleClose = () => {
    onClose();
   }

   const handleDelete = async () => {
    let r ={...row};
    r["method"] = "delete";
    const response = await axios.post(url, r);
    onDelete(response.data.message);
   }
 
 
    return (
      <div className="modal" style={{ display: `${visible? 'block' : 'none'}` }}>
        <Modal.Dialog>
            <Modal.Header>
                <Modal.Title>{"Delete " + row.type}</Modal.Title>
            </Modal.Header>
        <form class="needs-validation">
        <Modal.Body>
        <div class="container">
           <p>{"Do You want to delete " +row.name_ar +" - "+row.name_en}</p>
        </div>
        </Modal.Body>

        <Modal.Footer>
            <Button variant="secondary"  className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
            onClick={e => handleClose()}>Close</Button>
            <Button onClick={e => handleDelete()} variant="primary" className="btn btn-danger">Delete</Button>
        </Modal.Footer>
        </form>	
        </Modal.Dialog>
   </div>
    );
};

export default DeleteModal;

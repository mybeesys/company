import React, { useState, useEffect } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';
import axios from 'axios';

const DeleteModal = ({ visible, onClose , onDelete, name, url , row ,translations}) => {

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
                <Modal.Title>Delete</Modal.Title>
            </Modal.Header>
        <form class="needs-validation">
        <Modal.Body>
        <div class="container">
           <p>{translations.Doyouwanttodelete+" " +row.name_ar +" - "+row.name_en}</p>
        </div>
        </Modal.Body>

        <Modal.Footer>
            <Button variant="secondary"  className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
            onClick={e => handleClose()}>{translations.Close}</Button>
            <Button onClick={e => handleDelete()} variant="primary" className="btn btn-danger">{translations.Delete}</Button>
        </Modal.Footer>
        </form>	
        </Modal.Dialog>
   </div>
    );
};

export default DeleteModal;

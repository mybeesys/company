import React, { useState, useEffect } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';

const ConfirmationModal = ({ visible, onClose , onConfirm, message ,translations}) => {

  const handleClose = (e) => {
    onClose(e);
   }

   const handleConfirm = (e) => {
    onConfirm(e);
   }
 
  return (
      <div className="modal" style={{ display: `${visible? 'block' : 'none'}` }}>
        <Modal.Dialog>
            <Modal.Header>
                <Modal.Title>{translations.Confirm}</Modal.Title>
            </Modal.Header>
        <form class="needs-validation">
        <Modal.Body>
        <div class="container">
           <p>{message}</p>
        </div>
        </Modal.Body>
        <Modal.Footer>
            <Button variant="secondary"  className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
            onClick={e => handleClose(e)}>{translations.Close}</Button>
            <Button onClick={e => handleConfirm(e)} variant="primary" className="btn btn-primary">{translations.Confirm}</Button>
        </Modal.Footer>
        </form>	
        </Modal.Dialog>
   </div>
    );
};

export default ConfirmationModal;

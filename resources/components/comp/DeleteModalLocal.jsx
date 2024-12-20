import React from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';

const DeleteModalLocal = ({ visible, onClose, onDelete, row, translations }) => {

  const handleClose = (e) => {
    e.preventDefault();
    onClose();
  }

  const handleDelete = async (e) => {
    e.preventDefault();
    onDelete(row);
  }

  return (
    <div className="modal" style={{ display: `${visible ? 'block' : 'none'}` }}>
      <Modal.Dialog>
        <Modal.Header>
          <Modal.Title>{translations.Delete}</Modal.Title>
        </Modal.Header>
          <Modal.Body>
            <div class="container">
              <p>{translations.Doyouwanttodelete + " " + row.name_ar + " - " + row.name_en}</p>
            </div>
          </Modal.Body>

          <Modal.Footer>
            <button variant="secondary" class="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
              onClick={e => handleClose(e)}>{translations.Close}</button>
            <button onClick={e => handleDelete(e)} class="btn btn-danger">{translations.Delete}</button>
          </Modal.Footer>
      </Modal.Dialog>
    </div>
  );
};

export default DeleteModalLocal;

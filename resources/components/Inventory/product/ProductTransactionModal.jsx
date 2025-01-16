import React, { useEffect, useState } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';
import TreeTableComponentLocal from '../../comp/TreeTableComponentLocal';

const ProductTransactionModal = ({ visible, onClose, transactions, translations, dir }) => {

  

  useEffect(() => {
    
  }, [transactions]);

  const handleClose = () => {
    onClose();
  }


  return (
    <div className="modal modal-lg" style={{ display: `${visible ? 'block' : 'none'}` }}>
      <Modal.Dialog>
        <Modal.Header>
          <Modal.Title>{translations.transactions}</Modal.Title>
        </Modal.Header>
        <form class="needs-validation">
          <Modal.Body>
            <div class="container">
              <TreeTableComponentLocal
                translations={translations}
                dir={dir}
                addNewRow={false}
                type= {"transactions"}
                title={translations.transactions}
                currentNodes={transactions}
                cols={[
                  {key : "type", title: "date", autoFocus: false, type :"Text", width:'40%', editable:true},
                  {key : "transaction_date", title: "date", autoFocus: false, type :"Text", width:'40%', editable:true},
                  {key : "qty", title: "qty", autoFocus: false, type :"Decimal", width:'40%', editable:true},
                  {key : "unit_transfer", title:"unit", autoFocus: true,  type :"AsyncDropDown", width:'40%', editable:false},
                  {key : "sub_total", title:"sss", autoFocus: true,  type :"Decimal", width:'40%', editable:false},
                  
                ]}
                actions ={[
                    
                ]}
                onUpdate={null}
                onDelete={null}
              />
            </div>
          </Modal.Body>

          <Modal.Footer>
            <Button variant="primary" className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
              onClick={e => {e.preventDefault(); handleClose();}}>{translations.Close}</Button>
            </Modal.Footer>
        </form>
      </Modal.Dialog>
    </div>
  );
};

export default ProductTransactionModal;

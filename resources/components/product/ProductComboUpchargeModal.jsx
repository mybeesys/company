import React, { useEffect, useState } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';
import TreeTableComponentLocal from '../comp/TreeTableComponentLocal';

const ProductComboUpchargeModal = ({ visible, onClose, combo, translations, dir }) => {

  const [upchargeProducts, setUpchargeProducts] = useState([]);

  useEffect(() => {
    setUpchargeProducts([...combo.products]);
  }, [combo]);

  const handleClose = () => {
    onClose(combo, upchargeProducts);
  }


  return (
    <div className="modal" style={{ display: `${visible ? 'block' : 'none'}` }}>
      <Modal.Dialog>
        <Modal.Header>
          <Modal.Title>{translations.useUpCharge}</Modal.Title>
        </Modal.Header>
        <form class="needs-validation">
          <Modal.Body>
            <div class="container">
              <TreeTableComponentLocal
                translations={translations}
                dir={dir}
                type= {"comboUpcharge"}
                title={translations.comboUpcharge}
                currentNodes={upchargeProducts}
                cols={[
                    {key : "label", title:"product", autoFocus: true, options: [], type :"Text", width:'40%', editable:false},
                    {key : "price", autoFocus: false, options: [], type :"Decimal", width:'40%', editable:true}
                ]}
                actions ={[
                    
                ]}
                onUpdate={(nodes)=> {setUpchargeProducts(nodes); return {message:"Done"}}}
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

export default ProductComboUpchargeModal;

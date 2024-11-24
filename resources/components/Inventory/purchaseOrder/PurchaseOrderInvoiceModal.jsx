import React, { useEffect, useState } from 'react';
import Modal from 'react-bootstrap/Modal';
import { Button } from 'primereact/button';

const PurchaseOrderInvoiceModal = ({ visible, onClose, data, translations, dir, onSave }) => {

  const [upchargeProducts, setUpchargeProducts] = useState([]);

  useEffect(() => {
    setUpchargeProducts([...combo.products]);
  }, [combo]);

  const handleClose = () => {
    onClose(combo, upchargeProducts);
  }

  const handleDelete = async () => {
    let r ={...row};
    r["method"] = "delete";
    const response = await axios.post(url, r);
    onDelete(response.data.message);
   }

  return (
    <div className="modal" style={{ display: `${visible ? 'block' : 'none'}` }}>
      <Modal.Dialog>
        <Modal.Header>
          <Modal.Title>{translations.useUpCharge}</Modal.Title>
        </Modal.Header>
        <form class="needs-validation">
          <Modal.Body>
            <div class="form-group">
              <div class="row">
                <div class="col-12">
                  <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                  <input type="text" class="form-control" id="name_ar" value={!!currentObject.name_ar ? currentObject.name_ar : ''}
                    onChange={(e) => handleChange('name_ar', e.target.value)} required></input>
                </div>
              </div>    
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

export default PurchaseOrderInvoiceModal;

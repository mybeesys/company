import React from 'react';
import ReactDOM from 'react-dom/client';


const App = () => {
    const rootElement = document.getElementById('product-root');
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    let product = JSON.parse(rootElement.getAttribute('product'));

    return (
      <div>
         <div class="row">
            <div class="col-3">
            <div class="card mb-5 mb-xl-8">
            </div>
            </div>
            <div class="col-9">
            <div class="card mb-5 mb-xl-8">
             <div class="card-body">
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">Arabic Name:</label>
                            <input type="text" class="form-control" id="name_ar" value={!!product.name_ar ? product.name_ar : ''} 
                                    required></input>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">English Name:</label>
                            <input type="text" class="form-control" id="name_en" value={!!product.name_en ? product.name_en : ''} 
                                    required></input>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">Arabic Description:</label>
                            <textarea type="text" class="form-control" id="description_ar" value={!!product.description_ar ? product.description_ar : ''} 
                                    required></textarea>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">English Description:</label>
                            <textarea type="text" class="form-control" id="description_en" value={!!product.description_en ? product.description_en : ''} 
                                    required></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="price" class="col-form-label">Price:</label>
                    <input type="number"  min="0" class="form-control" id="price" value={!!product.price ? product.price : ''} 
                             required></input>
                   </div>
                   <div class="col-6">
                    <label for="cost" class="col-form-label">Cost:</label>
                    <input type="number"  min="0" class="form-control" id="cost" value={!!product.cost ? product.cost : ''} 
                              required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="SKU" class="col-form-label">SKU:</label>
                    <input type="text" class="form-control" id="SKU" value={!!product.SKU ? product.SKU : ''} 
                              required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">Barcode:</label>
                    <input type="text" class="form-control" id="barcode" value={!!product.barcode ? product.barcode : ''} 
                           required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="SKU" class="col-form-label">Class:</label>
                    <input type="text" class="form-control" id="class" value={!!product.class ? product.class : ''} 
                              required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">Commissions:</label>
                    <input  type="number"  min="0" class="form-control" id="commissions" value={!!product.commissions ? product.commissions : ''} 
                           required></input>
                   </div>
                </div>
                </div>
                
                <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col">
                      <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="active" checked={(product.active  && product.active== 1) ? true : false }
                          />
                        </div>
                        <div class=" container col-8">Active</div>
                    </div>
                     </label>
                 </div>
                 </div>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col">
                      <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="sold_by_weight" checked={(product.sold_by_weight  && product.sold_by_weight== 1) ? true : false }
                          />
                        </div>
                        <div class=" container col-8">Sold By Weight</div>
                    </div>
                     </label>
                 </div>
                 </div>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col-12">
                      <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="track_serial_number" checked={(product.track_serial_number  && product.track_serial_number== 1) ? true : false }
                          />
                        </div>
                        <div class="col-8">Track Serial Number</div>
                    </div>
                     </label>
                 </div>
                 </div>
             </div>
            </div>
         </div>
         </div>
         </div>
    );
  };
  
ReactDOM.createRoot(document.getElementById('product-root')).render(<App />);
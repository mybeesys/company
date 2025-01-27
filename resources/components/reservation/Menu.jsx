import { useEffect, useState } from "react";
import { getRowName } from "../lang/Utils";
import io from 'socket.io-client';
import SweetAlert2 from 'react-sweetalert2';

const socket = io('http://3.95.164.155:3000'); // Connect to the Socket.IO server
//const socket = io('http://localhost:3000'); // Connect to the Socket.IO server

const Menu = ({ translations, dir }) => {
    const rootElement = document.getElementById('root');
    const blankurl = rootElement.getAttribute('blank-url');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const table = JSON.parse(rootElement.getAttribute('table'));
    const [currentTab, setCurrentTab] = useState('all');
    const [nodes, setNodes] = useState([]);
    const [allProducts, setAllProducts] = useState([]);
    const [allProductList, setAllProductList] = useState([]);
    const [order, setOrder] = useState({ items: [] });
    const [disableSubmitButton, setSubmitdisableButton] = useState(false);
    const [showAlert, setShowAlert] = useState(false);
    const [showMenu, setShowMenu] = useState(true);
    useEffect(() => {
        refreshMenu();
    }, []);

    const refreshMenu = () => {
        try {
            axios.get(urlList).then(response => {
                let result = response.data;
                let allProducts1 = [];
                let allProducts2 = [];
                result.forEach(category => {
                    allProducts1 = allProducts1.concat(category.children_with_products);
                    category.children_with_products.forEach(subCategory => {
                        allProducts2 = allProducts2.concat(subCategory.products_for_sale);
                    });
                });
                setNodes(result);
                setAllProducts(allProducts1);
                setAllProductList(allProducts2);
                console.log(allProducts1);
            });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    const getProductCount = (id) => {
        let index = order.items.findIndex(x => x.item_id == id);
        if (index == -1)
            return 0;
        else
            return order.items[index].quantity;
    }

    const sendOrder = (order) => {
        socket.emit('order', order, (response) => {
            // This is the acknowledgment callback that is executed once the server sends a response
            console.log('Received response for order:', response);
            if (response.success) {
                console.log('Order successfully processed:', response.order);
                setOrder(response.order);
            } else {
                console.log('Failed to process order:', response.message);
            }
        });
    }

    const removeFromOrder = (e, product) => {
        e.preventDefault();
        let index = order.items.findIndex(x => x.item_id == product.id);
        if (index == -1) {
            return;
        }
        else {
            if (order.items[index].quantity == 1)
                order.items.splice(index, 1);
            else{
                order.items[index].quantity -= 1;
                order.items[index].item_price = product.price_with_tax * order.items[index].quantity;
            }
        }
        sendOrder(order);
    }

    const addToOrder = (e, product) => {
        e.preventDefault();
        order.table_code = table.code;
        order.tenant = table.tenant;
        let index = order.items.findIndex(x => x.item_id == product.id);
        if (index == -1) {
            let item = {};
            item.quantity = 1;
            item.item_price = product.price_with_tax;
            item.item_id = product.id;
            order.items.push(item);
        }
        else {
            order.items[index].quantity +=  1;
            order.items[index].item_price = product.price_with_tax * order.items[index].quantity;
        }
        sendOrder(order);
    }

    const clickSubmit = (event) => {
        event.preventDefault();
        event.stopPropagation();
        setSubmitdisableButton(true);
        socket.emit('submitOrder', order, (response) => {
            // This is the acknowledgment callback that is executed once the server sends a response
            console.log('Received response for order:', response);
            if (response.success) {
                console.log('Order successfully submited:', response.order);
                setShowAlert(true);
                Swal.fire({
                    show: showAlert,
                    title: '',
                    html: translations.orderSubmited,
                    icon: "info",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showCancelButton: false,
                    showConfirmButton: false,
                }).then(() => { });
                setOrder(response.order);
            } else {
                console.log('Failed to process order:', response.message);
            }
        });
    }

    const renderProduct = (product, item)=>{
        return (
            <div class="row">
                <div class="row border-bottom py-2">
                    <div class="col-4 d-flex align-items-center" style={{"font-size" : "large"}}>{getRowName(product)}</div>
                    <div class="col-5">
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button class="custom-btn" onClick={(e) => addToOrder(e, product)}>{`+`}</button>
                            <p class="text-primary mb-0">{getProductCount(product.id)}</p>
                            <button class="custom-btn" onClick={(e) => removeFromOrder(e, product)}>{`-`}</button>
                        </div>
                    </div>
                    <div class="col-3 d-flex align-items-center justify-content-center" style={{"font-size" : "large"}}>{item.item_price}</div>
                </div>
            </div>
        )
    }

    return (
        <div>
            <div>
                <div class="card-toolbar row" style={{display : `${!showMenu ? 'block' : 'none'}`}}>
                    {
                      order.items.map((item)=> (
                            renderProduct(allProductList.find(x=>x.id == item.item_id), item)
                      )  
                    )}
                    <div class="row">
                    <div class="row border-bottom py-2">
                        <div class="col-4 d-flex align-items-center" style={{"font-size" : "large", "font-weight" : "800"}}>{translations.total}</div>
                            <div class="col-5">
                                
                            </div>
                            <div class="col-3 d-flex align-items-center justify-content-center" style={{"font-size" : "large", "font-weight" : "800"}}>{order.items.reduce((sum, item) => sum + item.item_price, 0)}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button class="btn btn-primary mx-2" style={{ "width": "12rem" }} onClick={clickSubmit} disabled={disableSubmitButton}>{translations.submitOrder}</button>
                        <button class="btn btn-secondary mx-2" style={{ "width": "12rem" }} onClick={(e) => setShowMenu(true)}>{translations.back}</button>
                    </div>
                </div>
            </div>
            <div class="container my-4" style={{display : `${showMenu ? 'block' : 'none'}`}}>
                <div class="card-toolbar row">
                    <div class="col-10">
                        <ul class="nav nav-tabs  nav-stretch fs-4 fw-bold custom-tabs">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active"
                                    id={`all_tab`} onClick={(e) => setCurrentTab(0)}
                                    data-bs-toggle="tab" role="tab" href={`#cat-all`}>{translations.showAll}</a>
                            </li>
                            {nodes.map((category, index) =>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link"
                                        id={`${category.id}_tab`} onClick={(e) => setCurrentTab(index + 1)}
                                        data-bs-toggle="tab" role="tab" href={`#cat-${category.id}`}>{getRowName(category, dir)}</a>
                                </li>
                            )}
                        </ul>
                    </div>
                    <div class="col-2" style={{ "justify-content": "end", "display": "flex" }}>
                        <div class="flex-center" style={{ "display": "flex" }}>
                            <button onClick={(e) => setShowMenu(false)} disabled={disableSubmitButton} class="btn btn-primary mx-2"
                                style={{ "width": "12rem" }}>{translations.viewCart}</button>

                        </div>

                    </div>
                </div>
                {/* <div class="d-flex align-items-center mt-3">
                <input type="text" class="form-control me-3" placeholder="Search your menu item here" />
            </div> */}
                <div class="tab-content">
                    <div class="row mt-4 tab-pane fade show active" id={`cat-all`} role="tabpanel" aria-labelledby={`all_tab`}>
                        {allProducts.map((subCatgeory) =>
                            <div>
                                <div class="container pb-3">
                                    <span class="title ">{`${getRowName(subCatgeory, dir)} (${subCatgeory.products_for_sale.length})`}</span>
                                </div>
                                <div class="container my-5">
                                    <div class="d-flex justify-content-between flex-wrap gap-4">
                                        {subCatgeory.products_for_sale.map((product) =>

                                            <div class="card p-3" style={{ "max-width": "30rem", "min-width": "30rem" }}>
                                                <div class="row g-0">
                                                    <div class="col-md-4">
                                                        <img src={!!!product.image ? blankurl : `/${product.image}`} class="img-fluid rounded-start" alt="Paneer Tikka"
                                                            style={{ "object-fit": "cover" }} />
                                                    </div>

                                                    <div class="col-md-8">
                                                        <div class="card-body">
                                                            <h5 class="card-title d-flex align-items-center">
                                                                <span class="me-2 text-success">🟢</span> {getRowName(product, dir)}
                                                            </h5>

                                                            <p style={{ "min-height": "2.6rem" }} class="card-text text-muted mb-1">{dir = 'rtl' ? product.description_ar : product.description_en}</p>
                                                            <p class="text-muted mb-1">{`${translations.preparationTime1}: ${product.preparation_time ?? ''} ${translations.minutes}`}</p>
                                                            <p class="text-muted mb-1">{`${translations.calories}: ${product.calories ?? ''}`}</p>
                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <h5 class="text-primary mb-0">{`${product.price_with_tax} $`}</h5>
                                                                <button class="custom-btn" onClick={(e) => addToOrder(e, product)}>{`+`}</button>
                                                                <p class="text-primary mb-0">{getProductCount(product.id)}</p>
                                                                <button class="custom-btn" onClick={(e) => removeFromOrder(e, product)}>{`-`}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                        )}
                    </div>
                    {nodes.map((category) =>

                        <div class="row mt-4 tab-pane fade" id={`cat-${category.id}`} role="tabpanel" aria-labelledby={`${category.id}_tab`}>
                            {category.children_with_products.map((subCatgeory) =>
                                <div>
                                    <div class="container pb-3">
                                        <span class="title ">{`${getRowName(subCatgeory, dir)} (${subCatgeory.products_for_sale.length})`}</span>
                                    </div>
                                    <div class="container my-5">
                                        <div class="d-flex justify-content-between flex-wrap gap-4">
                                            {subCatgeory.products_for_sale.map((product) =>

                                                <div class="card p-3" style={{ "max-width": "30rem", "min-width": "30rem" }}>
                                                    <div class="row g-0">
                                                        <div class="col-md-4">
                                                            <img src={!!!product.image ? blankurl : `/${product.image}`} class="img-fluid rounded-start" alt="Paneer Tikka"
                                                                style={{ "object-fit": "cover" }} />
                                                        </div>

                                                        <div class="col-md-8">
                                                            <div class="card-body">
                                                                <h5 class="card-title d-flex align-items-center">
                                                                    <span class="me-2 text-success">🟢</span> {getRowName(product, dir)}
                                                                </h5>

                                                                <p style={{ "min-height": "2.6rem" }} class="card-text text-muted mb-1">{dir = 'rtl' ? product.description_ar : product.description_en}</p>
                                                                <p class="text-muted mb-1">{`${translations.preparationTime1}: ${product.preparation_time ?? ''} ${translations.minutes}`}</p>
                                                                <p class="text-muted mb-1">{`${translations.calories}: ${product.calories ?? ''}`}</p>
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <h5 class="text-primary mb-0">{`${product.price_with_tax} $`}</h5>
                                                                    <button class="custom-btn" onClick={(e) => addToOrder(e, product)}>{`+`}</button>
                                                                    <p class="text-primary mb-0">{getProductCount(product.id)}</p>
                                                                    <button class="custom-btn" onClick={(e) => removeFromOrder(e, product)}>{`-`}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                            )}
                        </div>

                    )}
                </div>
            </div>
        </div>
    );
}

export default Menu;
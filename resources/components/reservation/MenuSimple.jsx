import { useEffect, useState } from "react";
import { getRowName } from "../lang/Utils";
import io from 'socket.io-client';
import SweetAlert2 from 'react-sweetalert2';

///const socket = io('http://3.95.164.155:3000'); // Connect to the Socket.IO server
const socket = io('http://localhost:3000'); // Connect to the Socket.IO server

const MenuQR = ({ translations, dir }) => {
    const rootElement = document.getElementById('root');
    const blankurl = rootElement.getAttribute('blank-url');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const [currentTab, setCurrentTab] = useState('all');
    const [nodes, setNodes] = useState([]);
    const [allProducts, setAllProducts] = useState([]);

    useEffect(() => {
        refreshMenu();
    }, []);

    const refreshMenu = () => {
        try {
            axios.get(urlList).then(response => {
                let result = response.data;
                let allProducts1 = [];
                result.forEach(category => {
                    allProducts1 = allProducts1.concat(category.children_with_products);
                });
                setNodes(result);
                setAllProducts(allProducts1);
                console.log(allProducts1);
            });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    return (
        <div class="container my-4">
            <div class="card-toolbar row">
                <div class="col-10">
                    <ul class="nav nav-tabs">
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
            </div>
            {/* <div class="d-flex align-items-center mt-3">
                <input type="text" class="form-control me-3" placeholder="Search your menu item here" />
            </div> */}
            <div class="tab-content">
                <div class="row mt-4 tab-pane fade show active" id={`cat-all`} role="tabpanel" aria-labelledby={`all_tab`}>
                    {allProducts.map((subCatgeory) =>
                        <div>
                            <div class="container pb-3">
                                <span class="title ">{getRowName(subCatgeory, dir)}</span>
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

                                                        <p style={{ "min-height": "3rem" }} class="card-text text-muted mb-1">{dir = 'rtl' ? product.description_ar : product.description_en}</p>
                                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                                            <h5 class="text-primary mb-0">{`${product.price} $`}</h5>
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
                                    <span class="title ">{getRowName(subCatgeory, dir)}</span>
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

                                                            <p style={{ "min-height": "3rem" }} class="card-text text-muted mb-1">{dir = 'rtl' ? product.description_ar : product.description_en}</p>
                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <h5 class="text-primary mb-0">{`${product.price} $`}</h5>
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
    );
}

export default MenuQR;
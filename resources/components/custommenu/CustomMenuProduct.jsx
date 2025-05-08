import { Column } from "primereact/column";
import { TreeTable } from "primereact/treetable";
import { useEffect, useState } from "react";
const CustomMenuProduct = ({
    translations,
    customMenuProducts,
    onProductSelectionChange,
    dir,
}) => {
    const [products, setProducts] = useState([]);
    const [selectedNodeKeys, setSelectedNodeKeys] = useState([]);

    const cleanProductData = (products, cleanProducts) => {
        for (let index = 0; index < products.length; index++) {
            const product = products[index];
            if (!!product.data.empty) continue;
            let cleanChildren = [];
            if (!!product.children)
                cleanProductData(product.children, cleanChildren);
            let cleanProduct = product;
            cleanProduct.children = cleanChildren;
            cleanProducts.push(cleanProduct);
        }
    };

    const checkSelected = (product, sKeys) => {
        let ss = { checked: false, partialChecked: false };
        if (
            product.data.type == "product" &&
            customMenuProducts.filter((x) => x.product_id == product.data.id)
                .length > 0
        ) {
            sKeys[product.key] = { checked: true, partialChecked: false };
        } else {
            if (!!product.children && product.children.length > 0) {
                let checked = null;
                ss = { checked: false, partialChecked: false };
                for (
                    let index1 = 0;
                    index1 < product.children.length;
                    index1++
                ) {
                    const child = product.children[index1];
                    let result = checkSelected(child, sKeys);
                    if (!!!checked) checked = result.checked;
                    if (result.checked || result.partialChecked)
                        ss.partialChecked = true;
                    checked = checked && result.checked;
                }
                ss[checked] = checked;
                sKeys[product.key] = { ...ss };
            }
        }
        return !!sKeys[product.key] ? sKeys[product.key] : ss;
    };

    const setInitialSelectedNode = (Prods) => {
        let sKeys = [];
        for (let index = 0; index < Prods.length; index++) {
            const product = Prods[index];
            checkSelected(product, sKeys);
            //    sKeys[product.key] = {checked : true};
        }
        setSelectedNodeKeys({ ...sKeys });
    };

    useEffect(() => {
        axios
            .get("/categories")
            .then((response) => {
                let cleanProducts = [];
                cleanProductData(response.data, cleanProducts);
                setProducts(cleanProducts);
                setInitialSelectedNode(response.data);
            })
            .catch((error) => {
                console.error("Error fetching translations", error);
            });
    }, []);

    const fillSelectedProducts = (products, keys, selectedProductIds) => {
        for (let index = 0; index < products.length; index++) {
            const product = products[index];
            if (
                Object.keys(keys).filter((x) => x == product.key).length > 0 &&
                product.data.type == "product" &&
                keys[product.key].checked
            )
                selectedProductIds.push(product.data.id);
            if (!!product.children)
                fillSelectedProducts(
                    product.children,
                    keys,
                    selectedProductIds
                );
        }
    };

    const onSelectedProductChange = (keys) => {
        let selectedProducts = [];
        fillSelectedProducts(products, keys, selectedProducts);
        setSelectedNodeKeys(keys);
        onProductSelectionChange(selectedProducts);
    };

    return (
        <section class="product spad">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="trending__product">
                            <div class="row border-bottom">
                                <div class="col-lg-8 col-md-8 col-sm-8">
                                    <div class="section-title">
                                        <h4>{translations.products}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4">
                                    <div class="btn__all"></div>
                                </div>
                            </div>
                            <div class="container">
                                <div class="row border-bottom border-dark">
                                    <TreeTable
                                        selectionMode="checkbox"
                                        selectionKeys={selectedNodeKeys}
                                        onSelectionChange={(e) =>
                                            onSelectedProductChange(e.value)
                                        }
                                        value={products}
                                        tableStyle={{ minWidth: "65rem" }}
                                        className={"custom-tree-table"}
                                    >
                                        <Column
                                            style={{ width: "20%" }}
                                            field="name_en"
                                            expander
                                        ></Column>
                                        <Column
                                            style={{ width: "20%" }}
                                            field="name_ar"
                                        ></Column>
                                    </TreeTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default CustomMenuProduct;

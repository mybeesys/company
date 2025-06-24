import React, { useEffect, useState } from "react";
import Modal from "react-bootstrap/Modal";
import { Button } from "primereact/button";
import TreeTableComponentLocal from "../comp/TreeTableComponentLocal";

const ProductComboUpchargeModal = ({
    visible,
    onClose,
    combo,
    translations,
    dir,
}) => {
    const [upchargeProducts, setUpchargeProducts] = useState([]);

    useEffect(() => {
        if (combo?.products) {
            const productsWithPrices = combo.products.map((product) => ({
                ...product,
                price:
                    combo.upchargePrices?.find(
                        (p) => p.product_id === product.value
                    )?.price || 0,
            }));
            setUpchargeProducts(productsWithPrices);
        }
    }, [combo]);

    const handleClose = () => {
        const productsWithPrices = upchargeProducts.map((product) => ({
            value: product.value,
            price: product.price,
        }));
        onClose(combo.id, productsWithPrices);
    };

    const handleUpdate = (nodes) => {
        const updatedProducts = upchargeProducts.map((product) => {
            const updatedNode = nodes.find((n) => n.value === product.value);
            return updatedNode
                ? { ...product, price: updatedNode.price }
                : product;
        });

        setUpchargeProducts(updatedProducts);
        return { message: "Done" };
    };

    return (
        <div
            className="modal"
            style={{ display: `${visible ? "block" : "none"}` }}
        >
            <Modal.Dialog>
                <Modal.Header>
                    <Modal.Title>{translations.useUpCharge}</Modal.Title>
                </Modal.Header>
                <form className="needs-validation">
                    <Modal.Body>
                        <div className="container">
                            <TreeTableComponentLocal
                                translations={translations}
                                dir={dir}
                                addNewRow={false}
                                type={"comboUpcharge"}
                                title={translations.comboUpcharge}
                                currentNodes={upchargeProducts}
                                cols={[
                                    {
                                        key: "label",
                                        title: "product",
                                        autoFocus: true,
                                        options: [],
                                        type: "Text",
                                        width: "40%",
                                        editable: false,
                                    },
                                    {
                                        key: "price",
                                        autoFocus: false,
                                        options: [],
                                        type: "Decimal",
                                        width: "40%",
                                        editable: true,
                                        required: true,
                                    },
                                ]}
                                actions={[]}
                                onUpdate={handleUpdate}
                                onDelete={null}
                            />
                        </div>
                    </Modal.Body>

                    <Modal.Footer>
                        <Button
                            variant="primary"
                            className="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold"
                            onClick={(e) => {
                                e.preventDefault();
                                handleClose();
                            }}
                        >
                            {translations.Close}
                        </Button>
                    </Modal.Footer>
                </form>
            </Modal.Dialog>
        </div>
    );
};

export default ProductComboUpchargeModal;

import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableEditorLocal from "../../comp/TreeTableEditorLocal";
import { getName } from "../../lang/Utils";
import SweetAlert2 from "react-sweetalert2";
import axios from "axios";

const WasteDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    let waste = JSON.parse(rootElement.getAttribute("waste"));
    const [currentObject, setCurrentObject] = useState(waste);
    const [showAlert, setShowAlert] = useState(false);
    const [branchProducts, setBranchProducts] = useState([]);
    const [unitSearchUrls, setUnitSearchUrls] = useState({});

    useEffect(() => {
        if (currentObject.establishment?.id) {
            fetchProductsForEstablishment(currentObject.establishment.id);
        }
    }, [currentObject.establishment, currentObject.items.product]);

    const fetchProductsForEstablishment = async (establishmentId) => {
        try {
            const response = await axios.get(
                `${window.location.origin}/getProductsByEstablishment/${establishmentId}`
            );
            setBranchProducts(response.data);
        } catch (error) {
            console.error("Error fetching products:", error);
            setBranchProducts([]);
        }
    };

    const onBasicChange = (key, value) => {
        const updatedObject = { ...currentObject };
        updatedObject[key] = value;

        if (key === "establishment" && value && value.id) {
            updatedObject.items = [];
            fetchProductsForEstablishment(value.id);
        }

        setCurrentObject(updatedObject);
    };

    const onProductChange = (key, val) => {
        setCurrentObject((prev) => ({
            ...prev,
            [key]: val,
        }));
        return { message: "Done" };
    };
    useEffect(() => {
        const urls = {};
        currentObject.items.forEach((item, index) => {
            if (item.product && item.product.id) {
                urls[
                    index
                ] = `${window.location.origin}/searchUnitTransfers?product_id=${item.product.id}`;
            }
        });
        setUnitSearchUrls(urls);
    }, [currentObject.items]);

    const getErrorMessage = (data) => {
        return data
            .map(
                (element) =>
                    `<div>${getName(element.name_en, element.name_ar, dir)} : ${
                        element.qty
                    }</div>`
            )
            .join("");
    };

    const handleQuantityError = (data) => {
        Swal.fire({
            title: "Error",
            html: `<div>${
                translations.notEnoughQuantity
            }</div>${getErrorMessage(data)}`,
            icon: "error",
            timer: 4000,
            showCancelButton: false,
            showConfirmButton: false,
        });
    };

    const validateObject = (data) => {
        if (!data.establishment || data.establishment.length === 0)
            return `${translations.establishment} ${translations.required}`;
        if (data.items && data.items.filter((x) => !x.unit).length > 0)
            return translations["item_unit_error"];
        return "Success";
    };

    return (
        <EditRowCompnent
            defaultMenu={[
                {
                    key: "wasteInfo",
                    visible: true,
                    comp: (
                        <BasicInfoComponent
                            currentObject={currentObject}
                            translations={translations}
                            dir={dir}
                            onBasicChange={onBasicChange}
                            fields={[
                                {
                                    key: "establishment",
                                    title: "establishment",
                                    searchUrl: "searchEstablishments",
                                    type: "Async",
                                    required: true,
                                },
                                {
                                    key: "transaction_date",
                                    title: "date",
                                    type: "Date",
                                    required: true,
                                    size: 12,
                                },
                                {
                                    key: "description",
                                    title: "notes",
                                    type: "TextArea",
                                    size: 12,
                                    newRow: true,
                                },
                            ]}
                        />
                    ),
                },
                {
                    key: "items",
                    visible: true,
                    comp: (
                        <TreeTableEditorLocal
                            translations={translations}
                            dir={dir}
                            header={false}
                            addNewRow={true}
                            type={"items"}
                            title={translations.items}
                            currentNodes={[...currentObject.items]}
                            defaultValue={{ taxed: 0 }}
                            cols={[
                                {
                                    key: "product",
                                    autoFocus: true,
                                    type: "DropDown",
                                    width: "30%",
                                    editable: true,
                                    required: true,
                                    options: branchProducts.map((p) => ({
                                        value: p.id,
                                        label: getName(
                                            p.name_en,
                                            p.name_ar,
                                            dir
                                        ),
                                        ...p,
                                    })),
                                    onChangeValue: (
                                        nodes,
                                        key,
                                        val,
                                        rowKey,
                                        postExecute
                                    ) => {
                                        const productId =
                                            typeof val === "object"
                                                ? val?.id
                                                : val;
                                        const updatedNodes = [...nodes];

                                        if (!productId) {
                                            updatedNodes[rowKey].data = {
                                                ...updatedNodes[rowKey].data,
                                                SKU: null,
                                                item_type: null,
                                                qty: null,
                                                unit_price_before_discount:
                                                    null,
                                                unit: null,
                                                total: null,
                                                product: null,
                                            };
                                            postExecute(updatedNodes);
                                            return;
                                        }

                                        const product = branchProducts.find(
                                            (p) => p.id === productId
                                        );
                                        if (product) {
                                            updatedNodes[rowKey].data.SKU =
                                                product.SKU;
                                            updatedNodes[rowKey].data.product =
                                                product;
                                            setUnitSearchUrls((prev) => ({
                                                ...prev,
                                                [rowKey]: `${window.location.origin}/searchUnitTransfers?product_id=${productId}`,
                                            }));

                                            axios
                                                .get(
                                                    `${window.location.origin}/searchUnitTransfers?product_id=${productId}`
                                                )
                                                .then((response) => {
                                                    const units = response.data;
                                                    if (
                                                        units &&
                                                        Array.isArray(units)
                                                    ) {
                                                        const defaultUnit =
                                                            units.find(
                                                                (unit) =>
                                                                    unit.unit2 ===
                                                                    null
                                                            );
                                                        const finalNodes = [
                                                            ...updatedNodes,
                                                        ];
                                                        finalNodes[
                                                            rowKey
                                                        ].data.unit =
                                                            defaultUnit || null;
                                                        postExecute(finalNodes);
                                                    } else {
                                                        postExecute(
                                                            updatedNodes
                                                        );
                                                    }
                                                })
                                                .catch((error) => {
                                                    console.error(
                                                        "Error fetching units:",
                                                        error
                                                    );
                                                    postExecute(updatedNodes);
                                                });
                                        } else {
                                            postExecute(updatedNodes);
                                        }
                                    },
                                },
                                {
                                    key: "SKU",
                                    type: "Text",
                                    width: "15%",
                                    editable: false,
                                    customCell: (data) => (
                                        <span>{data?.product?.SKU || ""}</span>
                                    ),
                                },
                                {
                                    key: "unit",
                                    type: "AsyncDropDown",
                                    width: "25%",
                                    editable: true,
                                    required: true,
                                    searchUrl: "searchUnitTransfers",
                                    relatedTo: {
                                        key: "product_id",
                                        relatedKey: "product.id",
                                    },
                                },
                                {
                                    key: "qty",
                                    type: "Decimal",
                                    width: "20%",
                                    editable: true,
                                    required: true,
                                },
                                {
                                    key: "delete",
                                    type: "Button",
                                    width: "10%",
                                    editable: false,
                                    customCell: (
                                        data,
                                        key,
                                        currentEditing,
                                        editable,
                                        rowKey
                                    ) => (
                                        <button
                                            className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                                            onClick={() => {
                                                const updatedItems = [
                                                    ...currentObject.items,
                                                ];
                                                updatedItems.splice(rowKey, 1);
                                                onProductChange(
                                                    "items",
                                                    updatedItems
                                                );
                                            }}
                                        >
                                            <i className="ki-outline ki-trash fs-2"></i>
                                        </button>
                                    ),
                                },
                            ]}
                            actions={[]}
                            onUpdate={(nodes) =>
                                onProductChange("items", nodes)
                            }
                            onDelete={null}
                        />
                    ),
                },
            ]}
            currentObject={currentObject}
            translations={translations}
            dir={dir}
            apiUrl={"storeWaste"}
            afterSubmitUrl="../../waste"
            handleError={handleQuantityError}
            validateObject={validateObject}
        />
    );
};

export default WasteDetail;

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
    const [currentObject, setcurrentObject] = useState(waste);
    const [showAlert, setShowAlert] = useState(false);
    const [branchProducts, setBranchProducts] = useState([]);

    useEffect(() => {
        if (currentObject.establishment?.id) {
            fetchProductsForEstablishment(currentObject.establishment.id);
        }
    }, [currentObject]);

    const fetchProductsForEstablishment = async (establishmentId) => {
        try {
            const response = await axios.get(
                `${window.location.origin}/getProductsByEstablishment/${currentObject.establishment.id}`
            );
            setBranchProducts(response.data);
        } catch (error) {
            console.error("Error fetching products:", error);
            setBranchProducts([]);
        }
    };

    const onBasicChange = (key, value) => {
        let r = { ...currentObject };
        r[key] = value;
        setcurrentObject({ ...r });
        if (key === "establishment" && value && value.id) {
            r.items = [];
            fetchProductsForEstablishment(value.id);
        }
    };

    const onProductChange = (key, val) => {
        currentObject[key] = val;
        setcurrentObject({ ...currentObject });
        return { message: "Done" };
    };

    const getErrorMessage = (data) => {
        let res = "";
        for (let index = 0; index < data.length; index++) {
            const element = data[index];
            res += `<div>${getName(element.name_en, element.name_ar, dir)} : ${
                element.qty
            }</div>`;
        }
        return res;
    };

    const handleQuantityError = (data) => {
        setShowAlert(true);
        Swal.fire({
            show: showAlert,
            title: "Error",
            html: `<div>${
                translations.notEnoughQuantity
            }</div>${getErrorMessage(data)}`,
            icon: "error",
            timer: 4000,
            showCancelButton: false,
            showConfirmButton: false,
        }).then(() => {
            setShowAlert(false);
        });
    };

    const validateObject = (data) => {
        if (!!!data.establishment || data.establishment.length == 0)
            return `${translations.establishment} ${translations.required}`;
        if (
            !!currentObject.items &&
            currentObject.items.filter((x) => !!!x.unit).length > 0
        )
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
                                                ? val.id
                                                : val;
                                        if (!val || !val.id) {
                                            nodes[rowKey].data.SKU = null;
                                            nodes[rowKey].data.item_type = null;
                                            nodes[rowKey].data.qty = null;
                                            nodes[
                                                rowKey
                                            ].data.unit_price_before_discount =
                                                null;
                                            nodes[rowKey].data.unit = null;
                                            nodes[rowKey].data.total = null;
                                        }

                                        const prod = branchProducts.find(
                                            (p) => p.id === val
                                        );

                                        if (prod) {
                                            nodes[rowKey].data.SKU = prod.SKU;
                                            axios
                                                .get(
                                                    `${window.location.origin}/searchUnitTransfers?product_id=${val}`
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
                                                        if (defaultUnit) {
                                                            nodes[
                                                                rowKey
                                                            ].data.unit =
                                                                defaultUnit;
                                                        } else {
                                                            nodes[
                                                                rowKey
                                                            ].data.unit = null;
                                                        }
                                                    } else {
                                                        nodes[
                                                            rowKey
                                                        ].data.unit = null;
                                                    }

                                                    postExecute(nodes);
                                                })
                                                .catch((error) => {
                                                    postExecute(nodes);
                                                });
                                        }
                                    },
                                },
                                {
                                    key: "SKU",
                                    autoFocus: true,
                                    type: "Text",
                                    width: "15%",
                                    editable: false,
                                    customCell: (data) => {
                                        return (
                                            <span>
                                                {!!data["product"]
                                                    ? data["product"].SKU
                                                    : ""}
                                            </span>
                                        );
                                    },
                                },
                                {
                                    key: "unit",
                                    autoFocus: true,
                                    type: "AsyncDropDown",
                                    width: "25%",
                                    editable: true,
                                    required: true,
                                    searchUrl: `searchUnitTransfers`,
                                    relatedTo: {
                                        key: "product_id",
                                        relatedKey: "product",
                                    },
                                },
                                {
                                    key: "qty",
                                    autoFocus: true,
                                    type: "Decimal",
                                    width: "15%",
                                    editable: true,
                                    required: true,
                                },
                                {
                                    key: "delete",
                                    autoFocus: false,
                                    type: "Button",
                                    width: "10%",
                                    editable: false,
                                    customCell: (
                                        data,
                                        key,
                                        currentEditing,
                                        editable,
                                        rowKey
                                    ) => {
                                        return (
                                            <a
                                                href="javascript:void(0);"
                                                className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                                                onClick={() => {
                                                    const updatedNodes = [
                                                        ...currentObject.items,
                                                    ];
                                                    updatedNodes.splice(
                                                        rowKey,
                                                        1
                                                    );
                                                    onProductChange(
                                                        "items",
                                                        updatedNodes
                                                    );
                                                }}
                                            >
                                                <i className="ki-outline ki-trash fs-2"></i>
                                            </a>
                                        );
                                    },
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
            apiUrl={"waste"}
            afterSubmitUrl="../../waste"
            handleError={handleQuantityError}
            validateObject={validateObject}
        />
    );
};

export default WasteDetail;

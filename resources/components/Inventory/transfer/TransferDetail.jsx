import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableEditorLocal from "../../comp/TreeTableEditorLocal";
import { getName } from "../../lang/Utils";
import SweetAlert2 from "react-sweetalert2";

const TransferDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    let transfer = JSON.parse(rootElement.getAttribute("transfer"));
    const [currentObject, setcurrentObject] = useState(transfer);
    const [showAlert, setShowAlert] = useState(false);
    const [addNewRow, setAddNewRow] = useState(true);
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get("type");

    useEffect(() => {
        //updateTotals(currentObject);
        if (type) {
            setAddNewRow(false);
        }
    }, [currentObject]);

    const onBasicChange = (key, value) => {
        let r = { ...currentObject };
        r[key] = value;
        setcurrentObject({ ...r });
    };

    // const updateTotals = (row) =>{
    //     row.subtotal = row.items ?
    //     row.items.reduce((accumulator, item) => accumulator + Number(item.total_before_vat ?? 0), 0) : 0;
    //     row.total = row.subtotal + Number(row.tax ?? 0);
    //     row.grand_total = row.total + Number(row.shipping_amount ?? 0) + Number(row.misc_amount ?? 0);
    // }

    const onProductChange = (key, val) => {
        currentObject[key] = val;
        //updateTotals(currentObject);
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
            setShowAlert(false); // Reset the state after alert is dismissed
        });
    };

    const validateObject = (data) => {
        if (!!!data.establishment || data.establishment.length == 0)
            return `${translations.from} ${translations.required}`;
        if (!!!data.toEstablishment || data.toEstablishment.length == 0)
            return `${translations.to} ${translations.required}`;
        if (
            !!currentObject.items &&
            currentObject.items.filter((x) => !!!x.unit).length > 0
        )
            return translations["item_unit_error"];
        return "Success";
    };

    return (
        <>
            <SweetAlert2 />
            <EditRowCompnent
                defaultMenu={[
                    {
                        key: "establishment",
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
                                        title: "from",
                                        searchUrl: "searchEstablishments",
                                        type: "Async",
                                        editable: type !== "partiallyReceived",
                                        required: true,
                                    },
                                    {
                                        key: "toEstablishment",
                                        title: "to",
                                        searchUrl: "searchEstablishments",
                                        type: "Async",
                                        editable: type !== "partiallyReceived",
                                    },
                                    {
                                        key: "transaction_date",
                                        title: "date",
                                        type: "Date",
                                        required: true,
                                        size: 6,
                                        editable: type !== "partiallyReceived",
                                    },
                                    {
                                        key: "notes",
                                        title: "notes",
                                        type: "TextArea",
                                        newRow: true,
                                        size: 12,
                                        editable: type !== "partiallyReceived",
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
                                addNewRow={addNewRow}
                                type={"items"}
                                title={translations.items}
                                currentNodes={[...currentObject.items]}
                                defaultValue={{ taxed: 0 }}
                                cols={[
                                    {
                                        key: "product",
                                        autoFocus: true,
                                        searchUrl: "searchProducts",
                                        type: "AsyncDropDown",
                                        width: "30%",
                                        editable: type !== "partiallyReceived",
                                        required: true,
                                        onChangeValue: (
                                            nodes,
                                            key,
                                            val,
                                            rowKey,
                                            postExecute
                                        ) => {
                                            const result = val.id.split("-");
                                            axios
                                                .get(
                                                    `${window.location.origin}/getProductInventory/${val.id}`
                                                )
                                                .then((response) => {
                                                    let prod = response.data;
                                                    nodes[rowKey].data.SKU =
                                                        prod.SKU;
                                                    nodes[
                                                        rowKey
                                                    ].data.item_type =
                                                        result[1];
                                                    if (!!prod.inventory) {
                                                        nodes[rowKey].data.qty =
                                                            prod.inventory.primary_vendor_default_quantity;
                                                        nodes[
                                                            rowKey
                                                        ].data.cost =
                                                            prod.inventory.primary_vendor_default_price;
                                                        nodes[
                                                            rowKey
                                                        ].data.unit =
                                                            prod.inventory.unit;
                                                        nodes[
                                                            rowKey
                                                        ].data.total =
                                                            !!prod.inventory
                                                                .primary_vendor_default_price &&
                                                            !!prod.inventory
                                                                .primary_vendor_default_quantity
                                                                ? prod.inventory
                                                                      .primary_vendor_default_price *
                                                                  prod.inventory
                                                                      .primary_vendor_default_quantity
                                                                : 0;
                                                    } else {
                                                        nodes[rowKey].data.qty =
                                                            null;
                                                        nodes[
                                                            rowKey
                                                        ].data.cost = null;
                                                        nodes[
                                                            rowKey
                                                        ].data.unit = null;
                                                        nodes[
                                                            rowKey
                                                        ].data.total = null;
                                                    }
                                                    postExecute(nodes);
                                                })
                                                .catch((error) => {
                                                    console.error(
                                                        "Error fetching translations",
                                                        error
                                                    );
                                                });
                                        },
                                    },
                                    {
                                        key: "SKU",
                                        autoFocus: true,
                                        type: "Text",
                                        width: "15%",
                                        editable: type !== "partiallyReceived",
                                        customCell: (
                                            data,
                                            key,
                                            currentEditing,
                                            editable
                                        ) => {
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
                                        editable: type !== "partiallyReceived",
                                        required: true,
                                        searchUrl: "searchUnitTransfers",
                                        relatedTo: {
                                            key: "id",
                                            relatedKey: "product.id",
                                        },
                                    },
                                    {
                                        key: "qty",
                                        autoFocus: true,
                                        type: "Decimal",
                                        width: "25%",
                                        editable: type !== "partiallyReceived",
                                        required: true,
                                        onChangeValue: (
                                            nodes,
                                            key,
                                            val,
                                            rowKey,
                                            postExecute
                                        ) => {
                                            // nodes[rowKey].data.total_before_vat = !!val && !!nodes[rowKey].data.unit_price_before_discount ? val * nodes[rowKey].data.cost : null;
                                            // postExecute(nodes);
                                        },
                                    },
                                    {
                                        key: "receivedQuantity",
                                        autoFocus: true,
                                        width: "22%",
                                        editable: false,
                                        required: true,
                                        customCell: (data) => {
                                            return (
                                                <span>
                                                    {data.receivedQuantity !==
                                                    undefined
                                                        ? data.receivedQuantity
                                                        : 0}
                                                </span>
                                            );
                                        },
                                    },
                                    {
                                        key: "remainingQuantity",
                                        autoFocus: true,
                                        width: "20%",
                                        editable: false,
                                        required: true,
                                        customCell: (data) => {
                                            return (
                                                <span>
                                                    {data.remainingQuantity !==
                                                    undefined
                                                        ? data.remainingQuantity
                                                        : 0}
                                                </span>
                                            );
                                        },
                                    },
                                    {
                                        key: "quantityToReceive",
                                        autoFocus: true,
                                        width: "22%",
                                        editable: type === "partiallyReceived",
                                        style: {
                                            visibility:
                                                type === "partiallyReceived"
                                                    ? "hidden"
                                                    : "visible",
                                        },
                                        type: "Decimal",
                                        required: true,
                                        onChangeValue: (
                                            nodes,
                                            key,
                                            val,
                                            rowKey,
                                            postExecute
                                        ) => {
                                            // nodes[rowKey].data.total_before_vat = !!val && !!nodes[rowKey].data.unit_price_before_discount ? val * nodes[rowKey].data.cost : null;
                                            // postExecute(nodes);
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
                type2={type}
                apiUrl="transfer"
                afterSubmitUrl="../../transfer"
                type="transfer"
                handleError={handleQuantityError}
                validateObject={validateObject}
            />
        </>
    );
};

export default TransferDetail;

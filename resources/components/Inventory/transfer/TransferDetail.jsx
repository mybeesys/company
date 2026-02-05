import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableEditor from "../../comp/TreeTableEditor";
import { getName } from "../../lang/Utils";
import SweetAlert2 from "react-sweetalert2";
import axios from "axios";

const TransferDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    let transfer = JSON.parse(rootElement.getAttribute("transfer"));

    if (!transfer.items) transfer.items = [];

    const [currentObject, setcurrentObject] = useState(transfer);
    const [showAlert, setShowAlert] = useState(false);
    const [addNewRow, setAddNewRow] = useState(true);
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get("type");

    useEffect(() => {
        if (type) {
            setAddNewRow(false);
        }
    }, [currentObject]);

    const onBasicChange = (key, value) => {
        let r = { ...currentObject };
        r[key] = value;
        setcurrentObject({ ...r });
    };

    const onProductChange = (key, val) => {
        const updatedItems = Array.isArray(val) ? val : [];
        setcurrentObject((prev) => ({ ...prev, [key]: updatedItems }));
        return { message: "Done" };
    };

    const handleOnDelete = (deletedNode) => {
        setcurrentObject((prev) => {
            const currentItems = Array.isArray(prev.items)
                ? [...prev.items]
                : [];

            const filteredItems = currentItems.filter((item) => {
                const itemKey = item.id || item.key;
                const deletedKey =
                    deletedNode.id || deletedNode.key || deletedNode.data?.id;

                if (!itemKey && !deletedKey) {
                    return item !== deletedNode;
                }
                return itemKey !== deletedKey;
            });

            return { ...prev, items: filteredItems };
        });
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
            return `${translations.from} ${translations.required}`;
        if (!!!data.toEstablishment || data.toEstablishment.length == 0)
            return `${translations.to} ${translations.required}`;
        if (
            !!currentObject.items &&
            Array.isArray(currentObject.items) &&
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
                            <TreeTableEditor
                                translations={translations}
                                dir={dir}
                                header={false}
                                addNewRow={addNewRow}
                                type={"items"}
                                title={translations.items}
                                currentNodes={
                                    Array.isArray(currentObject.items)
                                        ? [...currentObject.items]
                                        : []
                                }
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
                                            postExecute,
                                        ) => {
                                            const result = val.id.split("-");
                                            axios
                                                .get(
                                                    `${window.location.origin}/getProductInventory/${val.id}`,
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
                                                        ].data.total = null;
                                                    }
                                                    return axios.get(
                                                        `${window.location.origin}/searchUnitTransfers?product_id=${result[0]}`,
                                                    );
                                                })
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
                                                                    null,
                                                            );
                                                        nodes[
                                                            rowKey
                                                        ].data.unit =
                                                            defaultUnit || null;
                                                    } else {
                                                        nodes[
                                                            rowKey
                                                        ].data.unit = null;
                                                    }
                                                    postExecute(nodes);
                                                })
                                                .catch(() =>
                                                    postExecute(nodes),
                                                );
                                        },
                                    },
                                    {
                                        key: "SKU",
                                        type: "Text",
                                        width: "15%",
                                        editable: type !== "partiallyReceived",
                                        customCell: (data) => (
                                            <span>
                                                {data?.product?.SKU
                                                    ? data.product.SKU
                                                    : data?.SKU || ""}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: "unit",
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
                                        type: "Decimal",
                                        width: "25%",
                                        editable: type !== "partiallyReceived",
                                        required: true,
                                    },
                                    {
                                        key: "receivedQuantity",
                                        width: "22%",
                                        editable: false,
                                        customCell: (data) => (
                                            <span>
                                                {data.receivedQuantity ?? 0}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: "remainingQuantity",
                                        width: "20%",
                                        editable: false,
                                        customCell: (data) => (
                                            <span>
                                                {data.remainingQuantity ?? 0}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: "quantityToReceive",
                                        width: "22%",
                                        editable: type === "partiallyReceived",
                                        style: {
                                            visibility:
                                                type === "partiallyReceived"
                                                    ? "hidden"
                                                    : "visible",
                                        },
                                        type: "Decimal",
                                    },
                                ]}
                                actions={[]}
                                onUpdate={(nodes) => {
                                    if (Array.isArray(nodes)) {
                                        setcurrentObject((prev) => ({
                                            ...prev,
                                            items: nodes,
                                        }));
                                    }
                                }}
                                onDelete={handleOnDelete}
                                showDeleteConfirmation={false}
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

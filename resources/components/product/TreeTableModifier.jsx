import React, { useState, useEffect } from "react";
import { TreeTable } from "primereact/treetable";
import { Column } from "primereact/column";
import DeleteModal from "./DeleteModal";
import axios from "axios";
import SweetAlert2 from "react-sweetalert2";
import { getRowName } from "../lang/Utils";
import Swal from "sweetalert2";

const defaultObjectValue = { active: 1, for_sell: 1 };
const TreeTableProduct = ({ urlList, rootElement, translations }) => {
    const productCrudList = JSON.parse(
        rootElement.getAttribute("product-crud-url")
    );
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState("");
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState("-1");
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});
    const [validated, setValidated] = useState(false);
    const [expandedKeys, setExpandedKeys] = useState({});
    const [globalFilter, setGlobalFilter] = useState(null);
    const [filterOptions] = useState([
        { label: "Lenient", value: "lenient" },
        { label: "Strict", value: "strict" },
    ]);

    const expandAll = () => {
        const allKeys = getExpandedKeys(nodes);
        setExpandedKeys(allKeys);
    };

    const collapseAll = () => {
        setExpandedKeys({});
    };

    const handleDelete = (message) => {
        if (message !== "Done") {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                text: translations[message],
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false);
            });
            return;
        }
        setIsDeleteModalVisible(false);
        refreshTree();
    };

    const handleClose = () => {
        setIsDeleteModalVisible(false);
    };

    const refreshTree = () => {
        try {
            axios.get(urlList).then((response) => {
                let result = response.data;
                // Since the PHP backend now returns the correct structure,
                // we can directly set the state with the received data.
                setNodes(result);
            });
        } catch (error) {
            console.error("There was an error getting the product!", error);
        }
    };

    const getExpandedKeys = (nodes) => {
        let expandedKeys = {};
        const expandAll = (nodes) => {
            nodes.forEach((node) => {
                expandedKeys[node.key] = true;
                if (node.children) {
                    expandAll(node.children);
                }
            });
        };
        expandAll(nodes);
        return expandedKeys;
    };

    useEffect(() => {
        refreshTree();
    }, []);

    const editRow = (data, key) => {
        if (data.type === "product" && !!data.id) {
            window.location.href = `${productCrudList}/${data.id}/edit`;
        } else {
            setCurrentKey(key);
            setEditingRow({ ...data });
        }
    };

    const cancelEdit = (key) => {
        if (!!!editingRow.id || editingRow.id === 0) {
            let parentNode = getParentNode(key);
            let currentNodes = !!parentNode ? parentNode.children : nodes;
            for (let index = 0; index < currentNodes.length; index++) {
                const node = currentNodes[index];
                if (node.key === key) {
                    if (!!parentNode) {
                        parentNode.children[
                            parentNode.children.length - 1
                        ].key = key;
                        parentNode.children.splice(index, 1);
                    } else {
                        nodes[nodes.length - 1].key = key;
                        nodes.splice(index, 1);
                    }
                    break;
                }
            }
        }
        setCurrentKey("-1");
        setEditingRow({});
        setNodes([...nodes]);
    };

    const handleEditorChange = (value, key) => {
        setEditingRow({ ...editingRow, [key]: value });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        event.stopPropagation();
        const form = event.currentTarget;
        if (form.checkValidity() === false) {
            setValidated(true);
            form.classList.add("was-validated");
            return;
        }
        let editedNode = findNodeByKey(nodes, currentKey);
        for (var key in editingRow) {
            editedNode.data[key] = editingRow[key];
        }
        let url = JSON.parse(
            rootElement.getAttribute(`${editedNode.data.type}-url`)
        );
        let parentNode = getParentNode(editedNode.key);
        if (editedNode.data.parentKey !== "parent_id")
            editedNode.data["parent_id"] = null;
        if (!!parentNode) {
            editedNode.data[editedNode.data.parentKey] = parentNode.data.id;
            let parent2 = getParentNode(parentNode.key);
            while (!!parent2 && parent2.data.type !== "category") {
                parent2 = getParentNode(parent2.key);
            }
            if (!!parent2) {
                editedNode.data["category_id"] = parent2.data.id;
            }
        }
        try {
            const apiUrl = new URL(url);
            apiUrl.searchParams.append("show_in_menu", "1");
            const response = await axios.post(
                apiUrl.toString(),
                editedNode.data
            );
            if (response.data.message !== "Done") {
                setShowAlert(true);
                Swal.fire({
                    show: showAlert,
                    title: "Error",
                    text: translations[response.data.message],
                    icon: "error",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false,
                }).then(() => {
                    setShowAlert(false);
                });
                return;
            }
        } catch (error) {
            console.error(
                "There was an error saving the product!",
                error.response.data
            );
        }
        setEditingRow({});
        setCurrentKey("-1");
        refreshTree();
    };

    const findNodeByKey = (nodes, key) => {
        let path;
        key = key.toString();
        path = key.split("-");

        let node;
        let list = nodes;

        while (path.length) {
            node = list[parseInt(path[0], 10)];
            path.shift();
            list = node ? node.children : null;
        }
        return node;
    };

    const getParentNode = (key) => {
        key = key.toString();
        let seg = key.split("-");
        if (seg.length <= 1) return null;
        let parentKey = seg.slice(0, seg.length - 1).join("-");
        return findNodeByKey(nodes, parentKey);
    };

    const addInline = (
        key,
        type,
        parentKeyName,
        type1,
        parentKeyName1,
        isNew = false,
        extraData = {}
    ) => {
        let parentNode = findNodeByKey(nodes, key);
        let node = findNodeByKey(nodes, key);
        key = key.toString();
        let seg = key.split("-");
        let parentKey =
            seg.length === 1 ? null : seg.slice(0, seg.length - 1).join("-");

        node.data.empty = null;
        node.data.type1 = null;
        node.data.parentKey1 = null;
        node.data.type = type;
        node.data.parentKey =
            !!parentNode && parentNode.data.type === "category"
                ? "category_id"
                : parentKeyName;

        for (const key in defaultObjectValue) {
            node.data[key] = defaultObjectValue[key];
        }

        let newNodeKey = !!!parentKey
            ? Number(seg[0]) + 1
            : parentKey + "-" + (Number(seg[seg.length - 1]) + 1);
        let newNode = {
            key: newNodeKey.toString(),
            data: {
                ...extraData,
                type: type,
                parentKey: parentKeyName,
                type1: type1,
                parentKey1: parentKeyName1,
                empty: "Y",
                isNew: isNew,
            },
        };

        if (type === "variable") {
            newNode.data.name_ar = translations.AddVariable;
            newNode.data.name_en = translations.AddVariable;
        }

        if (!!!parentKey) {
            nodes.push(newNode);
        } else {
            let parentNode = findNodeByKey(nodes, parentKey);
            if (!parentNode.children) {
                parentNode.children = [];
            }
            parentNode.children.push(newNode);
        }

        setExpandedKeys({ ...expandedKeys, [key]: true, [parentKey]: true });
        setCurrentKey(key);
        setNodes([...nodes]);
        setEditingRow({ ...node.data, ...extraData });
    };

    const renderTextCell = (node, key, autoFocus) => {
        let indent = node.key.toString().split("-").length;

        if (node.data.empty === "Y" && key === "name_ar") {
            let addText = "";
            let newType = node.data.type;
            let newParentKey = node.data.parentKey;
            let parentNode = getParentNode(node.key);

            if (parentNode && parentNode.data.type === "variable") {
                newType = "product";
                newParentKey = "parent_id";
                //addText = `${translations.Add} ${translations.product}`;
                const parentName =
                    translations.language === "ar"
                        ? parentNode.data.name_ar
                        : parentNode.data.name_en;
                addText = `${translations.Add} ${translations.product} ${translations.under} ${parentName}`;

                const subcategoryId = parentNode.data.subcategory_id
                    ? parentNode.data.subcategory_id
                    : null;
                const description_ar = parentNode.data.description_ar;
                const description_en = parentNode.data.description_en;
                const calories = parentNode.data.calories;
                const preparation_time = parentNode.data.preparation_time;
                const tax_id = parentNode.data.tax_id;
                return (
                    <a
                        href="javascript:void(0);"
                        onClick={(e) => {
                            e.stopPropagation();
                            addInline(
                                node.key,
                                newType,
                                newParentKey,
                                node.data.type1,
                                node.data.parentKey1,
                                true,
                                {
                                    subcategory_id: subcategoryId,
                                    description_ar: description_ar,
                                    description_en: description_en,
                                    calories: calories,
                                    preparation_time: preparation_time,
                                    tax_id: tax_id,
                                }
                            );
                        }}
                    >
                        {addText}
                    </a>
                );
            } else if (parentNode && parentNode.data.type === "subcategory") {
                addText = `${translations.Add} ${translations.product}`;
                return (
                    <a
                        href="javascript:void(0);"
                        onClick={(e) => {
                            e.stopPropagation();
                            window.location.href = `${productCrudList}/create?parent_id=${parentNode.data.id}`;
                        }}
                    >
                        {addText}
                    </a>
                );
            } else {
                addText = `${translations.Add} ${translations[node.data.type]}`;
                return (
                    <a
                        href="javascript:void(0);"
                        onClick={(e) => {
                            e.stopPropagation();
                            addInline(
                                node.key,
                                newType,
                                newParentKey,
                                node.data.type1,
                                node.data.parentKey1,
                                true
                            );
                        }}
                    >
                        {addText}
                    </a>
                );
            }
        } else {
            return node.key === currentKey ? (
                <input
                    type="text"
                    className={`form-control text-editor text-indent-${indent}`}
                    style={{ width: `${100 - 10 * indent}%` }}
                    defaultValue={node.data[key]}
                    onChange={(e) => handleEditorChange(e.target.value, key)}
                    autoFocus={!!autoFocus}
                    onKeyDown={(e) => e.stopPropagation()}
                    required
                />
            ) : (
                <span>{node.data[key]}</span>
            );
        }
    };

    const renderCheckCell = (node, key) => {
        return node.key === currentKey ? (
            <div>
                <input
                    type="checkbox"
                    checked={editingRow[key] === 1}
                    className="form-check-input"
                    onChange={(e) =>
                        handleEditorChange(e.target.checked ? 1 : 0, key)
                    }
                />
            </div>
        ) : (
            <div>
                <input
                    type="checkbox"
                    defaultChecked={false}
                    checked={node.data[key] === 1}
                    className="form-check-input"
                    disabled
                />
            </div>
        );
    };

    const renderNumberCell = (node, key, autoFocus, required) => {
        const indent = node.key.toString().split("-").length;
        return node.key === currentKey ? (
            <input
                type="number"
                min="0"
                className={`form-control text-editor number-indent-${indent}`}
                defaultValue={node.data[key]}
                onChange={(e) => handleEditorChange(e.target.value, key)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()}
                style={{ width: "100%", visibility: "hidden" }}
                required={!!required}
                readOnly
            />
        ) : (
            <span>{node.data[key]}</span>
        );
    };

    const renderDecimalCell = (node, key, autoFocus) => {
        const indent = node.key.toString().split("-").length;
        return node.key === currentKey ? (
            <input
                type="number"
                min="0"
                step=".01"
                className={`form-control text-editor number-indent-${indent}`}
                defaultValue={node.data[key]}
                onChange={(e) => handleEditorChange(e.target.value, key)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()}
                style={{ width: "100%" }}
                required
            />
        ) : (
            <span>{node.data[key]}</span>
        );
    };

    const renderDecimalCellPrice = (node, key, autoFocus) => {
        const indent = node.key.toString().split("-").length;
        return node.key === currentKey ? (
            <input
                type="number"
                min="0"
                step=".01"
                className={`form-control text-editor number-indent-${indent}`}
                defaultValue={node.data[key]}
                onChange={(e) => handleEditorChange(e.target.value, key)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()}
                style={{ width: "100%" }}
                required
            />
        ) : (
            <span>{node.data["price_with_tax"]}</span>
        );
    };

    const openDeleteModel = (data) => {
        setUrl(JSON.parse(rootElement.getAttribute(`${data.type}-url`)));
        setCurrentNode(data);
        setIsDeleteModalVisible(true);
    };

    const actionTemplate = (node) => {
        const data = node.data;
        if (data.empty === "Y") {
            return <></>;
        }

        return (
            <div className="flex flex-wrap gap-2">
                {currentKey === "-1" ||
                (currentKey !== "-1" && node.key === currentKey) ? (
                    data.type !== "variable" && (
                        <a
                            href="javascript:void(0);"
                            onClick={() => {
                                if (currentKey === "-1") {
                                    editRow(data, node.key);
                                } else {
                                    document
                                        .getElementById("btnSubmit")
                                        .click();
                                }
                            }}
                            title="Edit"
                            className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
                        >
                            <i
                                className={
                                    currentKey !== "-1" &&
                                    node.key === currentKey
                                        ? "ki-outline ki-check fs-2"
                                        : "ki-outline ki-pencil fs-2"
                                }
                            ></i>
                        </a>
                    )
                ) : (
                    <></>
                )}
                {currentKey !== "-1" && node.key === currentKey ? (
                    <a
                        href="javascript:void(0);"
                        onClick={(e) => cancelEdit(currentKey)}
                        className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                        style={{
                            display:
                                data.type === "variable" ? "none" : "block",
                        }}
                    >
                        <i className="ki-outline ki-cross fs-2"></i>
                    </a>
                ) : null}
                {!data.isNew && data.id && (
                    <a
                        href="javascript:void(0);"
                        onClick={() => openDeleteModel(data)}
                        className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                    >
                        <i className="ki-outline ki-trash fs-2"></i>
                    </a>
                )}
                <button
                    id="btnSubmit"
                    type="submit"
                    style={{ display: "none" }}
                ></button>
            </div>
        );
    };

    const openAddCategory = () => {
        window.location.href = productCrudList + "/create";
    };

    const handleSearch = (value) => {
        setGlobalFilter(value);
        if (value) {
            const allKeys = getExpandedKeys(nodes);
            setExpandedKeys(allKeys);
        } else {
            setExpandedKeys({});
        }
    };

    const getHeader = () => (
        <div className="d-flex justify-content-between align-items-center">
            <input
                type="text"
                className="form-control text-editor"
                onInput={(e) => handleSearch(e.target.value)}
                placeholder={translations.globalFilter}
                style={{ width: "400px" }}
            />
            <div>
                <button
                    className="btn btn-secondary"
                    onClick={expandAll}
                    style={{ margin: "5px" }}
                >
                    {translations.ExpandAll}
                </button>
                <button className="btn btn-secondary" onClick={collapseAll}>
                    {translations.CollapseAll}
                </button>
            </div>
        </div>
    );

    let header = getHeader();

    return (
        <div className="card mb-5 mb-xl-8">
            <SweetAlert2 />
            <div className="card-header border-0 pt-5">
                <h3 className="card-title align-items-start flex-column">
                    <span className="card-label fw-bold fs-3 mb-1">
                        {translations.CategoryList}
                    </span>
                    <span className="text-muted mt-1 fw-semibold fs-7">
                        {translations.ProductList}
                    </span>
                </h3>
                <div className="card-toolbar">
                    <div className="d-flex align-items-center gap-2 gap-lg-3">
                        <a
                            href="javascript:void(0);"
                            className="btn btn-primary"
                            onClick={() => openAddCategory()}
                        >
                            {translations.Add}
                        </a>
                        <DeleteModal
                            visible={isDeleteModalVisible}
                            onClose={handleClose}
                            onDelete={handleDelete}
                            url={url}
                            row={currentNode}
                            translations={translations}
                        />
                    </div>
                </div>
            </div>
            <div className="card-body">
                <form
                    id="treeForm"
                    noValidate
                    validated={true}
                    className="needs-validation"
                    onSubmit={handleSubmit}
                >
                    <TreeTable
                        value={nodes}
                        scrollable
                        scrollHeight="400px"
                        tableStyle={{ minWidth: "50rem" }}
                        className={"custom-tree-table"}
                        globalFilter={globalFilter}
                        header={header}
                        filterMode="lenient"
                        expandedKeys={expandedKeys}
                        onToggle={(e) => setExpandedKeys(e.value)}
                        sortMode="multiple"
                    >
                        <Column
                            field="name_ar"
                            header={translations.name_ar}
                            style={{ width: "20%" }}
                            body={(node) => renderTextCell(node, "name_ar")}
                            sortable
                            expander
                        ></Column>
                        <Column
                            field="name_en"
                            header={translations.name_en}
                            style={{ width: "20%" }}
                            body={(node) =>
                                renderTextCell(node, "name_en", true)
                            }
                            sortable
                        ></Column>
                        <Column
                            header={translations.cost}
                            style={{ width: "10%" }}
                            body={(node) =>
                                node.data.type === "product" ? (
                                    renderDecimalCell(node, "cost")
                                ) : (
                                    <></>
                                )
                            }
                        ></Column>
                        <Column
                            header={translations.priceWithTax}
                            style={{ width: "10%" }}
                            body={(node) =>
                                node.data.type === "product" ||
                                node.data.type === "variable" ? (
                                    renderDecimalCellPrice(node, "price")
                                ) : (
                                    <></>
                                )
                            }
                        ></Column>
                        <Column
                            header={translations.order}
                            style={{ width: "10%" }}
                            body={(node) =>
                                renderNumberCell(node, "order", false, false)
                            }
                        ></Column>
                        <Column
                            header={translations.active}
                            style={{ width: "10%" }}
                            body={(node) => renderCheckCell(node, "active")}
                        ></Column>
                        <Column
                            header={translations.forSell}
                            style={{ width: "10%" }}
                            body={(node) =>
                                node.data.type === "product" ||
                                node.data.type === "variable" ? (
                                    renderCheckCell(node, "for_sell")
                                ) : (
                                    <></>
                                )
                            }
                        ></Column>
                        <Column
                            style={{ width: "10%" }}
                            body={(node) => actionTemplate(node)}
                        />
                    </TreeTable>
                </form>
            </div>
        </div>
    );
};

export default TreeTableProduct;

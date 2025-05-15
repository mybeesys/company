import React, { useState, useEffect } from "react";
import { TreeTable } from "primereact/treetable";
import { Column } from "primereact/column";
import DeleteModal from "../product/DeleteModal";
import axios from "axios";
import SweetAlert2 from "react-sweetalert2";

const defaultObjectValue = { active: 1 };
const TreeTableAttribute = ({ urlList, rootElement, translations }) => {
    const [nodes, setNodes] = useState([]);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [url, setUrl] = useState("");
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState("-1");
    const [showAlert, setShowAlert] = useState(false);
    const [currentNode, setCurrentNode] = useState({});
    const [validated, setValidated] = useState(false);

    const handleDelete = (message) => {
        if (message != "Done") {
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
                setShowAlert(false); // Reset the state after alert is dismissed
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
            const response = axios.get(urlList).then((response) => {
                let result = response.data;
                setNodes(result);
            });
        } catch (error) {
            console.error("There was an error get the product!", error);
        }
    };

    useEffect(() => {
        refreshTree();
    }, []);

    const editRow = (data, key) => {
        setCurrentKey(key);
        setEditingRow({ ...data });
    };

    const cancelEdit = (key) => {
        if (!!!editingRow.id || editingRow.id == 0) {
            let parentNode = getParentNode(key);
            let currentNodes = !!parentNode ? parentNode.children : nodes;
            for (let index = 0; index < currentNodes.length; index++) {
                const node = currentNodes[index];
                if (node.key == key) {
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
    };

    const handleEditorChange = (value, key) => {
        editingRow[key] = value;
        setEditingRow({ ...editingRow });
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
        if (!!parentNode)
            editedNode.data[editedNode.data.parentKey] = parentNode.data.id;
        const response = await axios.post(url, editedNode.data);
        if (response.data.message != "Done") {
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
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            return;
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

        while (path.length) {
            let list = node ? node.children : nodes;

            node = list[parseInt(path[0], 10)];
            path.shift();
        }

        return node;
    };

    const getParentNode = (key) => {
        key = key.toString();
        let seg = key.split("-");
        let parentKey = seg.length == 1 ? null : seg[0];
        for (let index = 1; index < seg.length - 1; index++) {
            parentKey = parentKey + "-" + seg[index];
        }
        if (!!!parentKey) return null;
        else return findNodeByKey(nodes, parentKey);
    };

    const addInline = (key, type, parentKeyName) => {
        let node = findNodeByKey(nodes, key);
        key = key.toString();
        let seg = key.split("-");
        let parentKey = seg.length == 1 ? null : seg[0];
        for (let index = 1; index < seg.length - 1; index++) {
            parentKey = parentKey + "-" + seg[index];
        }
        node.data.empty = null;
        for (const key in defaultObjectValue) {
            node.data[key] = defaultObjectValue[key];
        }
        let newNode = {
            key: !!!parentKey
                ? Number(seg[0]) + 1
                : parentKey + "-" + (Number(seg[seg.length - 1]) + 1),
            data: { type: type, parentKey: parentKeyName, empty: "Y" },
        };
        if (!!!parentKey) nodes.push(newNode);
        else {
            let parentNode = findNodeByKey(nodes, parentKey);
            parentNode.children.push(newNode);
        }
        setCurrentKey(key);
        setNodes([...nodes]);
        setEditingRow({ ...node.data });
    };

    const renderTextCell = (node, key, autoFocus) => {
        const indent = node.key.toString().split("-").length;
        if (key == "name_en" && !!node.data.empty) {
            return (
                <a
                    href="#"
                    onClick={(e) =>
                        addInline(node.key, node.data.type, node.data.parentKey)
                    }
                >{`${translations.Add} ${translations[node.data.type]}`}</a>
            );
        } else {
            return node.key == currentKey ? (
                <input
                    type="text"
                    class={`form-control text-editor text-indent-${indent}`}
                    style={{ width: `${100 - 10 * indent}%!important` }}
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

    const renderCheckCell = (node, key, autoFocus) => {
        return node.key == currentKey ? (
            <div>
                <input
                    type="checkbox"
                    checked={editingRow[key] == 1 ? true : false}
                    class="form-check-input"
                    data-kt-check={node.data[key]}
                    data-kt-check-target=".widget-10-check"
                    onChange={(e) => {
                        handleEditorChange(e.target.checked ? 1 : 0, key);
                    }}
                />
            </div>
        ) : (
            <div>
                <input
                    type="checkbox"
                    defaultChecked={false}
                    checked={node.data[key]}
                    class="form-check-input"
                    data-kt-check={node.data[key]}
                    data-kt-check-target=".widget-10-check"
                    disabled
                />
            </div>
        );
    };

    const renderNumberCell = (node, key, autoFocus) => {
        const indent = node.key.toString().split("-").length;
        return node.key == currentKey ? (
            <input
                type="number"
                min="0"
                class={`form-control text-editor number-indent-${indent}`}
                defaultValue={node.data[key]}
                onChange={(e) => handleEditorChange(e.target.value, key)}
                autoFocus={!!autoFocus}
                onKeyDown={(e) => e.stopPropagation()}
                style={{ width: "100%", visibility: "hidden" }}
                readOnly
            />
        ) : (
            <span>{node.data[key]}</span>
        );
    };

    const renderDecimalCell = (node, key, autoFocus) => {
        const indent = node.key.toString().split("-").length;
        return node.key == currentKey ? (
            <input
                type="number"
                min="0"
                step=".01"
                class={`form-control text-editor number-indent-${indent}`}
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

    const openDeleteModel = (data) => {
        setUrl(JSON.parse(rootElement.getAttribute(`${data.type}-url`)));
        setCurrentNode(data);
        setIsDeleteModalVisible(true);
    };

    const actionTemplate = (node) => {
        const data = node.data;
        return !!!node.data.empty && node.data.empty != "Y" ? (
            <div className="flex flex-wrap gap-2">
                {currentKey == "-1" ||
                (currentKey != "-1" && node.key == currentKey) ? (
                    <a
                        href="#"
                        onClick={() => {
                            if (currentKey == "-1") editRow(data, node.key);
                            else {
                                let btnSubmit =
                                    document.getElementById("btnSubmit");
                                btnSubmit.click();
                            }
                        }}
                        title="Edit"
                        class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
                    >
                        <i
                            class={
                                currentKey != "-1" && node.key == currentKey
                                    ? "ki-outline ki-check fs-2"
                                    : "ki-outline ki-pencil fs-2"
                            }
                        ></i>
                    </a>
                ) : (
                    <></>
                )}
                {currentKey != "-1" ? (
                    <a
                        href="#"
                        onClick={(e) => cancelEdit(currentKey)}
                        class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                    >
                        <i class="ki-outline ki-cross fs-2"></i>
                    </a>
                ) : null}
                <a
                    href="#"
                    onClick={() => openDeleteModel(data)}
                    class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                >
                    <i class="ki-outline ki-trash fs-2"></i>
                </a>
                <button
                    id="btnSubmit"
                    type="submit"
                    style={{ display: "none" }}
                ></button>
            </div>
        ) : (
            <></>
        );
    };

    return (
        <div class="card mb-5 mb-xl-8">
            <SweetAlert2 />

            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">
                        {translations.AttributeList}
                    </span>
                </h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
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
            <div class="card-body">
                <form
                    id="treeForm"
                    noValidate
                    validated={true}
                    class="needs-validation"
                    onSubmit={handleSubmit}
                >
                    <TreeTable
                        value={nodes}
                        tableStyle={{ minWidth: "50rem" }}
                        className={"custom-tree-table"}
                    >
                        <Column
                            header={translations.name_en}
                            style={{ width: "20%" }}
                            body={(node) =>
                                renderTextCell(node, "name_en", true)
                            }
                            sortable
                            expander
                        ></Column>
                        <Column
                            header={translations.name_ar}
                            style={{ width: "20%" }}
                            body={(node) => renderTextCell(node, "name_ar")}
                            sortable
                        ></Column>
                        <Column
                            header={translations.order}
                            style={{ width: "10%" }}
                            body={(node) => renderNumberCell(node, "order")}
                            sortable
                        ></Column>
                        <Column
                            header={translations.active}
                            style={{ width: "10%" }}
                            body={(node) => renderCheckCell(node, "active")}
                            sortable
                        >
                            {" "}
                        </Column>
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

export default TreeTableAttribute;

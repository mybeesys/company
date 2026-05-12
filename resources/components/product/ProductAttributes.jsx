import React, { useState, useCallback } from "react";
import Select from "react-select";
import { Button } from "primereact/button";
import { Dropdown, Form } from "react-bootstrap";
import ConfirmationModal from "./ConfirmationModal";
import SweetAlert2 from "react-sweetalert2";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";
import { colors } from "laravel-mix/src/Log";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();
const ProductAttribute = ({
    translations,
    onChange,
    product,
    AttributesTree,
}) => {
    const rootElement = document.getElementById("root");
    let dir = rootElement.getAttribute("dir");
    const [Attributes1, setAttributes1] = useState([]);
    const [Attributes2, setAttributes2] = useState([]);
    const [AttributeClass1, setAttributeClass1] = useState([]);
    const [AttributeClass2, setAttributeClass2] = useState(-1);
    const [editingRow, setEditingRow] = useState({});
    const [currentKey, setCurrentKey] = useState("-1");
    const [disableButton, setdisableButton] = useState(true);
    const [disableAttributeClass2, setdisableAttributeClass2] = useState(false);
    setdisableAttributeClass2;
    const [showAlert, setShowAlert] = useState(false);
    const [autoObject, setAutoObject] = useState({
        price: false,
        SKU: false,
        barcode: false,
        starting: 0,
        startingCheck: false,
    });
    const [showConfirmation, setshowConfirmation] = useState(false);
    const handleMultiSelectChange = (selectedOptions) => {
        const next = selectedOptions || [];
        setAttributeClass1(next);
        setAttributes1(findChildrenById(next));
        setdisableButton(next.length === 0);
    };
    const onCloseConfirm = (event) => {
        event.preventDefault();
        setshowConfirmation(false);
    };
    const generateSKU = () => {
        const randomNum = Math.floor(Math.random() * 100000);
        return String(randomNum).padStart(5, "0");
    };
    const generateUPCA = () => {
        let data = "";
        for (let i = 0; i < 11; i++) {
            data += Math.floor(Math.random() * 10).toString();
        }
        let oddSum = 0;
        let evenSum = 0;

        for (let i = 0; i < 11; i++) {
            if (i % 2 === 0) {
                oddSum += parseInt(data[i]);
            } else {
                evenSum += parseInt(data[i]);
            }
        }

        const total = oddSum * 3 + evenSum;
        const checkDigit = (10 - (total % 10)) % 10;

        return data + checkDigit;
    };
    const findChildrenById = (ids) => {
        const children = [];
        if (ids == null || ids === -1 || ids === "-1") {
            return children;
        }
        let idList = ids;
        if (!Array.isArray(idList)) {
            idList = [idList];
        }

        idList.forEach((rawId) => {
            const id =
                rawId && typeof rawId === "object" && "value" in rawId
                    ? rawId.value
                    : rawId;
            const item = AttributesTree.find(
                (node) => Number(node?.data?.id) === Number(id)
            );
            if (item && item.children) {
                children.push(...item.children);
            }
        });
        return children;
    };

    const parentIdsToClass1Options = (parentIds) => {
        if (!Array.isArray(parentIds) || parentIds.length === 0) {
            return [];
        }
        return parentIds
            .map((raw) => {
                const id =
                    raw && typeof raw === "object" && "value" in raw
                        ? raw.value
                        : raw;
                const node = AttributesTree.find(
                    (n) => Number(n?.data?.id) === Number(id)
                );
                if (!node?.data?.id) {
                    return null;
                }
                return {
                    label:
                        dir === "rtl"
                            ? node.data.name_ar
                            : node.data.name_en,
                    value: node.data.id,
                    name_ar: node.data.name_ar,
                    name_en: node.data.name_en,
                    id: node.data.id,
                };
            })
            .filter(Boolean);
    };

    const onConfirm = (evt) => {
        evt.preventDefault();
        var newMstrix = [];
        var id = 0;

        // Extract IDs from the selected options
        const attributeClass1Ids = AttributeClass1.map(
            (option) => option.value
        );

        if (AttributeClass2 !== -1) {
            var Children1 = findChildrenById(attributeClass1Ids);
            var Children2 = findChildrenById(AttributeClass2);

            Children1.forEach((element1) => {
                Children2.forEach((element2) => {
                    let newObject = {};
                    if (
                        element1.data.empty !== "Y" &&
                        element2.data.empty !== "Y"
                    ) {
                        newObject.name_ar =
                            product.name_ar +
                            " " +
                            element1.data.name_ar +
                            " " +
                            element2.data.name_ar;
                        newObject.name_en =
                            product.name_en +
                            " " +
                            element1.data.name_en +
                            " " +
                            element2.data.name_en;
                        newObject.attribute1 = {
                            name_ar: element1.data.name_ar,
                            name_en: element1.data.name_en,
                            parent_id: attributeClass1Ids,
                            id: element1.data.id,
                        };
                        newObject.attribute2 = {
                            name_ar: element2.data.name_ar,
                            name_en: element2.data.name_en,
                            parent_id: AttributeClass2,
                            id: element2.data.id,
                        };
                        newObject.price = autoObject.price ? product.price : 0;
                        newObject.barcode = autoObject.barcode
                            ? product.barcode
                            : "";
                        newObject.SKU = autoObject.SKU ? product.SKU : "";
                        newObject.starting = autoObject.startingCheck
                            ? autoObject.starting
                            : 0;
                        newObject.id = id + 1;
                        id++;
                        newMstrix.push(newObject);
                    }
                });
            });
        } else {
            var Children1 = findChildrenById(attributeClass1Ids);
            Children1.forEach((element1) => {
                if (element1.data.empty !== "Y") {
                    let newObject = {
                        name_ar: product.name_ar + " " + element1.data.name_ar,
                        name_en: product.name_en + " " + element1.data.name_en,
                        attribute1: {
                            name_ar:
                                product.name_ar + " " + element1.data.name_ar,
                            name_en:
                                product.name_en + " " + element1.data.name_en,
                            parent_id: attributeClass1Ids,
                            id: element1.data.id,
                        },
                        price: autoObject.price ? product.price_with_tax : 0,
                        barcode: autoObject.barcode ? generateUPCA() : "",
                        SKU: autoObject.SKU ? generateSKU() : "",
                        /* starting: autoObject.startingCheck
                            ? autoObject.starting
                            : 0, */
                        id: id + 1,
                    };

                    id++;
                    newMstrix.push(newObject);
                }
            });
        }

        onChange("attributes", newMstrix);
        setshowConfirmation(false);
    };

    const handleChange = async (key, value) => {
        if (key == "AttributeClass1") {
            setAttributeClass1(value);
            setAttributes1(findChildrenById(value));
        } else {
            setAttributeClass2(value);
            setAttributes2(findChildrenById(value));
        }
        if (key == "AttributeClass1" && value != -1) {
            setdisableButton(false);
            setdisableAttributeClass2(false);
        } else if (key == "AttributeClass1" && value == -1) {
            setdisableButton(true);
            setAttributeClass2("-1");
            setdisableAttributeClass2(true);
            setAttributes2([]);
            onGenerate([]);
        }
    };

    const handleAutoChange = (key, value) => {
        autoObject[key] = value;
        setAutoObject({ ...autoObject });
    };

    const generateNewMatrix = (evt) => {
        evt.preventDefault();
        if (!product.name_ar || !product.name_en) {
            Swal.fire({
                title: "Error",
                text: translations.productNameRequired,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            });
            return; // Stop the function from proceeding
        }
        if (AttributeClass1.length > 0) {
            setshowConfirmation(true);
        } else {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                text: translations.att1andatt2,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
        }
    };

    React.useEffect(() => {
        const classOptions = (AttributesTree || []).filter(
            (option) => option?.data?.id && option.data.empty !== "Y"
        );
        if (classOptions.length === 0) {
            return;
        }

        const productMatrix = product.attributes || [];

        productMatrix[0]
            ? !!productMatrix[0].attribute1
                ? setAttributes1(
                      findChildrenById(productMatrix[0].attribute1.parent_id)
                  )
                : setAttributes1([])
            : setAttributes1([]);

        productMatrix[0]
            ? !!productMatrix[0].attribute2
                ? setAttributes2(
                      findChildrenById(productMatrix[0].attribute2.parent_id)
                  )
                : setAttributes2([])
            : setAttributes2([]);

        const fallbackClass2 = classOptions[1]?.data?.id ?? -1;

        if (productMatrix[0]?.attribute1?.parent_id) {
            const restoredClass1 = parentIdsToClass1Options(
                productMatrix[0].attribute1.parent_id
            );
            setAttributeClass1(restoredClass1);
            setdisableButton(restoredClass1.length === 0);
        } else {
            setAttributeClass1([]);
            setdisableButton(true);
        }

        productMatrix[0]
            ? !!productMatrix[0].attribute2
                ? setAttributeClass2(productMatrix[0].attribute2.parent_id)
                : setAttributeClass2(
                      classOptions.length > 1 ? fallbackClass2 : -1
                  )
            : setAttributeClass2(-1);
    }, [AttributesTree, product?.id]);

    const handleDelete = (row) => {
        const attrs = product.attributes || [];
        const index = attrs.findIndex((x) => x.id == row.id);
        if (index < 0) {
            return { message: "Done" };
        }
        if (attrs[index]["deleted"] == 1) {
            attrs[index]["deleted"] = 0;
        } else {
            attrs[index]["deleted"] = 1;
        }
        onChange("attributes", attrs);
        return { message: "Done" };
    };

    // const handleActiveDeactiveMatrix = (id) => {
    //   var editedMatrix = [...productMatrix];
    //   var index = 0;
    //   for (let i = 0; i < editedMatrix.length; i++) {
    //     if (editedMatrix[i].id == id) {
    //       break;
    //     }
    //     index = index + 1;
    //   }
    //   if (editedMatrix[index]['deleted'] == 1)
    //     editedMatrix[index]['deleted'] = 0;
    //   else
    //     editedMatrix[index]['deleted'] = 1;

    //   setProductMatrix([...editedMatrix]);
    // }

    return (
        <>
            <SweetAlert2 />
            <ConfirmationModal
                visible={showConfirmation}
                onClose={onCloseConfirm}
                onConfirm={onConfirm}
                message={translations.MatrixConfirmation}
                translations={translations}
            ></ConfirmationModal>

            <div class="card-body" dir={dir}>
                <form>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-6">
                                <label
                                    for="attributeSet"
                                    class="col-form-label"
                                >
                                    {translations.attr_set1}
                                </label>
                                <Select
                                    isMulti
                                    options={AttributesTree.filter(
                                        (option) =>
                                            option?.data?.id &&
                                            option.data.empty !== "Y"
                                    ).map((option) => ({
                                        label:
                                            dir === "rtl"
                                                ? option.data.name_ar
                                                : option.data.name_en,
                                        value: option.data.id,
                                        name_ar: option.data.name_ar,
                                        name_en: option.data.name_en,
                                        id: option.data.id,
                                    }))}
                                    value={AttributeClass1}
                                    onChange={handleMultiSelectChange}
                                    components={animatedComponents}
                                    className="basic-multi-select"
                                    classNamePrefix="select"
                                />
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <div class="row" style={{ padding: "5px" }}>
                        <div class="col-6"></div>
                        <div class="col-6">
                            <div class="row">
                                <div class="col-8">
                                    <Button
                                        className="GenaerateButton btn btn-primary"
                                        variant="primary"
                                        disabled={disableButton}
                                        onClick={(e) => generateNewMatrix(e)}
                                    >
                                        {translations.generateMatrix}
                                    </Button>
                                </div>
                                <div class="col-4">
                                    <Dropdown>
                                        <Dropdown.Toggle
                                            disabled={disableButton}
                                            className="GenaerateButton"
                                            variant="sec"
                                            id="dropdown-basic"
                                        >
                                            {translations.autofill}
                                        </Dropdown.Toggle>
                                        <Dropdown.Menu
                                            style={{ padding: "10px" }}
                                        >
                                            <Form.Group
                                                className="mb-3"
                                                controlId="formBasicCheckbox"
                                            >
                                                <Form.Check
                                                    type="checkbox"
                                                    label={translations.price}
                                                    checked={
                                                        !!autoObject.price
                                                            ? autoObject.price
                                                            : false
                                                    }
                                                    onChange={(e) =>
                                                        handleAutoChange(
                                                            "price",
                                                            e.target.checked
                                                        )
                                                    }
                                                />
                                            </Form.Group>
                                            <Dropdown.Divider />
                                            <Form.Group
                                                className="mb-3"
                                                controlId="formBasicCheckbox"
                                            >
                                                <Form.Check
                                                    type="checkbox"
                                                    label={translations.barcode}
                                                    checked={
                                                        !!autoObject.barcode
                                                            ? autoObject.barcode
                                                            : false
                                                    }
                                                    onChange={(e) =>
                                                        handleAutoChange(
                                                            "barcode",
                                                            e.target.checked
                                                        )
                                                    }
                                                />
                                            </Form.Group>
                                            <Dropdown.Divider />
                                            <Form.Group
                                                className="mb-3"
                                                controlId="formBasicCheckbox"
                                            >
                                                <Form.Check
                                                    type="checkbox"
                                                    label={translations.SKU}
                                                    checked={
                                                        !!autoObject.SKU
                                                            ? autoObject.SKU
                                                            : false
                                                    }
                                                    onChange={(e) =>
                                                        handleAutoChange(
                                                            "SKU",
                                                            e.target.checked
                                                        )
                                                    }
                                                />
                                            </Form.Group>
                                            <Dropdown.Divider />
                                        </Dropdown.Menu>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                    <TreeTableEditorLocal
                        translations={translations}
                        dir={dir}
                        header={false}
                        addNewRow={false}
                        type={"attribute"}
                        title={translations.recipe}
                        currentNodes={product.attributes || []}
                        defaultValue={{}}
                        cols={[
                            {
                                key: "name_ar",
                                autoFocus: true,
                                type: "Text",
                                width: "30%",
                                editable: true,
                                required: true,
                            },
                            {
                                key: "name_en",
                                autoFocus: true,
                                type: "Text",
                                width: "25%",
                                editable: true,
                                required: true,
                            },
                            {
                                key: "price",
                                autoFocus: true,
                                type: "Decimal",
                                width: "30%",
                                editable: true,
                                required: true,
                            },
                            {
                                key: "barcode",
                                autoFocus: true,
                                type: "Text",
                                width: "25%",
                                editable: true,
                                required: false,
                            },
                            {
                                key: "SKU",
                                autoFocus: true,
                                type: "Text",
                                width: "25%",
                                editable: true,
                                required: false,
                            },
                        ]}
                        actions={[
                            {
                                execute: (data) => handleDelete(data),
                                customRender: (data) =>
                                    data.deleted == 1 ? (
                                        <i class={`ki-outline ki-plus fs-2`} />
                                    ) : (
                                        <i class={`ki-outline ki-trash fs-2`} />
                                    ),
                            },
                        ]}
                        onUpdate={(nodes) => onChange("attributes", nodes)}
                        onDelete={null}
                    />
                </div>
            </div>
        </>
    );
};

export default ProductAttribute;

import React, { useState, useCallback } from "react";
import ProductBasicInfo from "./ProductBasicInfo";
import ProductDisplay from "./ProductDisplay";
import ProductAttributes from "./ProductAttributes";
import ProductModifier from "./ProductModifier";
import ProductRecipe from "./ProductRecipe";
import axios from "axios";
import SweetAlert2 from "react-sweetalert2";
import ProductCombo from "./ProductCombo";
import ProductLinkedCombo from "./ProductLinkedCombo";
import UnitTransferProduct from "./UnitTransferProduct";
import ProductEstablishment from "./ProductEstablishment";
import ProductPriceTier from "./ProductPriceTier";

const ProductComponent1 = ({ translations, dir }) => {
    const rootElement = document.getElementById("root");
    const producturl = JSON.parse(rootElement.getAttribute("product-url"));
    const categoryurl = JSON.parse(rootElement.getAttribute("category-url"));
    const modifierClassUrl = JSON.parse(
        rootElement.getAttribute("listModifier-url")
    );
    let product = JSON.parse(rootElement.getAttribute("product"));
    const [AttributesTree, setAttributesTree] = useState([
        { data: {} },
        { data: {} },
    ]);
    const [currentObject, setcurrentObject] = useState(product);
    const [units, setUnits] = useState([]);
    const [productUnit, setProductUnit] = useState();
    const [unitTransfer, setUnitTransfers] = useState(
        !!product.unitTransfer ? product.unitTransfer : []
    );
    const [currentTab, setCurrentTab] = useState(1);
    const [defaultMenu, setdefaultMenu] = useState([
        { key: "basicInfo", visible: true },
        { key: "printInfo", visible: true },
        { key: "priceTier", visible: true },
        { key: "modifiers", visible: !!currentObject.for_sell },
        { key: "recipe", visible: true },
        { key: "groupCombo", visible: !!currentObject.for_sell },
        //{ key: 'linkedCombo', visible: false },
        { key: "inventory", visible: true },
        { key: "Unit", visible: true },
        { key: "advancedInfo", visible: !!currentObject.for_sell },
    ]);
    const [menu, setMenu] = useState(defaultMenu);
    const [ingredientTree, setIngredientTree] = useState([]);
    const [categories, setCategories] = useState([]);
    const [showAlert, setShowAlert] = useState(false);
    const [currentModifiers, setcurrentModifiers] = useState(
        !!product.modifiers ? product.modifiers : []
    );
    const [disableSubmitButton, setSubmitdisableButton] = useState(false);
    const [productLOVs, setProductLOVs] = useState({
        productForComboLOV: [],
        linkedComboPromptLOV: [],
        linkedComboLOV: [],
    });

    const parentHandlechanges = (childproduct) => {
        if (childproduct.for_sell != currentObject.for_sell)
            handleForSellChange(childproduct);
        setcurrentObject({ ...childproduct });
    };

    const clickSubmit = () => {
        let btnSubmit = document.getElementById("btnMainSubmit");
        btnSubmit.click();
    };

    const handleMainSubmit = (event) => {
        event.preventDefault();
        event.stopPropagation();
        const form = event.currentTarget;
        if (form.checkValidity() === false) {
            form.classList.add("was-validated");
            return;
        }
        if (!validProduct()) return;
        else {
            saveChanges();
        }
    };

    const onProductFieldChange = (key, value) => {
        currentObject[key] = value;
        setcurrentObject({ ...currentObject });
        console.log("nn", currentObject);
        return {
            message: "Done",
        };
    };

    const getErrorMessage = (data) => {
        let res = "";
        for (let index = 0; index < data.length; index++) {
            const element = data[index];
            res += `<div>${translations[element]}</div>`;
        }
        return res;
    };

    const handleUniqueError = (data) => {
        setShowAlert(true);
        Swal.fire({
            show: showAlert,
            title: "Error",
            html: `<div>${translations.exist}</div>${getErrorMessage(data)}`,
            icon: "error",
            timer: 4000,
            showCancelButton: false,
            showConfirmButton: false,
        }).then(() => {
            setShowAlert(false); // Reset the state after alert is dismissed
        });
    };

    const saveChanges = async () => {
        try {
            setSubmitdisableButton(true);
            let r = { ...currentObject };
            r["active"] ? (r["active"] = 1) : (r["active"] = 0);
            r["for_sell"] ? (r["for_sell"] = 1) : (r["for_sell"] = 0);
            r["show_in_menu"]
                ? (r["show_in_menu"] = 1)
                : (r["show_in_menu"] = 0);
            r["track_serial_number"]
                ? (r["track_serial_number"] = 1)
                : (r["track_serial_number"] = 0);
            r["sold_by_weight"]
                ? (r["sold_by_weight"] = 1)
                : (r["sold_by_weight"] = 0);
            r["modifiers"] = [...currentModifiers];
            let transfer = [...unitTransfer];

            if (!!productUnit) {
                if (!!!productUnit.id)
                    transfer.push({
                        id: 0,
                        unit1: productUnit.unit1,
                        unit2: -100,
                        transfer: -100,
                        primary: -100,
                    });
                else transfer.push(productUnit); //{ id: 0 , unit1: productUnit , unit2: -100 , transfer: -100 , primary :-100});
            }
            const sortedItems = [...transfer].sort((a, b) => a.id - b.id);
            r["transfer"] = [...sortedItems];

            const response = await axios.post(producturl, r, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });
            if (response.data.message == "Done") {
                window.location.href = categoryurl;
            } else if (response.data.message == "UNIQUE") {
                handleUniqueError(response.data.data);
            } else {
                setShowAlert(true);
                Swal.fire({
                    show: showAlert,
                    title: "Error",
                    text: translations.technicalerror,
                    icon: "error",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false,
                }).then(() => {
                    setShowAlert(false); // Reset the state after alert is dismissed
                });
            }
        } catch (error) {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                text: translations.technicalerror,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            console.error("There was an error adding the product!", error);
        }
        setSubmitdisableButton(false);
    };

    const cancel = () => {
        window.location.href = categoryurl;
    };

    const getName = (name_en, name_ar) => {
        if (dir == "ltr") return name_en;
        else return name_ar;
    };

    const getProductLOVs = async () => {
        const response = await axios.get("/productLOVs/" + product.id);
        const products = response.data.product.map((e) => {
            return { label: getName(e.name_en, e.name_ar), value: e.id };
        });
        const linkedComboPrompts = response.data.prompt.map((e) => {
            return { label: translations[e.name], value: e.value };
        });
        const linkedCombos = response.data.linkedCombo
            .slice(0, response.data.linkedCombo.length - 1)
            .map((e) => {
                return {
                    label: getName(e.data.name_en, e.data.name_ar),
                    value: e.data.id,
                    combos: e.data.combos,
                };
            });

        const category = response.data.category;
        setCategories(category);

        const ingredient = response.data.ingredient.map((e) => {
            return {
                label: getName(e.name_en, e.name_ar),
                value: e.id + e.type,
                cost: e.cost,
            };
        });
        setIngredientTree(ingredient);

        const attribute = response.data.attribute;
        setAttributesTree(attribute);

        const units = response.data.unitTransfer;
        const unitsResult = units.map((e) => {
            return { label: e.unit1, value: e.id };
        });
        setUnits(unitsResult);

        let mainUnit = units.find(function (element) {
            return element.unit2 == null;
        });

        setProductUnit(mainUnit);

        const unitTransfers = response.data.unitTransfer;
        const unitTransfersResult =
            unitTransfers.length > 0
                ? unitTransfers
                      .filter((e) => e.unit2 != null)
                      .map((e) => {
                          return {
                              id: e.id,
                              transfer: e.transfer,
                              unit1: e.unit1,
                              unit2: e.unit2,
                              primary: e.primary,
                              newid: e.newid,
                          };
                      })
                : [];
        //unitTransfersResult.push({ id: -100, unit1: null, unit2: null, primary: false, transfer: null, newid: null });
        setUnitTransfers(unitTransfersResult);

        setProductLOVs({
            productForComboLOV: products,
            linkedComboPromptLOV: linkedComboPrompts,
            linkedComboLOV: linkedCombos,
            ingredient: ingredient,
            attribute: attribute,
        });
    };

    // Clean up object URLs to avoid memory leaks
    React.useEffect(() => {
        getProductLOVs();
    }, []);

    const handleForSellChange = (childproduct) => {
        let currentMenu = [
            { key: "basicInfo", visible: true },
            { key: "printInfo", visible: true },
            { key: "priceTier", visible: true },
            { key: "modifiers", visible: childproduct.for_sell },
            { key: "recipe", visible: true },
            { key: "groupCombo", visible: childproduct.for_sell },
            //{ key: 'linkedCombo', visible: false },
            { key: "inventory", visible: true },
            { key: "Unit", visible: true },
            { key: "advancedInfo", visible: childproduct.for_sell },
        ];
        setMenu([...currentMenu]);
        currentMenu.forEach((m, index) => {
            if (index != 0) {
                var element = document.getElementById(m.key);
                element.classList.remove("active");
            }
        });
        document.getElementById("printInfo").classList.add("active");
        document.getElementById("printInfo").classList.add("show");
        setCurrentTab(1);
    };

    const parentHandleRecipe = (resultrecipe) => {
        setRecipe([...resultrecipe]);
    };

    const parentHandleTransfer = (result) => {
        setUnitTransfers([...result]);
    };

    const handleModifierChange = (modifierId, key, value) => {
        let modifier = {
            active: 0,
            required: 0,
            default: 0,
            min_modifiers: 0,
            max_modifiers: 0,
            display_order: 0,
            button_display: 0,
            modifier_display: 0,
            product_id: currentObject.id,
            modifier_id: modifierId,
        };
        let m = currentModifiers.filter((m) => m.modifier_id == modifierId);
        if (!!m && !!m.length) {
            modifier = m[0];
            modifier[key] = value;
        } else {
            modifier[key] = value;
            currentModifiers.push(modifier);
        }
        setcurrentModifiers([...currentModifiers]);
    };

    const handleSelectAll = (allModifiers) => {
        let modifier = {
            active: 1,
            required: 0,
            default: 0,
            min_modifiers: 0,
            max_modifiers: 0,
            display_order: 0,
            button_display: 0,
            modifier_display: 0,
            product_id: currentObject.id,
        };
        allModifiers.forEach((m) => {
            if (
                currentModifiers.filter((x) => x.modifier_id == m.data.id)
                    .length == 0
            ) {
                modifier.modifier_id = m.data.id;
                currentModifiers.push({ ...modifier });
            }
        });
        setcurrentModifiers([...currentModifiers]);
    };

    const validProduct = () => {
        let errorMessage = null;
        let valid = true;
        if (!!!productUnit || !!!productUnit.unit1) {
            valid = false;
            errorMessage = translations.noDefaultUnit;
            document.getElementById("Unit_tab").click();
        }
        if (
            !!currentObject.set_price &&
            !!currentObject.combos &&
            !!currentObject.combos.length
        ) {
            const totalPrice = currentObject.combos.reduce(
                (sum, item) =>
                    sum + (!!item.price ? parseFloat(item.price) : 0),
                0
            );
            if (totalPrice != currentObject.price) {
                valid = false;
                errorMessage = translations.ComboPriceError;
                document.getElementById("groupCombo_tab").click();
            }
        }
        if (
            !!currentObject.group_combo &&
            !!currentObject.linked_combo &&
            currentObject.group_combo == currentObject.linked_combo
        ) {
            valid = false;
            errorMessage = translations.groupComboAndLinkedComboSelected;
            document.getElementById("linkedCombo_tab").click();
        }
        if (!valid) {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                text: errorMessage,
                icon: "error",
                timer: 4000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            return false;
        }
        return true;
    };

    const handleMainUnit = (value) => {
        setProductUnit(value);
    };

    return (
        <div>
            <SweetAlert2 />
            <div class="container">
                <div class="row">
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            <h1>{`${translations.Add} ${translations.product}`}</h1>
                        </div>
                    </div>
                    <div
                        class="col-6"
                        style={{ "justify-content": "end", display: "flex" }}
                    >
                        <div class="flex-center" style={{ display: "flex" }}>
                            <button
                                onClick={clickSubmit}
                                disabled={disableSubmitButton}
                                class="btn btn-primary mx-2"
                                style={{ width: "12rem" }}
                            >
                                {translations.savechanges}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div class="row">
                <form
                    noValidate
                    validated={true}
                    class="needs-validation"
                    onSubmit={handleMainSubmit}
                >
                    <div class="container">
                        <div class="row">
                            <div class="col-sm">
                                <div
                                    class="card"
                                    data-section="contact"
                                    style={{
                                        border: "0",
                                        "box-shadow": "none",
                                    }}
                                >
                                    <div class="container">
                                        <ProductBasicInfo
                                            visible={menu[0].visible}
                                            translations={translations}
                                            parentHandlechanges={
                                                parentHandlechanges
                                            }
                                            currentObject={currentObject}
                                            saveChanges={saveChanges}
                                            category={categories}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="card-toolbar ">
                                    <ul
                                        class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold"
                                        role="tablist"
                                    >
                                        {menu.map((m, index) => {
                                            return index == 0 || !m.visible ? (
                                                <></>
                                            ) : (
                                                <li
                                                    class="nav-item"
                                                    role="presentation"
                                                >
                                                    <a
                                                        id={`${m.key}_tab`}
                                                        onClick={(e) =>
                                                            setCurrentTab(index)
                                                        }
                                                        class={`nav-link justify-content-center text-active-gray-800 ${
                                                            currentTab == index
                                                                ? "active"
                                                                : ""
                                                        }`}
                                                        data-bs-toggle="tab"
                                                        role="tab"
                                                        href={`#${m.key}`}
                                                        aria-selected="true"
                                                    >
                                                        {translations[m.key]}
                                                    </a>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="printInfo"
                                        class="card-body p-0 tab-pane fade show active"
                                        role="tabpanel"
                                        aria-labelledby="printInfo_tab"
                                    >
                                        <ProductDisplay
                                            translations={translations}
                                            parentHandlechanges={
                                                parentHandlechanges
                                            }
                                            product={currentObject}
                                            saveChanges={saveChanges}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="priceTier"
                                        class="card-body p-0 tab-pane fade show"
                                        role="tabpanel"
                                        aria-labelledby="priceTier_tab"
                                    >
                                        <ProductPriceTier
                                            translations={translations}
                                            dir={dir}
                                            currentObject={currentObject}
                                            onBasicChange={onProductFieldChange}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content ">
                                    <div
                                        id="advancedInfo"
                                        class="card-body p-0 tab-pane fade show"
                                        role="tabpanel"
                                        aria-labelledby="advancedInfo_tab"
                                    >
                                        {
                                            <ProductAttributes
                                                translations={translations}
                                                parentHandlechanges={
                                                    parentHandlechanges
                                                }
                                                product={currentObject}
                                                saveChanges={saveChanges}
                                                AttributesTree={AttributesTree}
                                                onChange={onProductFieldChange}
                                                //onActiveDeactiveMatrix={handleActiveDeactiveMatrix}
                                                //onGenerate={handleGenerateMatrix}
                                            />
                                        }
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="modifiers"
                                        class="card-body p-0 tab-pane fade show"
                                        role="tabpanel"
                                        aria-labelledby="modifiers_tab"
                                    >
                                        <ProductModifier
                                            translations={translations}
                                            productId={currentObject.id}
                                            productModifiers={currentModifiers}
                                            urlList={modifierClassUrl}
                                            onChange={handleModifierChange}
                                            onSelectAll={handleSelectAll}
                                        />
                                    </div>
                                </div>

                                <div class="tab-content">
                                    <div
                                        id="recipe"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="recipe_tab"
                                    >
                                        <ProductRecipe
                                            translations={translations}
                                            product={currentObject}
                                            productRecipe={currentObject.recipe}
                                            ingredientTree={ingredientTree}
                                            onBasicChange={onProductFieldChange}
                                            dir={dir}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="groupCombo"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="groupCombo_tab"
                                    >
                                        <ProductCombo
                                            translations={translations}
                                            product={currentObject}
                                            onComboChange={onProductFieldChange}
                                            products={
                                                productLOVs.productForComboLOV
                                            }
                                            dir={dir}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="linkedCombo"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="linkedCombo_tab"
                                    >
                                        <ProductLinkedCombo
                                            translations={translations}
                                            product={currentObject}
                                            onComboChange={onProductFieldChange}
                                            pormpts={
                                                productLOVs.linkedComboPromptLOV
                                            }
                                            linkedCombos={
                                                productLOVs.linkedComboLOV
                                            }
                                            products={
                                                productLOVs.productForComboLOV
                                            }
                                            dir={dir}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="inventory"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="inventory_tab"
                                    >
                                        <ProductEstablishment
                                            translations={translations}
                                            dir={dir}
                                            currentObject={currentObject}
                                            onBasicChange={onProductFieldChange}
                                        />
                                    </div>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="Unit"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="Unit_tab"
                                    >
                                        <UnitTransferProduct
                                            translations={translations}
                                            product={currentObject}
                                            unitTransfer={unitTransfer}
                                            unitTree={units}
                                            parentHandle={parentHandleTransfer}
                                            handleMainUnit={handleMainUnit}
                                            productUnit={productUnit}
                                            dir={dir}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="submit" id="btnMainSubmit" hidden></input>
                </form>
            </div>
        </div>
    );
};

export default ProductComponent1;

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
import { formatDecimal } from "../lang/Utils";

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
        { key: "Unit", visible: true },
        { key: "priceTier", visible: true },
        { key: "inventory", visible: true },
        { key: "printInfo", visible: true },

        { key: "modifiers", visible: !!currentObject.for_sell },
        { key: "advancedInfo", visible: !!currentObject.for_sell },

        { key: "group", visible: !!currentObject.for_sell },
        { key: "recipe", visible: true },
        //{ key: 'linkedCombo', visible: false },
    ]);
    const [menu, setMenu] = useState(defaultMenu);
    const [ingredientTree, setIngredientTree] = useState([]);
    const [categories, setCategories] = useState([]);
    const [showAlert, setShowAlert] = useState(false);
    const [currentModifiers, setCurrentModifiers] = useState(
        !!product.modifiers ? product.modifiers : []
    );
    const [disableSubmitButton, setSubmitdisableButton] = useState(false);
    const [productLOVs, setProductLOVs] = useState({
        productForComboLOV: [],
        linkedComboPromptLOV: [],
        linkedComboLOV: [],
    });

    const [selectedEstablishments, setSelectedEstablishments] = useState([]);

    const handleEstablishmentChange = (selectedIds) => {
        setSelectedEstablishments(selectedIds);
    };
    const parentHandlechanges = (childproduct) => {
        const mergedProduct = {
            ...currentObject,
            ...childproduct,
        };

        if (childproduct.for_sell !== currentObject.for_sell) {
            handleForSellChange(mergedProduct);
        }

        setcurrentObject(mergedProduct);
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
            r["establishments"] = selectedEstablishments.map((id) => ({
                id: id,
            }));
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
        const lovId =
            product &&
            product.id !== null &&
            product.id !== undefined &&
            product.id !== ""
                ? product.id
                : null;
        const url =
            lovId === null ? "/productLOVs" : `/productLOVs/${lovId}`;
        let response;
        try {
            response = await axios.get(url);
        } catch (e) {
            console.error("getProductLOVs failed", e);
            setIngredientTree([]);
            setAttributesTree([]);
            setCategories([]);
            return;
        }
        const d = response.data || {};
        const products = (Array.isArray(d.product) ? d.product : []).map(
            (e) => ({ label: getName(e.name_en, e.name_ar), value: e.id })
        );
        const linkedComboPrompts = (
            Array.isArray(d.prompt) ? d.prompt : []
        ).map((e) => ({ label: translations[e.name], value: e.value }));
        const lc = d.linkedCombo;
        const linkedComboList = Array.isArray(lc) ? lc : [];
        const linkedCombos = linkedComboList
            .slice(0, Math.max(0, linkedComboList.length - 1))
            .map((e) => ({
                label: getName(e.data.name_en, e.data.name_ar),
                value: e.data.id,
                combos: e.data.combos,
            }));

        setCategories(d.category || []);

        const ingredient = (
            Array.isArray(d.ingredient) ? d.ingredient : []
        ).map((e) => {
            const value =
                e.recipe_option_value != null && e.recipe_option_value !== ""
                    ? e.recipe_option_value
                    : `${e.id}-i`;
            return {
                label: getName(e.name_en, e.name_ar),
                value,
                cost: e.cost,
            };
        });
        setIngredientTree(ingredient);

        const attribute = Array.isArray(d.attribute) ? d.attribute : [];
        setAttributesTree(attribute);

        const units = Array.isArray(d.unitTransfer) ? d.unitTransfer : [];
        const unitsResult = units.map((e) => ({
            label: e.unit1,
            value: e.id,
        }));
        setUnits(unitsResult);

        const mainUnit = units.find((element) => element.unit2 == null);
        setProductUnit(mainUnit);

        const unitTransfersResult =
            units.length > 0
                ? units
                      .filter((e) => e.unit2 != null)
                      .map((e) => ({
                          id: e.id,
                          transfer:
                              e.transfer != null && e.transfer !== -100
                                  ? formatDecimal(e.transfer)
                                  : e.transfer,
                          unit1: e.unit1,
                          unit2: e.unit2,
                          primary: e.primary,
                          newid: e.newid,
                      }))
                : [];
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

    const handleForSellChange = (mergedProduct) => {
        const forSell = !!mergedProduct.for_sell;
        let currentMenu = [
            { key: "basicInfo", visible: true },
            { key: "Unit", visible: true },
            { key: "priceTier", visible: true },
            { key: "inventory", visible: true },
            { key: "printInfo", visible: true },

            { key: "modifiers", visible: forSell },
            { key: "advancedInfo", visible: forSell },

            { key: "group", visible: forSell },
            { key: "recipe", visible: true },
        ];
        setMenu([...currentMenu]);
        currentMenu.forEach((m, index) => {
            if (index != 0) {
                var element = document.getElementById(m.key);
                element.classList.remove("active");
            }
        });
        document.getElementById("Unit").classList.add("active");
        document.getElementById("Unit").classList.add("show");
        setCurrentTab(1);
    };

    const parentHandleTransfer = (result) => {
        setUnitTransfers([...result]);
    };

    const handleModifierChange = (updatedModifiers) => {
        setCurrentModifiers(updatedModifiers);
    };

    const handleSelectAll = (selectedModifiers) => {
        const allModifiers = selectedModifiers.map((mod) => ({
            ...mod,
            active: 1,
        }));
        setCurrentModifiers(allModifiers);
    };

    const validProduct = () => {
        let errorMessage = null;
        let valid = true;
        // console.log("currentObject", currentObject);
        if (!!!productUnit || !!!productUnit.unit1) {
            valid = false;
            errorMessage = translations.noDefaultUnit;
            document.getElementById("Unit_tab").click();
        }
        if (!!!currentObject.subcategory_id) {
            valid = false;
            errorMessage = translations.subcategoryerror;
        }
        if (currentObject.recipe && currentObject.recipe.length > 0) {
            for (const item of currentObject.recipe) {
                if (!item.unit_transfer || !item.unit_transfer.id) {
                    valid = false;
                    errorMessage = translations.recipeerror;
                    document.getElementById("recipe_tab").click();
                }
            }
        }
        if (
            currentObject.set_price == 0 &&
            !!currentObject.combos &&
            !!currentObject.combos.length
        ) {
            const totalPrice = currentObject.combos.reduce(
                (sum, item) =>
                    sum + (!!item.price ? parseFloat(item.price) : 0),
                0
            );
            if (totalPrice > currentObject.price_with_tax) {
                valid = false;
                errorMessage = translations.ComboPriceError;
                document.getElementById("group_tab").click();
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

    const isEditMode = Boolean(currentObject?.id);
    const pageTitle = isEditMode
        ? `${translations.Edit} ${translations.product}`
        : `${translations.Add} ${translations.product}`;

    const visibleMenuItems = menu
        .map((m, index) => ({ ...m, index }))
        .filter((m) => m.index !== 0 && m.visible);

    const activateTab = (index, key) => {
        setCurrentTab(index);
        menu.forEach((m, i) => {
            if (i === 0) return;
            const pane = document.getElementById(m.key);
            if (pane) {
                pane.classList.remove("active", "show");
            }
        });
        const target = document.getElementById(key);
        if (target) {
            target.classList.add("active", "show");
        }
    };

    return (
        <div>
            <SweetAlert2 />
            <div class="container">
                <div class="row">
                    <div className="col-12 col-lg-6">
                        <div className="d-flex align-items-center gap-2 gap-lg-3">
                            <h1>{pageTitle}</h1>
                        </div>
                    </div>
                    <div className="col-12 col-lg-6 d-flex justify-content-lg-end mt-3 mt-lg-0">
                        <div className="flex-center d-flex w-100 w-lg-auto justify-content-end">
                            <button
                                onClick={clickSubmit}
                                disabled={disableSubmitButton}
                                className="btn btn-primary mx-2 w-100 w-sm-auto"
                                style={{ minWidth: "12rem" }}
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
                            <div className="col-12 col-lg-6">
                                <div
                                    className="card"
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
                            <div className="col-12 col-lg-6 mt-4 mt-lg-0">
                                <div className="card-toolbar product-side-tabs-wrap">
                                    <select
                                        className="form-select form-select-solid d-lg-none mb-3 product-side-tabs-select"
                                        value={currentTab}
                                        onChange={(e) => {
                                            const index = Number(e.target.value);
                                            const item = visibleMenuItems.find(
                                                (m) => m.index === index
                                            );
                                            if (item) {
                                                activateTab(index, item.key);
                                            }
                                        }}
                                        aria-label={pageTitle}
                                    >
                                        {visibleMenuItems.map((m) => (
                                            <option key={m.key} value={m.index}>
                                                {translations[m.key]}
                                            </option>
                                        ))}
                                    </select>
                                    <ul
                                        className="nav nav-tabs nav-line-tabs fs-6 border-0 fw-bold flex-wrap product-side-tabs d-none d-lg-flex"
                                        role="tablist"
                                    >
                                        {visibleMenuItems.map((m) => (
                                            <li
                                                className="nav-item"
                                                role="presentation"
                                                key={m.key}
                                            >
                                                <a
                                                    id={`${m.key}_tab`}
                                                    href={`#${m.key}`}
                                                    role="tab"
                                                    aria-selected={
                                                        currentTab === m.index
                                                    }
                                                    className={`nav-link text-active-gray-800 ${
                                                        currentTab === m.index
                                                            ? "active"
                                                            : ""
                                                    }`}
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        activateTab(
                                                            m.index,
                                                            m.key
                                                        );
                                                    }}
                                                >
                                                    {translations[m.key]}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="Unit"
                                        class="card-body p-0 tab-pane fade show active "
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
                                <div class="tab-content">
                                    <div
                                        id="printInfo"
                                        class="card-body p-0 tab-pane fade show"
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
                                            productId={currentObject?.id || 0}
                                            productModifiers={
                                                currentModifiers || []
                                            }
                                            urlList={modifierClassUrl}
                                            onChange={(updatedModifiers) => {
                                                const formattedModifiers =
                                                    updatedModifiers.map(
                                                        (mod) => ({
                                                            class: {
                                                                ...mod.class,
                                                            },
                                                            modifiers:
                                                                mod.modifiers.map(
                                                                    (m) => ({
                                                                        ...m,
                                                                    })
                                                                ),
                                                        })
                                                    );
                                                handleModifierChange(
                                                    formattedModifiers
                                                );
                                            }}
                                            onSelectAll={(
                                                selectedModifiers
                                            ) => {
                                                handleSelectAll(
                                                    selectedModifiers
                                                );
                                            }}
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
                                        id="group"
                                        class="card-body p-0 tab-pane fade show "
                                        role="tabpanel"
                                        aria-labelledby="group_tab"
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
                                            onEstablishmentChange={
                                                handleEstablishmentChange
                                            }
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

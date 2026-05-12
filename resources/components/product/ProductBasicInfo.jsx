import React, { useState, useCallback } from "react";
import { InputSwitch } from "primereact/inputswitch";
import axios from "axios";
import Select from "react-select";
import makeAnimated from "react-select/animated";
import { getRowName } from "../lang/Utils";

const animatedComponents = makeAnimated();

const allergensList = [
    { value: "eggs", label: "Eggs | بيض", icon: "🍳" },
    { value: "milk", label: "Milk | حليب", icon: "🥛" },
    { value: "fish", label: "Fish | سمك", icon: "🐟" },
    { value: "crustaceans", label: "Crustaceans | قشريات", icon: "🦀" },
    { value: "tree_nuts", label: "Tree Nuts | مكسرات", icon: "🥜" },
    { value: "peanuts", label: "Peanuts | فول سوداني", icon: "🥜" },
    { value: "wheat", label: "Wheat | قمح", icon: "🌾" },
    { value: "soybeans", label: "Soybeans | صويا", icon: "🫘" },
    { value: "sesame", label: "Sesame | سمسم", icon: "🌱" },
    { value: "mustard", label: "Mustard | خردل", icon: "🍯" },
    { value: "celery", label: "Celery | كرفس", icon: "🌿" },
    { value: "lupin", label: "Lupin | ترمس", icon: "🌼" },
    { value: "molluscs", label: "Molluscs | رخويات", icon: "🐚" },
    { value: "sulphites", label: "Sulphites | كبريتات", icon: "🧪" },
];

const ProductBasicInfo = ({
    translations,
    parentHandlechanges,
    currentObject,
    visible,
}) => {
    const rootElement = document.getElementById("root");
    const listCategoryurl = JSON.parse(
        rootElement.getAttribute("listCategory-url")
    );
    const listSubCategoryurl = JSON.parse(
        rootElement.getAttribute("listSubCategory-url")
    );
    const productPermission =
        rootElement.getAttribute("product-permission") ?? "absolute";
    const canAdd = productPermission === "absolute";

    const dir = String(
        document.documentElement.getAttribute("dir") ||
            rootElement.getAttribute("dir") ||
            "ltr"
    )
        .trim()
        .toLowerCase();
    const [categoryOptions, setCategoryOptions] = useState([]);
    const [subcategoryOption, setSubCategoryOptions] = useState([]);

    const fetchCategoryOptions = async () => {
        try {
            let subCategories = [];
            let response = await axios.get(listCategoryurl);
            const categories = response.data.map((category) => ({
                label: getRowName(category, dir),
                value: category.id,
            }));
            if (response.data.length > 0) {
                if (!!!currentObject.category_id) {
                    currentObject["category_id"] = response.data[0].id;
                    currentObject["category"] = categories[0];
                } else {
                    currentObject["category"] = categories.find(
                        (x) => x.value == currentObject.category_id
                    );
                }
                subCategories = await fetchSubCategoryOptions(
                    currentObject.category_id
                );
                if (subCategories.length > 0) {
                    currentObject["subcategory_id"] = subCategories[0].value;
                    currentObject["subcategory"] = subCategories[0];
                } else {
                    currentObject["subcategory"] = null;
                }
            }
            setCategoryOptions(categories);
            setSubCategoryOptions(subCategories);
            parentHandlechanges({ ...currentObject });
        } catch (error) {
            console.error("Error fetching options:", error);
        }
    };

    const fetchSubCategoryOptions = async (categoryId) => {
        try {
            const response = await axios.get(
                listSubCategoryurl + "/" + categoryId
            );
            const subCategories = response.data.map((subCategory) => ({
                label: getRowName(subCategory, dir),
                value: subCategory.id,
            }));
            return subCategories;
        } catch (error) {
            console.error("Error fetching options:", error);
        }
    };

    const handleChange = async (key, value, option) => {
        let updatedObject = { ...currentObject };

        if (key === "category_id") {
            updatedObject["category_id"] = value;
            updatedObject["category"] = option;

            const subCategories = await fetchSubCategoryOptions(value);
            setSubCategoryOptions(subCategories);

            updatedObject["subcategory_id"] = null;
            updatedObject["subcategory"] = null;
        } else if (key === "subcategory_id") {
            updatedObject["subcategory_id"] = value;
            updatedObject["subcategory"] = option;
        } else if (key === "allergens") {
            updatedObject["allergens"] = value;
        } else {
            updatedObject[key] = value;
        }

        parentHandlechanges(updatedObject);
    };

    React.useEffect(() => {
        fetchCategoryOptions();
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(
            (tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl)
        );
    }, []);

    return (
        <>
            <div
                className="card-body"
                dir={dir}
                style={{ display: visible ? "block" : "none" }}
            >
                <div className="d-flex align-items-center pt-3">
                    {canAdd &&(
                        <>
                        <label
                        className="fs-6 fw-semibold mb-2 me-0"
                        style={{ width: "50px" }}
                    >
                        {translations.active}
                        <span
                            className="ms-1"
                            data-bs-toggle="tooltip"
                            aria-label={translations.active_status}
                            data-bs-original-title={translations.active_status}
                        >
                            <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                        </span>
                    </label>
                    <div className="form-check p-0 form-switch">
                        <InputSwitch
                            checked={!!currentObject.active}
                            onChange={(e) => handleChange("active", e.value)}
                        />
                    </div>
                        </>
                    )}
                    <label
                        className="ps-10 fs-6 fw-semibold mb-2 me-3"
                        style={{ width: "150px" }}
                    >
                        {translations.showInMenu}
                        <span
                            className="ms-1"
                            data-bs-toggle="tooltip"
                            aria-label={translations.showInMenu_status}
                            data-bs-original-title={translations.showInMenu_status}
                        >
                            <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                        </span>
                    </label>
                    <div className="form-check form-switch p-0">
                        <InputSwitch
                            checked={!!currentObject.show_in_menu}
                            onChange={(e) => handleChange("show_in_menu", e.value)}
                        />
                    </div>
                </div>

                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label htmlFor="name_ar" className="col-form-label">
                                {translations.name_ar}
                            </label>
                            <input
                                type="text"
                                className="form-control form-control-solid custom-height"
                                id="name_ar"
                                value={currentObject.name_ar || ""}
                                onChange={(e) => handleChange("name_ar", e.target.value)}
                                required
                            />
                        </div>
                        <div className="col-6">
                            <label htmlFor="name_en" className="col-form-label">
                                {translations.name_en}
                            </label>
                            <input
                                type="text"
                                className="form-control form-control-solid custom-height"
                                id="name_en"
                                value={currentObject.name_en || ""}
                                onChange={(e) => handleChange("name_en", e.target.value)}
                                required
                            />
                        </div>
                    </div>
                </div>

                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label className="col-form-label">{translations.category}</label>
                            <Select
                                id="category_id"
                                isMulti={false}
                                options={categoryOptions}
                                closeMenuOnSelect={true}
                                components={animatedComponents}
                                value={currentObject.category}
                                onChange={(val) => handleChange("category_id", val.value, val)}
                                menuPortalTarget={document.body}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 100000 }) }}
                            />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.subcategory}</label>
                            <Select
                                id="subcategory_id"
                                isMulti={false}
                                options={subcategoryOption}
                                closeMenuOnSelect={true}
                                components={animatedComponents}
                                value={currentObject.subcategory}
                                onChange={(val) => handleChange("subcategory_id", val.value, val)}
                                menuPortalTarget={document.body}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 100000 }) }}
                            />
                        </div>
                    </div>
                </div>



                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label className="col-form-label">{translations.deacription_ar}</label>
                            <textarea
                                className="form-control form-control-solid"
                                value={currentObject.description_ar || ""}
                                onChange={(e) => handleChange("description_ar", e.target.value)}
                            />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.deacription_en}</label>
                            <textarea
                                className="form-control form-control-solid"
                                value={currentObject.description_en || ""}
                                onChange={(e) => handleChange("description_en", e.target.value)}
                            />
                        </div>
                    </div>
                </div>



  <div className="form-group">
                    <div className="row">
                        <div className="col-12">
                            <label className="col-form-label">{translations.allergens || "Allergens | مسببات الحساسية"}</label>
                            <Select
                                isMulti
                                options={allergensList}
                                components={animatedComponents}
                            value={(() => {
        let selectedAllergens = currentObject.allergens;

        if (typeof selectedAllergens === 'string') {
            try {
                selectedAllergens = JSON.parse(selectedAllergens);
            } catch (e) {
                selectedAllergens = [];
            }
        }

         if (!Array.isArray(selectedAllergens)) {
            selectedAllergens = [];
        }

        return allergensList.filter(option =>
            selectedAllergens.some(selected =>
               (selected.value ? selected.value === option.value : selected === option.value)
            )
        );
    })()}
                                onChange={(val) => handleChange("allergens", val)}
                                getOptionLabel={(e) => (
                                    <div style={{ display: "flex", alignItems: "center" }}>
                                        {/* <span style={{ marginRight: "10px" }}>{e.icon}</span> */}
                                        <span>{e.label}</span>
                                    </div>
                                )}
                                menuPortalTarget={document.body}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 100000 }) }}
                                placeholder="Select allergens..."
                            />
                        </div>
                    </div>
                </div>

                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label className="col-form-label">{translations.preparationTime}</label>
                            <input
                                type="number"
                                className="form-control form-control-solid custom-height"
                                value={currentObject.preparation_time || ""}
                                onChange={(e) => handleChange("preparation_time", e.target.value)}
                            />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.calories}</label>
                            <input
                                type="number"
                                min="0"
                                step=".01"
                                className="form-control form-control-solid custom-height"
                                value={currentObject.calories || ""}
                                onChange={(e) => handleChange("calories", e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label className="col-form-label">{translations.SKU}</label>
                            <input
                                type="text"
                                className="form-control form-control-solid custom-height"
                                value={currentObject.SKU || ""}
                                placeholder="00000"
                                onChange={(e) => handleChange("SKU", e.target.value)}
                            />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.barcode}</label>
                            <input
                                type="text"
                                className="form-control form-control-solid custom-height"
                                value={currentObject.barcode || ""}
                                onChange={(e) => handleChange("barcode", e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                <div className="form-group">
                    <div className="row">
                        <div className="col-6">
                            <label className="col-form-label">{translations.order}</label>
                            <input
                                type="number"
                                className="form-control form-control-solid custom-height"
                                value={currentObject.order || ""}
                                onChange={(e) => handleChange("order", e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default ProductBasicInfo;

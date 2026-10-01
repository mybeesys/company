import React, { useEffect, useState } from "react";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();
const IngredientBasicInfo = ({
    dir,
    translations,
    units,
    vendors,
    currentObject,
    onBasicChange,
}) => {
    return (
        <div class="card-body" dir={dir}>
            <div class="d-flex flex-wrap align-items-center gap-4 gap-lg-8 pt-3 pb-2">
                <div class="d-flex align-items-center">
                    <label
                        class="fs-6 fw-semibold mb-0 me-3"
                    >
                        {translations.active}
                    </label>
                    <div class="form-check form-switch p-0">
                        <InputSwitch
                            checked={!!currentObject.active}
                            onChange={(e) => onBasicChange("active", e.value ? 1 : 0)}
                        />
                    </div>
                </div>
                <div class="d-flex align-items-center ingredient-for-sell-switch">
                    <label
                        class="fs-6 fw-semibold mb-0 me-2"
                    >
                        {translations.forSell}
                        <span
                            className="ms-1"
                            data-bs-toggle="tooltip"
                            aria-label={translations.ingredientForSellHint || translations.forSell_status}
                            data-bs-original-title={translations.ingredientForSellHint || translations.forSell_status}
                        >
                            <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                        </span>
                    </label>
                    <div class="form-check form-switch p-0">
                        <InputSwitch
                            checked={!!currentObject.for_sell}
                            onChange={(e) => onBasicChange("for_sell", e.value ? 1 : 0)}
                        />
                    </div>
                </div>
            </div>
            {!!currentObject.for_sell ? (
                <div className="alert alert-light-warning d-flex align-items-center py-3 px-4 mb-4 mt-2">
                    <i className="ki-outline ki-information-5 fs-2 text-warning me-3"></i>
                    <div className="fs-7 text-gray-700">
                        {translations.ingredientForSellNotice ||
                            "هذا المكون سيظهر ضمن المنتجات في فواتير المبيعات والعروض والكافيه — تأكد من ضبط سعر البيع والضريبة من تبويب التسعير."}
                    </div>
                </div>
            ) : null}
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">
                            {translations.name_ar}
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-solid custom-height"
                            id="name_ar"
                            value={currentObject.name_ar}
                            onChange={(e) =>
                                onBasicChange("name_ar", e.target.value)
                            }
                            required
                        ></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">
                            {translations.name_en}
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-solid custom-height"
                            id="name_en"
                            value={currentObject.name_en}
                            onChange={(e) =>
                                onBasicChange("name_en", e.target.value)
                            }
                            required
                        ></input>
                    </div>
                </div>
            </div>


                {/* <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">
                                {translations.category}
                            </label>
                            <Select
                                id="category_id"
                                isMulti={false}
                                options={[]}
                                closeMenuOnSelect={true}
                                components={animatedComponents}
                                // value={currentObject.category}
                                // onChange={(val) =>
                                //     handleChange("category_id", val.value, val)
                                // }
                                menuPortalTarget={document.body}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 100000,
                                    }),
                                }}
                            />
                        </div>
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">
                                {translations.subcategory}
                            </label>
                            <Select
                                id="subcategory_id"
                                isMulti={false}
                                options={[]}
                                closeMenuOnSelect={true}
                                components={animatedComponents}
                                // value={}
                                // onChange={(val) =>
                                //     handleChange(
                                //         "subcategory_id",
                                //         val.value,
                                //         val
                                //     )
                                // }
                                menuPortalTarget={document.body}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 100000,
                                    }),
                                }}
                            />
                        </div>
                    </div>
                </div> */}
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="cost" class="col-form-label">
                            {translations.cost}
                        </label>
                        <input
                            type="number"
                            min="0"
                            step=".01"
                            class="form-control form-control-solid custom-height"
                            id="amount"
                            value={currentObject.cost}
                            onChange={(e) =>
                                onBasicChange("cost", e.target.value)
                            }
                            required
                        ></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="SKU" class="col-form-label">
                            {translations.SKU}
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-solid custom-height"
                            id="name_ar"
                            value={currentObject.SKU}
                            onChange={(e) =>
                                onBasicChange("SKU", e.target.value)
                            }
                        ></input>
                    </div>
                    <div class="col-6">
                        <label for="barcode" class="col-form-label">
                            {translations.barcode}
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-solid custom-height"
                            id="barcode"
                            value={currentObject.barcode}
                            onChange={(e) =>
                                onBasicChange("barcode", e.target.value)
                            }
                        ></input>
                    </div>
                </div>
            </div>

            {/* <div class="form-group">
                <div class="row">
                    <div class="col-4">
                        <label for="reorder_point" class="col-form-label">
                            {translations.reorder_point}
                        </label>
                        <input
                            type="number"
                            min="0"
                            class="form-control form-control-solid custom-height"
                            id="reorder_point"
                            value={currentObject.reorder_point}
                            onChange={(e) =>
                                onBasicChange("reorder_point", e.target.value)
                            }
                        ></input>
                    </div>
                    <div class="col-4">
                        <label for="reorder_quantity" class="col-form-label">
                            {translations.reorder_quantity}
                        </label>
                        <input
                            type="number"
                            min="0"
                            class="form-control form-control-solid custom-height"
                            id="reorder_quantity"
                            value={currentObject.reorder_quantity}
                            onChange={(e) =>
                                onBasicChange(
                                    "reorder_quantity",
                                    e.target.value
                                )
                            }
                        ></input>
                    </div>
                    <div class="col-4">
                        <label for="yield_percentage" class="col-form-label">
                            {translations.yield_percentage}
                        </label>
                        <input
                            type="number"
                            min="0"
                            step=".01"
                            class="form-control form-control-solid custom-height"
                            id="yield_percentage"
                            value={currentObject.yield_percentage}
                            onChange={(e) =>
                                onBasicChange(
                                    "yield_percentage",
                                    e.target.value
                                )
                            }
                        ></input>
                    </div>
                </div>
            </div> */}
        </div>
    );
};

export default IngredientBasicInfo;

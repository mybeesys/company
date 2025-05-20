import React, { useState, useCallback, useEffect } from "react";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();
const ModifierBasicInfo = ({
    translations,
    onBasicChange,
    currentObject,
    lov,
}) => {
    const rootElement = document.getElementById("root");
    let dir = rootElement.getAttribute("dir");

    useEffect(() => {
        if (lov.modifierClasses.length > 0) {
            if (!!!currentObject.class_id) {
                currentObject["class_id"] = lov.modifierClasses[0].value;
                currentObject["modifierClass"] = lov.modifierClasses[0];
            } else {
                currentObject["modifierClass"] = lov.modifierClasses.find(
                    (x) => x.value == currentObject.class_id
                );
            }
            onBasicChange("class_id", currentObject["class_id"]);
            onBasicChange("modifierClass", currentObject["modifierClass"]);
        }
    }, [lov]);

    return (
        <>
            <div class="card-body" dir={dir}>
                <div class="d-flex  align-items-center pt-3">
                    <label
                        class="fs-6 fw-semibold mb-2 me-3 "
                        style={{ width: "150px" }}
                    >
                        {translations.active}
                    </label>
                    <div class="form-check form-switch">
                        <InputSwitch
                            checked={
                                !!currentObject.active
                                    ? !!currentObject.active
                                    : false
                            }
                            onChange={(e) => onBasicChange("active", e.value)}
                        />
                    </div>
                </div>
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
                                value={
                                    !!currentObject.name_ar
                                        ? currentObject.name_ar
                                        : ""
                                }
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
                                value={
                                    !!currentObject.name_en
                                        ? currentObject.name_en
                                        : ""
                                }
                                onChange={(e) =>
                                    onBasicChange("name_en", e.target.value)
                                }
                                required
                            ></input>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-12">
                            <label for="name_ar" class="col-form-label">
                                {translations.modifierClass}
                            </label>
                            <Select
                                id="class_id"
                                isMulti={false}
                                options={lov.modifierClasses}
                                closeMenuOnSelect={true}
                                components={animatedComponents}
                                value={currentObject.modifierClass}
                                onChange={(val) => {
                                    onBasicChange("class_id", val.value);
                                    onBasicChange("modifierClass", val);
                                }}
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
                                id="SKU"
                                value={
                                    !!currentObject.SKU ? currentObject.SKU : ""
                                }
                                placeholder="00000"
                                pattern="^\d{5}$"
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
                                value={
                                    !!currentObject.barcode
                                        ? currentObject.barcode
                                        : ""
                                }
                                onChange={(e) =>
                                    onBasicChange("barcode", e.target.value)
                                }
                            ></input>
                        </div>
                    </div>
                </div>
                <div class="form-group" style={{ paddingtop: "5px" }}></div>
            </div>
        </>
    );
};

export default ModifierBasicInfo;

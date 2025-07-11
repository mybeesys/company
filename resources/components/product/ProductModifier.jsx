import React, { useEffect, useState } from "react";
import Select from "react-select";
import ProductModiferDetail from "./ProductModiferDetail";
import makeAnimated from "react-select/animated";
import axios from "axios";

const animatedComponents = makeAnimated();

const ProductModifier = ({
    translations,
    urlList,
    productId,
    productModifiers = [],
    onChange,
    onSelectAll,
}) => {
    const rootElement = document.getElementById("root");
    let dir = rootElement.getAttribute("dir");
    const [modifierClasses, setModifierClasses] = useState([]);
    const [selectedModifiers, setSelectedModifiers] = useState([]);
    const queryParams = new URLSearchParams(window.location.search);
    const initialModifierGroups = () => {
        try {
            const queryParams = new URLSearchParams(window.location.search);
            const groups = queryParams.get("modifierGroups");
            return groups ? JSON.parse(decodeURIComponent(groups)) : [];
        } catch (e) {
            return [];
        }
    };

    const [modifierGroups, setModifierGroups] = useState(() => {
        return initialModifierGroups().map((group) => ({
            class: group.class || {
                product_id: productId,
                modifier_class_id: group.modifier_class_id || group.id,
                min_modifiers: group.min_modifiers || 0,
                max_modifiers: group.max_modifiers || 0,
                free_quantity: group.free_quantity || 0,
                free_type: group.free_type || 0,
                modifier_id: null,
            },
            modifiers: group.modifiers || [],
        }));
    });
    useEffect(() => {
        axios
            .get(urlList)
            .then((response) => {
                setModifierClasses(response.data);
            })
            .catch((error) => {
                console.error("Error fetching modifiers:", error);
            });
    }, [urlList]);

    const handleModifierChange = (modifierClass, key, value) => {
        //console.log("Received modifiers data:", { modifierClass, key, value });
        if (!modifierClass || modifierClass.modifier_class_id === undefined) {
            //  console.error("Invalid modifierClass:", modifierClass);
            return;
        }

        const updatedModifiers = selectedModifiers.map((selected) => {
            const found = productModifiers.find(
                (m) =>
                    (m.class?.modifier_class_id || m.modifier_class_id) ===
                    selected.value
            );
            return (
                found || {
                    class: {
                        product_id: productId,
                        modifier_class_id: selected.value,
                        min_modifiers: 0,
                        max_modifiers: 0,
                        free_quantity: 0,
                        free_type: 0,
                        modifier_id: null,
                    },
                    modifiers: [],
                }
            );
        });

        const classIndex = updatedModifiers.findIndex(
            (m) =>
                (m.class?.modifier_class_id || m.modifier_class_id) ===
                modifierClass.modifier_class_id
        );

        if (classIndex === -1) return;
        const baseModifier = {
            product_id: productId,
            modifier_class_id: modifierClass.modifier_class_id,
            min_modifiers: modifierClass.min_modifiers || 0,
            max_modifiers: modifierClass.max_modifiers || 0,
            free_quantity: modifierClass.free_quantity || 0,
            free_type: modifierClass.free_type || 0,
        };

        if (key === "modifiers") {
            const processedModifiers = value.map((mod) => ({
                ...mod,
                product_id: productId,
                modifier_class_id: modifierClass.modifier_class_id,
                active: mod.active || 0,
                default: mod.default || 0,
                display_order: mod.display_order || 0,
                free_quantity: mod.free_quantity || 0,
                free_type: mod.free_type || 0,
            }));

            if (classIndex >= 0) {
                updatedModifiers[classIndex] = {
                    class: {
                        ...updatedModifiers[classIndex].class,
                        ...baseModifier,
                    },
                    modifiers: processedModifiers,
                };
            } else {
                updatedModifiers.push({
                    class: {
                        ...baseModifier,
                        modifier_id: null,
                    },
                    modifiers: processedModifiers,
                });
            }
        } else {
            if (classIndex >= 0) {
                updatedModifiers[classIndex] = {
                    ...updatedModifiers[classIndex],
                    class: {
                        ...updatedModifiers[classIndex].class,
                        [key]: value,
                        ...baseModifier,
                    },
                };
            } else {
                updatedModifiers.push({
                    class: {
                        ...baseModifier,
                        modifier_id: null,
                        [key]: value,
                    },
                    modifiers: [],
                });
            }
        }

        //console.log("Final data to be saved:", updatedModifiers);
        onChange(updatedModifiers);
    };

    const handleSelectAllModifiers = (modifierClass, modifiers) => {
        const updatedModifiers = modifiers.map((mod) => ({
            product_id: productId,
            modifier_class_id: modifierClass.modifier_class_id,
            modifier_id: mod.id,
            active: 1,
            default: mod.default || 0,
            display_order: mod.display_order || 0,
            name: mod.name,
        }));

        handleModifierChange(modifierClass, "modifiers", updatedModifiers);
    };

    const handleMultiSelectChange = (selectedOptions) => {
        setSelectedModifiers(selectedOptions);

        if (onChange) {
            const newModifiers = selectedOptions.map((option) => {
                const existing = productModifiers.find(
                    (m) =>
                        (m.class?.modifier_class_id || m.modifier_class_id) ===
                        option.value
                );

                return (
                    existing || {
                        class: {
                            product_id: productId,
                            modifier_class_id: option.value,
                            min_modifiers: 0,
                            max_modifiers: 0,
                            free_quantity: 0,
                            free_type: 0,
                            modifier_id: null,
                        },
                        modifiers: [],
                    }
                );
            });

            onChange(newModifiers);
        }
    };

    return (
        <>
            <Select
                isMulti
                options={modifierClasses
                    .filter((x) => x.data.empty !== "Y")
                    .map((modifierClass) => ({
                        label:
                            dir === "rtl"
                                ? modifierClass.data.name_ar
                                : modifierClass.data.name_en,
                        value: modifierClass.data.id,
                    }))}
                value={selectedModifiers}
                onChange={handleMultiSelectChange}
                components={animatedComponents}
                className="basic-multi-select"
                classNamePrefix="select"
                placeholder={
                    translations.select_modifiers || "Select modifiers..."
                }
            />

            {selectedModifiers.map((selectedModifier) => {
                const modifierClass = modifierClasses.find(
                    (m) => m.data.id === selectedModifier.value
                );

                if (!modifierClass) return null;

                const existingData = modifierGroups?.find(
                    (group) =>
                        group.class.modifier_class_id == modifierClass.data.id
                ) || {
                    class: {
                        product_id: productId,
                        modifier_class_id: modifierClass.data.id,
                        modifier_id: null,
                        min_modifiers: 0,
                        max_modifiers: 0,
                        free_quantity: 0,
                        free_type: 0,
                    },
                    modifiers:
                        modifierClass.children?.map((child) => ({
                            id: child.data.id,
                            name:
                                dir === "rtl"
                                    ? child.data.name_ar
                                    : child.data.name_en,
                            active: 0,
                            default: 0,
                            display_order: 0,
                        })) || [],
                };

                return (
                    <ProductModiferDetail
                        key={modifierClass.data.id}
                        translations={translations}
                        productId={productId}
                        modifierId={{
                            product_id: productId,
                            modifier_class_id: modifierClass.data.id,
                            ...existingData.class,
                        }}
                        title={
                            dir === "rtl"
                                ? modifierClass.data.name_ar
                                : modifierClass.data.name_en
                        }
                        productModifiers={existingData}
                        onchange={handleModifierChange}
                        onSelectAll={handleSelectAllModifiers}
                    />
                );
            })}
        </>
    );
};

export default ProductModifier;

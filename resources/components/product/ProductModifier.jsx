import React, { useEffect, useState, useCallback } from "react";
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
    const dir = rootElement.getAttribute("dir");

    const [modifierClasses, setModifierClasses] = useState([]);
    const [selectedModifiers, setSelectedModifiers] = useState([]);
    const [modifierGroups, setModifierGroups] = useState([]);
    const [isLoading, setIsLoading] = useState(true);

    const modifierGroupsAttr = rootElement.getAttribute("modifierGroups");

    useEffect(() => {
        if (modifierGroupsAttr) {
            try {
                const initialGroups = JSON.parse(modifierGroupsAttr);
                const groups = initialGroups.map((group) => ({
                    class: {
                        product_id: group.class.product_id,
                        modifier_class_id: group.class.modifier_class_id,
                        min_modifiers: group.class.min_modifiers,
                        max_modifiers: group.class.max_modifiers,
                        free_quantity: group.class.free_quantity,
                        free_type: group.class.free_type,
                        name_ar: group.class.name_ar,
                        name_en: group.class.name_en,
                        modifier_id: null,
                    },
                    modifiers: group.modifiers || [],
                }));
                setModifierGroups(groups);
            } catch (error) {
                console.error("Error parsing modifierGroups:", error);
            }
        }
    }, [modifierGroupsAttr, isLoading]);

    useEffect(() => {
        setIsLoading(true);
        axios
            .get(urlList)
            .then((response) => {
                setModifierClasses(response.data);
                setIsLoading(false);
            })
            .catch((error) => {
                console.error("Error fetching modifiers:", error);
                setIsLoading(false);
            });
    }, [urlList]);

    useEffect(() => {
        if (modifierClasses.length > 0 && modifierGroups.length > 0) {
            const initialSelectedModifiers = modifierGroups
                .filter((modifier) => modifier.class)
                .map((modifier) => {
                    const matchedClass = modifierClasses.find(
                        (mc) => mc.data.id === modifier.class.modifier_class_id
                    );

                    return {
                        value: modifier.class.modifier_class_id,
                        label: matchedClass
                            ? dir === "rtl"
                                ? matchedClass.data.name_ar
                                : matchedClass.data.name_en
                            : dir === "rtl"
                            ? modifier.class.name_ar
                            : modifier.class.name_en,
                    };
                });

            setSelectedModifiers(initialSelectedModifiers);
        }
    }, [modifierGroups, modifierClasses, dir]);

    const handleModifierChange = useCallback(
        (modifierClass, key, value) => {
            if (
                !modifierClass ||
                modifierClass.modifier_class_id === undefined
            ) {
                return;
            }

            setModifierGroups((prevGroups) => {
                const updatedGroups = [...prevGroups];
                const groupIndex = updatedGroups.findIndex(
                    (group) =>
                        group.class.modifier_class_id ===
                        modifierClass.modifier_class_id
                );

                if (groupIndex === -1) {
                    updatedGroups.push({
                        class: { ...modifierClass },
                        modifiers: key === "modifiers" ? value : [],
                    });
                } else {
                    if (key === "modifiers") {
                        updatedGroups[groupIndex].modifiers = value;
                    } else {
                        updatedGroups[groupIndex].class[key] = value;
                    }
                }
                if (onChange) {
                    onChange(updatedGroups);
                }

                return updatedGroups;
            });
        },
        [onChange]
    );

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

    const selectOptions = modifierClasses
        .filter((x) => x.data.empty !== "Y")
        .map((modifierClass) => ({
            label:
                dir === "rtl"
                    ? modifierClass.data.name_ar
                    : modifierClass.data.name_en,
            value: modifierClass.data.id,
        }));

    return (
        <>
            {isLoading ? (
                <div>Loading modifiers...</div>
            ) : (
                <>
                    <Select
                        isMulti
                        options={selectOptions}
                        value={selectedModifiers}
                        onChange={handleMultiSelectChange}
                        components={animatedComponents}
                        className="basic-multi-select"
                        classNamePrefix="select"
                        placeholder={
                            translations.select_modifiers ||
                            "Select modifiers..."
                        }
                    />

                    {selectedModifiers.map((selectedModifier) => {
                        const modifierClass = modifierClasses.find(
                            (m) => m.data.id === selectedModifier.value
                        );

                        if (!modifierClass) return null;

                        const existingData = modifierGroups.find(
                            (group) =>
                                group.class.modifier_class_id ===
                                modifierClass.data.id
                        );

                        const dataToUse = existingData || {
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
                        if (
                            !dataToUse.modifiers.length &&
                            modifierClass.children
                        ) {
                            dataToUse.modifiers = modifierClass.children.map(
                                (child) => ({
                                    id: child.data.id,
                                    name:
                                        dir === "rtl"
                                            ? child.data.name_ar
                                            : child.data.name_en,
                                    active: 0,
                                    default: 0,
                                    display_order: 0,
                                })
                            );
                        }
                        return (
                            <ProductModiferDetail
                                key={modifierClass.data.id}
                                translations={translations}
                                productId={productId}
                                modifierId={{
                                    product_id: productId,
                                    modifier_class_id: modifierClass.data.id,
                                    ...dataToUse.class,
                                }}
                                title={
                                    dir === "rtl"
                                        ? modifierClass.data.name_ar
                                        : modifierClass.data.name_en
                                }
                                productModifiers={dataToUse}
                                onchange={handleModifierChange}
                                onSelectAll={handleSelectAllModifiers}
                            />
                        );
                    })}
                </>
            )}
        </>
    );
};

export default ProductModifier;

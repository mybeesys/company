import React, { useEffect, useRef, useState } from "react";
import TreeTableEditorRecipe from "../comp/TreeTableEditorRecipe";
import { formatDecimal } from "../lang/Utils";
import { computeRecipeRowCost } from "../lang/recipeCost";

const ProductRecipe = ({
    translations,
    productRecipe,
    product,
    ingredientTree,
    onBasicChange,
    dir,
}) => {
    const [isRecipeYieldRequired, setIsRecipeYieldRequired] = useState(false);
    const recalcVersionRef = useRef(0);

    const handleCheckboxChange = (e) => {
        const checked = e.target.checked;
        onBasicChange("prep_recipe", checked ? 1 : 0);
        setIsRecipeYieldRequired(checked);
    };

    useEffect(() => {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(
            (tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl)
        );
    }, [product, ingredientTree]);

    const applyRowCost = (rowData, postExecute, nodes, rowKey) => {
        if (!rowData.newid || rowData.quantity == null || rowData.quantity === "") {
            return;
        }

        const ingredient = ingredientTree.find((e) => e.value === rowData.newid);
        if (!ingredient) return;

        const version = ++recalcVersionRef.current;
        computeRecipeRowCost(
            rowData.newid,
            rowData.quantity,
            rowData.unit_transfer,
            ingredient.cost
        ).then((cost) => {
            if (version !== recalcVersionRef.current) return;
            rowData.cost = cost;
            postExecute([...nodes]);
        });
    };

    const handleDelete = (row) => {
        let index = productRecipe.findIndex((x) => x.id == row.id);
        productRecipe.splice(index, 1);
        onBasicChange("recipe", productRecipe);
        return { message: "Done" };
    };

    return (
        <div className="recipe-panel pt-2">
            <div className="table-responsive recipe-table">
                <TreeTableEditorRecipe
                    translations={translations}
                    dir={dir}
                    header={false}
                    addNewRow={true}
                    type={"recipe"}
                    title={translations.recipe}
                    currentNodes={[...productRecipe]}
                    defaultValue={{}}
                    cols={[
                        {
                            key: "newid",
                            title: "Ingredient",
                            autoFocus: true,
                            options: ingredientTree,
                            type: "DropDown",
                            width: "35%",
                            editable: true,
                            required: true,
                            onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                                const updatedNodes = [...nodes];
                                const rowData = updatedNodes[rowKey].data;
                                rowData[key] = val;
                                applyRowCost(rowData, postExecute, updatedNodes, rowKey);
                            },
                        },
                        {
                            key: "unit_transfer",
                            title: "unit",
                            autoFocus: true,
                            type: "AsyncDropDown",
                            width: "22%",
                            editable: true,
                            required: true,
                            searchUrl: "searchUnitTransfers",
                            relatedTo: {
                                key: "id",
                                relatedKey: "newid",
                            },
                            onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                                const updatedNodes = [...nodes];
                                const rowData = updatedNodes[rowKey].data;
                                rowData[key] = val;
                                applyRowCost(rowData, postExecute, updatedNodes, rowKey);
                            },
                        },
                        {
                            key: "quantity",
                            title: "quantity",
                            autoFocus: false,
                            type: "Decimal",
                            width: "18%",
                            decimals: 6,
                            editable: true,
                            required: true,
                            onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                                const updatedNodes = [...nodes];
                                const rowData = updatedNodes[rowKey].data;
                                rowData[key] = val;
                                applyRowCost(rowData, postExecute, updatedNodes, rowKey);
                            },
                        },
                        {
                            key: "cost",
                            title: "cost",
                            autoFocus: false,
                            type: "Decimal",
                            width: "15%",
                            decimals: 4,
                            editable: false,
                            required: false,
                        },
                    ]}
                    actions={[]}
                    onUpdate={(nodes) => onBasicChange("recipe", nodes)}
                    onDelete={handleDelete}
                />
            </div>
            <div className="row g-3 recipe-meta-row">
                <div className="col-12 col-md-6 col-lg-5">
                    <label htmlFor="recipe_yield" className="form-label fw-semibold">
                        {translations.recipe_yield}
                    </label>
                    <input
                        type="text"
                        inputMode="decimal"
                        className="form-control form-control-solid custom-height"
                        id="recipe_yield"
                        value={product.recipe_yield ?? ""}
                        onChange={(e) =>
                            onBasicChange("recipe_yield", e.target.value)
                        }
                        onBlur={(e) => {
                            const formatted = formatDecimal(e.target.value);
                            if (
                                formatted !== "" &&
                                formatted !== String(product.recipe_yield ?? "")
                            ) {
                                onBasicChange("recipe_yield", formatted);
                            }
                        }}
                        required={isRecipeYieldRequired}
                    />
                </div>
            </div>
            <div className="d-flex align-items-center flex-wrap gap-2 pt-3">
                <label
                    className="fs-6 fw-semibold mb-0"
                    htmlFor="prep_recipe"
                >
                    {translations.prep_recipe}
                    <span
                        className="ms-1"
                        data-bs-toggle="tooltip"
                        aria-label={translations.prep_recipe_status}
                        data-bs-original-title={translations.prep_recipe_status}
                    >
                        <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                    </span>
                </label>
                <div className="form-check mb-0">
                    <input
                        type="checkbox"
                        className="form-check-input"
                        id="prep_recipe"
                        checked={product.prep_recipe === 1}
                        onChange={handleCheckboxChange}
                    />
                </div>
            </div>
        </div>
    );
};

export default ProductRecipe;

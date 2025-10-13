import React, { useEffect, useState } from "react";
import TreeTableEditorRecipe from "../comp/TreeTableEditorRecipe";

const ProductRecipe = ({
    translations,
    productRecipe,
    product,
    ingredientTree,
    onBasicChange,
    dir,
}) => {
    const [isRecipeYieldRequired, setIsRecipeYieldRequired] = useState(false);

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

    const calculateCost = (ingredientId, quantity, unitTransfer) => {
        if (!ingredientId || !quantity) return 0;

        const ingredient = ingredientTree.find((e) => e.value === ingredientId);
        if (!ingredient) return 0;

        let transfer = 0;
        if (unitTransfer && typeof unitTransfer === "object") {
            transfer = parseFloat(unitTransfer?.data?.transfer || 0);
        }


        if (transfer > 0) {
            return (parseFloat(quantity) / transfer) * ingredient.cost;
        } else {
            return parseFloat(quantity) * ingredient.cost;
        }
    };

    const handleDelete = (row) => {
        let index = productRecipe.findIndex((x) => x.id == row.id);
        productRecipe.splice(index, 1);
        onBasicChange("recipe", productRecipe);
        return { message: "Done" };
    };

    return (
        <div class="pt-3">
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

                            if (rowData.newid && rowData.quantity) {
                                rowData.cost = calculateCost(
                                    rowData.newid,
                                    rowData.quantity,
                                    rowData.unit_transfer
                                );
                            }

                            postExecute(updatedNodes);
                        },
                    },
                    {
                        key: "unit_transfer",
                        autoFocus: true,
                        type: "AsyncDropDown",
                        width: "25%",
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

                            if (rowData.newid && rowData.quantity) {
                                rowData.cost = calculateCost(
                                    rowData.newid,
                                    rowData.quantity,
                                    rowData.unit_transfer
                                );
                            }

                            postExecute(updatedNodes);
                        },
                    },
                    {
                        key: "quantity",
                        autoFocus: false,
                        type: "Decimal",
                        width: "20%",
                        editable: true,
                        required: true,
                        onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                            const updatedNodes = [...nodes];
                            const rowData = updatedNodes[rowKey].data;
                            rowData[key] = val;

                            if (rowData.newid && rowData.quantity) {
                                rowData.cost = calculateCost(
                                    rowData.newid,
                                    rowData.quantity,
                                    rowData.unit_transfer
                                );
                            }

                            postExecute(updatedNodes);
                        },
                    },
                    {
                        key: "cost",
                        autoFocus: false,
                        type: "Decimal",
                        width: "20%",
                        editable: false,
                        required: false,
                    },
                ]}
                actions={[]}
                onUpdate={(nodes) => onBasicChange("recipe", nodes)}
                onDelete={handleDelete}
            />
            <div class="row" style={{ paddingTop: "20px" }}>
                <div class="col-6">
                    <label for="recipe_yield" class="col-form-label">
                        {translations.recipe_yield}
                    </label>
                    <input
                        type="number"
                        min="0"
                        step=".01"
                        class="form-control form-control-solid custom-height"
                        id="recipe_yield"
                        value={product.recipe_yield}
                        onChange={(e) =>
                            onBasicChange("recipe_yield", e.target.value)
                        }
                        required={isRecipeYieldRequired}
                    ></input>
                </div>
            </div>
            <div class="d-flex align-items-center pt-3">
                <label
                    class="fs-6 fw-semibold mb-2 me-3"
                    style={{ width: "150px" }}
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
                <div class="form-check">
                    <input
                        type="checkbox"
                        style={{ border: "1px solid #9f9f9f" }}
                        class="form-check-input my-2"
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

import React, { useEffect } from "react";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";

const ProductRecipe = ({
    translations,
    productRecipe,
    product,
    ingredientTree,
    onBasicChange,
    dir,
}) => {
    useEffect(() => {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(
            (tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl)
        );
    }, [product, ingredientTree]);

    const fetchUnitTransferData = async (id) => {
        try {
            const response = await axios.get(`/getUnitTransfer/${id}`);
            return response.data;
        } catch (error) {
            console.error("Failed to fetch unit transfer:", error);
            return null;
        }
    };
    const calculateCost = async (ingredientId, quantity, unitTransfer) => {
        const ingredient = ingredientTree.find((e) => e.value === ingredientId);
        if (!ingredient || !quantity) return 0;

        try {
            if (unitTransfer?.id) {
                const unitData = await fetchUnitTransferData(unitTransfer.id);
                if (unitData?.unit2) {
                    return quantity / ingredient.cost;
                }
            }
            return quantity * ingredient.cost;
        } catch (error) {
            console.error("Error calculating cost:", error);
            return 0;
        }
    };
    const handleDelete = (row) => {
        let index = productRecipe.findIndex((x) => x.id == row.id);
        productRecipe.splice(index, 1); // Removes 1 element at index 2
        onBasicChange("recipe", productRecipe);
        return { message: "Done" };
    };

    return (
        <div class="pt-3">
            <TreeTableEditorLocal
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
                        width: "25%",
                        editable: true,
                        required: true,
                        onChangeValue: (
                            nodes,
                            key,
                            val,
                            rowKey,
                            postExecute
                        ) => {
                            if (!!val && !!nodes[rowKey].data["quantity"]) {
                                let cost = ingredientTree.find(
                                    (e) => e.value == val
                                ).cost;
                                nodes[rowKey].data["cost"] =
                                    nodes[rowKey].data["quantity"] * cost;
                            }
                            postExecute(nodes);
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
                    },
                    {
                        key: "quantity",
                        autoFocus: false,
                        type: "Decimal",
                        width: "20%",
                        editable: true,
                        required: true,
                        onChangeValue: async (
                            nodes,
                            key,
                            val,
                            rowKey,
                            postExecute
                        ) => {
                            const updatedNodes = [...nodes];
                            const rowData = updatedNodes[rowKey].data;

                            if (rowData["newid"] && val) {
                                rowData["quantity"] = parseFloat(val) || 0;

                                rowData["cost"] = await calculateCost(
                                    rowData["newid"],
                                    rowData["quantity"],
                                    rowData["unit_transfer"]
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
            <div class="row" style={{ paddingtop: "20px" }}>
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
                        value={
                            !!product.recipe_yield ? product.recipe_yield : ""
                        }
                        onChange={(e) =>
                            onBasicChange("recipe_yield", e.target.value)
                        }
                    ></input>
                </div>
            </div>
            <div class="d-flex  align-items-center pt-3">
               <label
                    class="fs-6 fw-semibold mb-2 me-3 "
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
                        checked={product.prep_recipe}
                        onChange={(e) =>
                            onBasicChange(
                                "prep_recipe",
                                e.target.checked ? 1 : 0
                            )
                        }
                    />
                </div>
            </div>
        </div>
    );
};

export default ProductRecipe;

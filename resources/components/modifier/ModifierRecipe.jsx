import React, { useEffect, useRef } from "react";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";
import { computeRecipeRowCost } from "../lang/recipeCost";

const ModifierRecipe = ({
    translations,
    modifierRecipe,
    modifier,
    ingredientTree,
    onBasicChange,
    dir,
}) => {
    const recalcVersionRef = useRef(0);

    useEffect(() => {}, [modifier, ingredientTree]);

    const handleDelete = (row) => {
        let index = modifierRecipe.findIndex((x) => x.id == row.id);
        modifierRecipe.splice(index, 1);
        onBasicChange("recipe", modifierRecipe);
        return { message: "Done" };
    };

    const calculateCost = (nodes, rowKey, postExecute) => {
        const row = nodes[rowKey]?.data;
        if (!row?.newid || row.quantity == null || row.quantity === "") {
            return;
        }

        const ingredient = ingredientTree.find((e) => e.value == row.newid);
        const itemCost = parseFloat(ingredient?.cost || 0);
        const version = ++recalcVersionRef.current;

        computeRecipeRowCost(
            row.newid,
            row.quantity,
            row.unit_transfer,
            itemCost
        ).then((cost) => {
            if (version !== recalcVersionRef.current) return;
            nodes[rowKey].data.cost = cost;
            postExecute([...nodes]);
        });
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
                currentNodes={[...modifierRecipe]}
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
                        onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                            calculateCost(nodes, rowKey, postExecute);
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
                            calculateCost(nodes, rowKey, postExecute);
                        },
                    },
                    {
                        key: "quantity",
                        title: "quantity",
                        autoFocus: false,
                        type: "Decimal",
                        width: "20%",
                        decimals: 6,
                        editable: true,
                        required: true,
                        onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                            calculateCost(nodes, rowKey, postExecute);
                        },
                    },
                    {
                        key: "cost",
                        title: "cost",
                        autoFocus: false,
                        type: "Decimal",
                        width: "20%",
                        decimals: 4,
                        editable: false,
                        required: false,
                    },
                ]}
                actions={[]}
                onUpdate={(nodes) => onBasicChange("recipe", nodes)}
                onDelete={handleDelete}
                modifier
            />

            <div class="d-flex  align-items-center pt-3">
                <label
                    class="fs-6 fw-semibold mb-2 me-3 "
                    style={{ width: "300px" }}
                >
                    {translations.adjust_product_cost_recipe_cost}
                </label>
                <div class="form-check">
                    <input
                        type="checkbox"
                        style={{ border: "1px solid #9f9f9f" }}
                        class="form-check-input my-2"
                        id="adjust_product_cost_recipe_cost"
                        name="adjust_product_cost_recipe_cost"
                        checked={modifier?.adjust_product_cost_recipe_cost}
                        onChange={(e) =>
                            onBasicChange(
                                "adjust_product_cost_recipe_cost",
                                e.target.checked ? 1 : 0
                            )
                        }
                    />
                </div>
            </div>

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
                            !!modifier.recipe_yield ? modifier.recipe_yield : ""
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
                </label>
                <div class="form-check">
                    <input
                        type="checkbox"
                        style={{ border: "1px solid #9f9f9f" }}
                        class="form-check-input my-2"
                        id="prep_recipe"
                        checked={modifier.prep_recipe}
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

export default ModifierRecipe;

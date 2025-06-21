import axios from "axios";
import { useState, useEffect } from "react";
import SweetAlert2 from "react-sweetalert2";
import DropdownMenu from "../../comp/DropdownMenu";
import TreeTableComponent from "../../comp/TreeTableComponent";

const PrepTable = ({ dir, translations }) => {
    const [showAlert, setShowAlert] = useState(false);
    const [currentRow, setCurrentRow] = useState({});
    const [products, setProducts] = useState([]);

    const [warehouses, setWarehouses] = useState([]);
    const [selectedFromWarehouse, setSelectedFromWarehouse] = useState("");
    const [selectedToWarehouse, setSelectedToWarehouse] = useState("");

    useEffect(() => {
        const fetchWarehouses = async () => {
            try {
                const response = await axios.get("establishmentList");
                setWarehouses(response.data);
                if (response.data.length > 0) {
                    setSelectedFromWarehouse(response.data[0].id);
                    setSelectedToWarehouse(response.data[0].id);
                }
            } catch (error) {
                console.error("Error fetching warehouses:", error);
            }
        };
        fetchWarehouses();
        fetchProducts();
    }, []);

    const fetchProducts = () => {
        axios
            .get("/products/needPreparationList")
            .then((response) => {
                setProducts(response.data);
            })
            .catch((error) => {
                console.error("Error fetching products:", error);
            });
    };

    const prepareData = (data) => {
        return data.map((row) => {
            return {
                key: row.id,
                data: {
                    ...row,
                    products: dir === "rtl" ? row.name_ar : row.name_en,
                },
            };
        });
    };

    const productNameCell = (data, key, editMode, editable) => {
        const productName = dir === "rtl" ? data.name_ar : data.name_en;
        return (
            <span
                style={{
                    color: "#333",
                }}
            >
                {productName}
            </span>
        );
    };

    const prepareRecipeCell = (data, key, editMode, editable, refreshTree) => {
        const handlePrepare = async (event) => {
            event.preventDefault();

            try {
                const response = await axios.get(
                    `products/getIngredientList/${data.id}`
                );
                const ingredientsList = response.data.products;
                const qyt = response.data.recipeQyt;

                const warehousesHtml = `
                <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500;">${
                            translations.from
                        }</label>
                        <select id="from-warehouse" class="form-control" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ced4da; width: 100%;">
                            ${warehouses
                                .map(
                                    (wh) => `
                                <option value="${wh.id}">
                                    ${dir === "rtl" ? wh.name : wh.name_en}
                                </option>
                            `
                                )
                                .join("")}
                        </select>
                    </div>
                    
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500;">${
                            translations.to
                        }</label>
                        <select id="to-warehouse" class="form-control" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ced4da; width: 100%;">
                            ${warehouses
                                .map(
                                    (wh) => `
                                <option value="${wh.id}">
                                    ${dir === "rtl" ? wh.name : wh.name_en}
                                </option>
                            `
                                )
                                .join("")}
                        </select>
                    </div>
                </div>
            `;
                const ingredientsHtml = `
                <div style="display: flex; margin-bottom: 20px; gap: 20px;">
                    <div style="flex: 1; display: flex; flex-direction: column; border: 1px solid #dee2e6; border-radius: 4px;">
                        <div style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 4px 4px 0 0;">
                            <i class="fas fa-list-ul" style="margin-right: 8px;"></i>
                            ${translations.ingredients}
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            ${ingredientsList
                                .map(
                                    (ingredient) => `
                                <div style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; flex: 1;">
                                    ${
                                        dir === "rtl"
                                            ? ingredient.products.name_ar
                                            : ingredient.products.name_en
                                    }
                                </div>
                            `
                                )
                                .join("")}
                        </div>
                    </div>
                    
                    <div style="width: 150px; display: flex; flex-direction: column; border: 1px solid #dee2e6; border-radius: 4px;">
                        <div style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 4px 4px 0 0; text-align: center;">
                            <i class="fas fa-hashtag" style="margin-right: 8px;"></i>
                            ${translations.quantity}
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            ${ingredientsList
                                .map(
                                    (ingredient) => `
                                <div style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; text-align: center; flex: 1;">
                                    <input 
                                        type="number" 
                                        class="form-control ingredient-qty" 
                                        style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ced4da; text-align: center; width: 100%;"
                                        value="${ingredient.quantity || 0}"
                                        min="0"
                                        step="0.01"
                                    />
                                </div>
                            `
                                )
                                .join("")}
                        </div>
                    </div>
                </div>
            `;

                const productionQtyHtml = `
                <div style="margin-top: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <label style="min-width: 120px; color: #495057; font-weight: 500;">${
                            translations.production_quantity
                        }</label>
                        <input 
                            type="number" 
                            id="production-qty" 
                            class="form-control" 
                            style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ced4da; width: 250px;"
                            value="${qyt.recipe_yield || 1}"
                            min="1"
                            step="1"
                            required
                        />
                    </div>
                </div>
            `;

                const { value: formValues } = await Swal.fire({
                    title: `<div style="display: flex; align-items: center; gap: 10px;">
                          <i class="fas fa-utensils" style="font-size: 24px; color: #ffc107;"></i>
                          <span style="font-size: 1.25rem;">${translations.recipe_ingredient}</span>
                        </div>`,
                    html: `
                    <div>
                        ${warehousesHtml}
                        ${ingredientsHtml}
                        ${productionQtyHtml}
                    </div>
                `,
                    showCancelButton: true,
                    confirmButtonText: `<i class="fas fa-check-circle" style="margin-right: 8px;"></i> ${translations.prepare}`,
                    cancelButtonText: `<i class="fas fa-times-circle" style="margin-right: 8px;"></i> ${translations.Close}`,
                    width: "650px",
                    customClass: {
                        confirmButton: "btn btn-warning btn-lg",
                        cancelButton: "btn btn-secondary btn-lg",
                    },
                    preConfirm: () => {
                        const fromWh =
                            document.getElementById("from-warehouse").value;
                        const toWh =
                            document.getElementById("to-warehouse").value;
                        const productionQty =
                            document.getElementById("production-qty").value;

                        if (productionQty <= 0) {
                            Swal.showValidationMessage(
                                translations.production_qty_error ||
                                    "Production quantity must be greater than 0"
                            );
                            return false;
                        }

                        const quantityInputs =
                            document.querySelectorAll(".ingredient-qty");
                        let validQuantities = true;

                        quantityInputs.forEach((input) => {
                            if (input.value < 0) {
                                validQuantities = false;
                            }
                        });

                        if (!validQuantities) {
                            Swal.showValidationMessage(
                                translations.ingredient_qty_error ||
                                    "Ingredient quantities cannot be negative"
                            );
                            return false;
                        }

                        const ingredientsData = Array.from(quantityInputs).map(
                            (input, index) => ({
                                id: ingredientsList[index].products.id,
                                name: ingredientsList[index].products[
                                    dir === "rtl" ? "name_ar" : "name_en"
                                ],
                                quantity: input.value,
                                order: ingredientsList[index].order,
                            })
                        );

                        return {
                            productId: data.id,
                            from: fromWh,
                            to: toWh,
                            ingredients: ingredientsData,
                            productionQty: productionQty,
                        };
                    },
                });

                if (formValues) {
                    await axios.post("/prepareRecipe", formValues);
                    Swal.fire(
                        translations.success,
                        translations.preparation_saved,
                        "success"
                    );
                    refreshTree();
                }
            } catch (error) {
                Swal.fire({
                    title: translations.error,
                    text: translations.qyt_error,
                    icon: "error",
                    confirmButtonText: translations.Save,
                });
            }
        };

        return (
            <button
                onClick={handlePrepare}
                className="btn btn-warning"
                style={{
                    padding: "8px 16px",
                    fontSize: "14px",
                    fontWeight: "600",
                    borderRadius: "4px",
                }}
            >
                <i
                    className="fas fa-utensils"
                    style={{ marginRight: "8px" }}
                ></i>
                {translations.prepare_recipe}
            </button>
        );
    };

    const canEditRow = () => false;

    return (
        <div>
            <SweetAlert2 />
            <TreeTableComponent
                translations={translations}
                canEditRow={canEditRow}
                editUrl={null}
                dir={dir}
                urlList="/products/needPreparationList"
                canAddInline={false}
                title="prep_re"
                cols={[
                    {
                        key: "product",
                        type: "text",
                        width: "90%",
                        customCell: productNameCell,
                    },
                    {
                        key: "actions",
                        type: "text",
                        width: "10%",
                        customCell: prepareRecipeCell,
                    },
                ]}
                prepareData={prepareData}
            />
        </div>
    );
};

export default PrepTable;

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
                const unit = response.data.unit;
                const quantities = response.data.quantities || [];
                const warehousesHtml = `
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label>${translations.from}</label>
                            <select id="from-warehouse" class="form-control">
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
                            <label>${translations.to}</label>
                            <select id="to-warehouse" class="form-control">
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
                </div>
            `;
                const ingredientsHtml = ingredientsList
                    .map(
                        (ingredient, index, unit) => `
                <tr>
                    <td>${
                        dir === "rtl" ? ingredient.name_ar : ingredient.name_en
                    }</td>
                    <td>
                        <div style="text-align: left;">
                            <input type="number" class="form-control" style="width: 200px;"
                            value="${quantities[index]?.quantity || 0}"
                            }" 
                            }"
                            }" />
                        </div>
                    </td>
                </tr>
            `
                    )
                    .join("");

                const { value: formValues } = await Swal.fire({
                    title: translations.recipe_ingredient,

                    html: `
                        <div style="max-height: 90vh; overflow-y: auto;">
                            ${warehousesHtml}

                    <table class="table">
                        <thead style="background-color: lightgray; color: black;">
                            <tr>
                              <th>${translations.ingredients}</th>
                              <th>${translations.quantity}</th>
                            </tr>
                        </thead>
                        <tbody id="ingredients-table">
                            ${ingredientsHtml}
                        </tbody>
                    </table>
                </div>
            `,
                    showCancelButton: true,
                    confirmButtonText: translations.prepare,
                    cancelButtonText: translations.Close,
                    width: "600px",
                    customClass: {
                        confirmButton: "btn btn-warning",
                        cancelButton: "btn btn-secondary",
                    },
                    preConfirm: () => {
                        const fromWh =
                            document.getElementById("from-warehouse").value;
                        const toWh =
                            document.getElementById("to-warehouse").value;

                        const ingredientsData = Array.from(
                            document.querySelectorAll("#ingredients-table tr")
                        ).map((row, index) => ({
                            id: ingredientsList[index].id,
                            name: row.cells[0].innerText,
                            quantity: row.querySelector("input").value,
                        }));

                        return {
                            productId: data.id,
                            from: fromWh,
                            to: toWh,
                            ingredients: ingredientsData,
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
            <button onClick={handlePrepare} className="btn btn-sm btn-warning">
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

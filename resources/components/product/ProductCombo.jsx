import React, { useState, useCallback } from "react";
import ProductComboUpchargeModal from "./ProductComboUpchargeModal";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";

const ProductCombo = ({
    translations,
    dir,
    product,
    products,
    onComboChange,
}) => {
    const [showUpchargeDialog, setShowUpchargeDialog] = useState(false);
    const [currentCombo, setCurrentCombo] = useState({
        combo_id: -1,
        products: [],
    });
    const [columnWidths, setColumnWidths] = useState({
        name_ar: "50px",
        name_en: "70px",
        products: "300px",
        quantity: "100px",
        price: !!product.set_price ? "100px" : "100px",
    });

    const handleInputChange = useCallback(
        (key, value, rowKey, onChangeValue) => {
            let newWidth;
            if (key === "products") {
                newWidth = Math.max(value.length * 8 + 100, 200);
            } else if (["name_ar", "name_en"].includes(key)) {
                newWidth = Math.max(value.toString().length * 10, 100);
            } else {
                const numLength = value.toString().replace(".", "").length;
                newWidth = Math.max(numLength * 8 + 30, 100);
            }

            setColumnWidths((prevWidths) => ({
                ...prevWidths,
                [key]: `${newWidth}px`,
            }));

            if (onChangeValue) {
                onChangeValue(key, value, rowKey);
            }
        },
        []
    );

    const onClose = (id, updatedProducts) => {
        const updatedCombos = product.combos.map((combo) => {
            if (combo.id === id) {
                const upchargePrices = updatedProducts
                    .filter((product) => product.price > 0)
                    .map((product) => ({
                        product_id: product.value,
                        price: product.price,
                    }));

                return {
                    ...combo,
                    upchargePrices: upchargePrices,
                };
            }
            return combo;
        });

        onComboChange("combos", updatedCombos);
        setShowUpchargeDialog(false);
    };
    React.useEffect(() => {
        const initTooltips = () => {
            const tooltipElements = document.querySelectorAll(
                '[data-bs-toggle="tooltip"]'
            );
            tooltipElements.forEach((el) => {
                const instance = window.bootstrap.Tooltip.getInstance(el);
                if (instance) instance.dispose();
            });
            tooltipElements.forEach((el) => {
                new window.bootstrap.Tooltip(el, {
                    trigger: "hover focus",
                });
            });
        };
        const timer = setTimeout(initTooltips, 50);

        return () => clearTimeout(timer);
    }, [
        translations,
        product.group_combo,
        product.set_price,
        product.use_upcharge,
    ]);
    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row pt-3">
                    <div class="d-flex  align-items-center ">
                        <label
                            class="fs-6 fw-semibold mb-2 me-3 "
                            style={{ width: "150px" }}
                        >
                            {translations.groupCombo}
                            <span
                                className="ms-1"
                                data-bs-toggle="tooltip"
                                aria-label={translations.groupCombo_status}
                                data-bs-original-title={
                                    translations.groupCombo_status
                                }
                            >
                                <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                            </span>
                        </label>
                        <div class="form-check">
                            <input
                                type="checkbox"
                                style={{ border: "1px solid #9f9f9f" }}
                                class="form-check-input my-2"
                                id="active"
                                checked={
                                    !!product.group_combo == 0 ? false : true
                                }
                                onChange={(e) =>
                                    onComboChange(
                                        "group_combo",
                                        e.target.checked ? 1 : 0
                                    )
                                }
                            />
                        </div>
                    </div>
                </div>
                {!!product.group_combo ? (
                    <>
                        <div class="row">
                            <div class="col-6">
                                <div class="d-flex  align-items-center ">
                                    <label
                                        class="fs-6 fw-semibold mb-2 me-3 "
                                        style={{ width: "150px" }}
                                    >
                                        {translations.setPriceInCombo}
                                        <span
                                            className="ms-1"
                                            data-bs-toggle="tooltip"
                                            aria-label={
                                                translations.setPriceInCombo_status
                                            }
                                            data-bs-original-title={
                                                translations.setPriceInCombo_status
                                            }
                                        >
                                            <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
                                    </label>
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            style={{
                                                border: "1px solid #9f9f9f",
                                            }}
                                            class="form-check-input my-2"
                                            id="active"
                                            checked={true}
                                            disabled
                                            onChange={(e) =>
                                                onComboChange("set_price", 0)
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="d-flex  align-items-center ">
                                    <label
                                        class="fs-6 fw-semibold mb-2 me-3 "
                                        style={{ width: "150px" }}
                                    >
                                        {translations.useUpCharge}
                                        <span
                                            className="ms-1"
                                            data-bs-toggle="tooltip"
                                            aria-label={
                                                translations.useUpCharge_status
                                            }
                                            data-bs-original-title={
                                                translations.useUpCharge_status
                                            }
                                        >
                                            <i className="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
                                    </label>
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            style={{
                                                border: "1px solid #9f9f9f",
                                            }}
                                            class="form-check-input my-2"
                                            id="active"
                                            checked={
                                                !!product.use_upcharge == 0
                                                    ? false
                                                    : true
                                            }
                                            onChange={(e) =>
                                                onComboChange(
                                                    "use_upcharge",
                                                    e.target.checked ? 1 : 0
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ProductComboUpchargeModal
                            visible={showUpchargeDialog}
                            onClose={onClose}
                            translations={translations}
                            combo={currentCombo}
                            dir={dir}
                        />

                        <TreeTableEditorLocal
                            translations={translations}
                            dir={dir}
                            header={false}
                            addNewRow={true}
                            type={"productCombo"}
                            title={translations.productCombos}
                            currentNodes={[...product.combos]}
                            defaultValue={{
                                price: 0,
                                combo_saving: 0,
                                quantity: 1,
                            }}
                            cols={[
                                {
                                    key: "name_ar",
                                    autoFocus: false,
                                    options: [],
                                    type: "Text",
                                    width: columnWidths.name_ar,
                                    editable: true,
                                    required: true,
                                    onChange: (e, rowKey) =>
                                        handleInputChange(
                                            "name_ar",
                                            e.target.value,
                                            rowKey
                                        ),
                                },
                                {
                                    key: "name_en",
                                    autoFocus: true,
                                    options: [],
                                    type: "Text",
                                    width: columnWidths.name_en,
                                    editable: true,
                                    required: true,
                                    onChange: (e, rowKey) =>
                                        handleInputChange(
                                            "name_en",
                                            e.target.value,
                                            rowKey
                                        ),
                                },
                                {
                                    key: "products",
                                    autoFocus: false,
                                    options: products,
                                    type: "MultiDropDown",
                                    width: columnWidths.products,
                                    editable: true,
                                    required: true,
                                    onChange: (e, rowKey) =>
                                        handleInputChange(
                                            "products",
                                            e.target.value,
                                            rowKey
                                        ),
                                },
                                {
                                    key: "price",
                                    autoFocus: false,
                                    options: [],
                                    type: "Decimal",
                                    width: columnWidths.price,
                                    editable: true,
                                    required: true,
                                    onChange: (e, rowKey) =>
                                        handleInputChange(
                                            "price",
                                            e.target.value,
                                            rowKey
                                        ),
                                    header: (
                                        <div
                                            style={{
                                                display: "flex",
                                                alignItems: "center",
                                            }}
                                        >
                                            {translations.price}
                                        </div>
                                    ),
                                },
                                {
                                    key: "quantity",
                                    autoFocus: false,
                                    options: [],
                                    type: "Number",
                                    width: columnWidths.quantity,
                                    editable: true,
                                    required: true,
                                    onChange: (e, rowKey) =>
                                        handleInputChange(
                                            "quantity",
                                            e.target.value,
                                            rowKey
                                        ),
                                },
                            ]}
                            actions={[
                                ...(!!product.use_upcharge
                                    ? [
                                          {
                                              execute: (data) => {
                                                  let dataProducts =
                                                      products.filter((x) =>
                                                          data.products.includes(
                                                              x.value
                                                          )
                                                      );
                                                  dataProducts =
                                                      dataProducts.map((x) => {
                                                          return {
                                                              id: x.value,
                                                              value: x.value,
                                                              label: x.label,
                                                          };
                                                      });
                                                  let combo =
                                                      product.combos.find(
                                                          (x) => x.id == data.id
                                                      );
                                                  for (
                                                      let index = 0;
                                                      index <
                                                      dataProducts.length;
                                                      index++
                                                  ) {
                                                      const prod =
                                                          dataProducts[index];
                                                      if (
                                                          !!combo.upchargePrices &&
                                                          combo.upchargePrices.filter(
                                                              (x) =>
                                                                  x.product_id ==
                                                                  prod.value
                                                          ).length > 0
                                                      ) {
                                                          const p =
                                                              combo.upchargePrices.find(
                                                                  (x) =>
                                                                      x.product_id ==
                                                                      prod.value
                                                              );
                                                          dataProducts[
                                                              index
                                                          ].price = p.price;
                                                      }
                                                  }
                                                  setCurrentCombo({
                                                      id: data.id,
                                                      products: dataProducts,
                                                  });
                                                  setShowUpchargeDialog(true);
                                              },
                                              icon: "ki-pencil",
                                          },
                                      ]
                                    : []),
                                {
                                    execute: (data) => {
                                        const updatedCombos =
                                            product.combos.filter(
                                                (combo) => combo.id !== data.id
                                            );
                                        onComboChange("combos", updatedCombos);
                                    },
                                    icon: "ki-trash",
                                    style: { color: "red" },
                                },
                            ]}
                            onUpdate={(nodes) => onComboChange("combos", nodes)}
                            onDelete={null}
                        />
                    </>
                ) : (
                    <></>
                )}
            </div>
        </div>
    );
};

export default ProductCombo;

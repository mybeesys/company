import { useEffect, useRef, useState } from "react";
import Select from "react-select";
import makeAnimated from "react-select/animated";
import { getRowName } from "../lang/Utils";

const animatedComponents = makeAnimated();

const InventoryManagement = ({
    translations,
    dir,
    vendors,
    currentObject,
    onBasicChange,
}) => {
    const rootElement = document.getElementById("root");
    const listTaxurl = JSON.parse(rootElement.getAttribute("listTax-url"));

    const [taxOptions, setTaxOptions] = useState([]);
    const timeoutRef = useRef(null);

    const fetchTaxOptions = async () => {
        try {
            let response = await axios.get(listTaxurl);
            const taxes = response.data.map((tax) => ({
                label: getRowName(tax, dir),
                value: tax.id,
                default: tax.default,
            }));

            if (response.data.length > 0) {
                if (!currentObject.order_tax_id) {
                    const taxIndex = taxes.findIndex(
                        (x) => !!x.default && x.default == 1
                    );
                    currentObject["order_tax"] =
                        taxIndex == -1 ? taxes[0] : taxes[taxIndex];
                    currentObject["order_tax_id"] =
                        taxIndex == -1 ? taxes[0].value : taxes[taxIndex].value;
                } else {
                    currentObject["order_tax"] = taxes.find(
                        (x) => x.value == currentObject.order_tax_id
                    );
                }
            }
            setTaxOptions(taxes);
            onBasicChange("order_tax_id", currentObject["order_tax_id"]);
            onBasicChange("order_tax", currentObject["order_tax"]);
        } catch (error) {
            console.error("Error fetching tax options:", error);
        }
    };

    useEffect(() => {
        // fetchVendorOptions();
        fetchTaxOptions();
    }, []);

    const onPriceWithTaxChange = (key, value) => {
        const tax_id = currentObject.order_tax_id;
        onBasicChange(key, value);

        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = setTimeout(
            () =>
                axios
                    .get(
                        `${
                            window.location.origin
                        }/getPriceFromPriceWithTax?tax_id=${
                            tax_id || ""
                        }&price=${value || ""}`
                    )
                    .then((response) => {
                        if (response.data.new_price == -1) return;
                        onBasicChange(
                            "price",
                            response.data.new_price
                        );
                    }),
            500
        );
    };

    const onPriceChange = (key, value, option) => {
        const tax_id = key == "tax_id" ? value : currentObject.tax_id;
        const price = key == "price" ? value : currentObject.price;
        onBasicChange(key, value);
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }
        timeoutRef.current = setTimeout(
            () =>
                axios
                    .get(
                        `${window.location.origin}/priceWithTax?tax_id=${
                            !!tax_id ? tax_id : ""
                        }&price=${!!price ? price : ""}`
                    )
                    .then((response) => {
                        currentObject.orderPriceWithTax =
                            response.data.orderPriceWithTax;
                        onBasicChange(
                            "orderPriceWithTax",
                            currentObject.orderPriceWithTax
                        );
                        if (key == "tax_id")
                            updatePriceWithtax(!!tax_id ? tax_id : "");
                    }),
            500
        );
        if (key == "tax_id") {
            onBasicChange("tax", option);
        }
    };

    return (
        <>
            <div className="form-group">
                <div className="row">
                    <div className="col-6">
                        <label
                            htmlFor="alertQuantity"
                            className="col-form-label"
                        >
                            {translations.alertQuantity}
                        </label>
                        <input
                            type="number"
                            min="0"
                            className="form-control form-control-solid custom-height"
                            id="alertQuantity"
                            value={currentObject.alertQuantity || ""}
                            onChange={(e) =>
                                onBasicChange("alertQuantity", e.target.value)
                            }
                        />
                    </div>
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">
                            {translations.vendor}
                        </label>
                        <select
                            class="form-control form-control-solid  px-4 selectpicker"
                            value={currentObject.vendor_id}
                            onChange={(e) =>
                                onBasicChange("vendor_id", e.target.value)
                            }
                        >
                            {vendors.map((appType) => (
                                <option
                                    key={appType.value}
                                    value={appType.value}
                                >
                                    {appType.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            <div className="form-group">
                <div className="row">
                    <div className="col-6">
                        <label
                            htmlFor="defaultOrderQuantity"
                            className="col-form-label"
                        >
                            {translations.defaultOrderQuantity}
                        </label>
                        <input
                            type="number"
                            min="0"
                            step=".01"
                            className="form-control form-control-solid custom-height"
                            id="defaultOrderQuantity"
                            name="defaultOrderQuantity"
                            value={currentObject.defaultOrderQuantity || ""}
                            onChange={(e) =>
                                onBasicChange(
                                    "defaultOrderQuantity",
                                    e.target.value
                                )
                            }
                        />
                    </div>
                    <div className="col-6">
                        <label
                            htmlFor="price"
                            className="col-form-label"
                        >
                            {translations.orderDefaultPrice}
                        </label>
                        <input
                            type="number"
                            min="0"
                            step=".01"
                            className="form-control form-control-solid custom-height"
                            id="price"
                            value={currentObject.price || ""}
                            onChange={(e) =>
                                onBasicChange(
                                    "price",
                                    e.target.value
                                )
                            }
                        />
                    </div>
                </div>
            </div>

            <div className="form-group">
                <div className="row">
                    <div className="col-6">
                        <label htmlFor="orderTax" className="col-form-label">
                            {translations.orderTax}
                        </label>
                        <Select
                            id="tax_id"
                            isMulti={false}
                            options={taxOptions}
                            closeMenuOnSelect={true}
                            components={animatedComponents}
                            value={currentObject.order_tax}
                            onChange={(val) => {
                                onBasicChange("order_tax_id", val.value);
                                onBasicChange("order_tax", val);
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
                    <div className="col-6">
                        <label
                            htmlFor="orderPriceWithTax"
                            className="col-form-label"
                        >
                            {translations.orderPriceWithTax}
                        </label>
                        <input
                            type="number"
                            min="0"
                            step=".01"
                            className="form-control form-control-solid custom-height"
                            id="orderPriceWithTax"
                            value={currentObject.orderPriceWithTax || ""}
                            onChange={(e) =>
                                onPriceWithTaxChange(
                                    "orderPriceWithTax",
                                    e.target.value
                                )
                            }
                        />
                    </div>
                    <div className="col-6">
                        <div
                            className="form-check form-switch form-check-custom form-check-solid"
                            style={{ paddingTop: 20 }}
                        >
                            <input
                                className="form-check-input"
                                type="checkbox"
                                id="trackInventory"
                                style={{
                                    width: "35px",
                                    height: "40px",
                                    minWidth: "39px",
                                    minHeight: "22px",
                                }}
                                checked={currentObject.trackInventory == 1}
                                onChange={(e) =>
                                    onBasicChange(
                                        "trackInventory",
                                         e.target.checked ? 1 : 0 
                                    )
                                }
                            />
                            <label
                                className="col-form-label px-5"
                                htmlFor="trackInventory"
                            >
                                {translations.trackInventory}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default InventoryManagement;

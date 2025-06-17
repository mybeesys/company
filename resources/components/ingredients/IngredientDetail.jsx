import React, { useEffect, useState } from "react";
import SweetAlert2 from "react-sweetalert2";
import IngredientBasicInfo from "./IngredientBasicInfo";
import UnitTransferIngredient from "./UnitTransferIngredient";
import axios from "axios";
import InventoryManagement from "./InventoryManagement";
import IngredinsEstablishment from "./IngredinsEstablishment";

const IngredientDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    let ingredient = JSON.parse(rootElement.getAttribute("ingredient"));
    const [currentObject, setcurrentObject] = useState(ingredient);
    const [defaultMenu, setdefaultMenu] = useState([
        { key: "basicInfo", visible: true },
        { key: "Unit", visible: false },
        { key: "PriceTier", visible: false },
        { key: "inventory", visible: false },
    ]);
    const [disableSubmitButton, setSubmitdisableButton] = useState(false);
    const [showAlert, setShowAlert] = useState(false);
    const [menu, setMenu] = useState(defaultMenu);
    const [units, setUnits] = useState([]);
    const [vendor, setVendor] = useState([]);
    const [unitTransfer, setUnitTransfers] = useState([]);
    const [productUnit, setProductUnit] = useState();
    const [currentTab, setCurrentTab] = useState(1);

    const [selectedEstablishments, setSelectedEstablishments] = useState([]);

    const handleEstablishmentChange = (selectedIds) => {
        setSelectedEstablishments(selectedIds);
    };
    const handleChange = (index, value) => {
        let currentMenu = [...menu];
        currentMenu[index].visible = value;
        setMenu([...currentMenu]);
    };

    const onBasicChange = (key, value) => {
        currentObject[key] = value;
        setcurrentObject({ ...currentObject });
    };

    const clickSubmit = () => {
        let btnSubmit = document.getElementById("btnMainSubmit");
        btnSubmit.click();
    };

    const cancel = () => {
        window.location.href = "/ingredient";
    };

    const saveChanges = async () => {
        try {
            setSubmitdisableButton(true);

            let r = { ...currentObject };
            r["active"] = r["active"] ? r["active"] : 0;

            let transfer = unitTransfer.filter((object) => object.id != -100);

            if (!!productUnit) {
                if (!!!productUnit.id)
                    transfer.push({
                        id: 0,
                        unit1: productUnit.unit1,
                        unit2: -100,
                        transfer: -100,
                        primary: -100,
                    });
                else transfer.push(productUnit);
            }

            const sortedItems = [...transfer].sort((a, b) => a.id - b.id);
            r["transfer"] = [...sortedItems];

            const response = await axios.post("/ingredient", r);
            if (response.data.message == "Done") {
                window.location.href = "/ingredient";
            } else {
                setShowAlert(true);
                Swal.fire({
                    show: showAlert,
                    title: "Error",
                    text: translations.technicalerror,
                    icon: "error",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false,
                }).then(() => {
                    setShowAlert(false);
                });
            }
        } catch (error) {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                text: translations.technicalerror,
                icon: "error",
                timer: 2000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false);
            });
            console.error("There was an error adding the product!", error);
        }
        setSubmitdisableButton(false);
    };

    const handleMainSubmit = (event) => {
        event.preventDefault();
        event.stopPropagation();
        const form = event.currentTarget;
        if (form.checkValidity() === false) {
            var menu = [...defaultMenu];
            menu[0].visible = true;
            setMenu([...menu]);

            form.classList.add("was-validated");
            return;
        } else {
            saveChanges();
        }
    };

    const handleMainUnit = (value) => {
        setProductUnit(value);
    };

    const parentHandleTransfer = (result) => {
        setUnitTransfers([...result]);
    };

    useEffect(() => {
        const fetchData = async () => {
            const res = await axios.get("/getVendors");
            const vendors = res.data.map((e) => {
                return {
                    name: e.name,
                    value: e.id,
                };
            });
            setVendor(vendors);

            const response = await axios.get(
                "/getUnitsTransferList/ingredient/" + currentObject.id
            );

            const units = response.data;
            const unitsResult = units.map((e) => {
                return { label: e.unit1, value: e.id };
            });
            setUnits(unitsResult);

            let mainUnit = units.find(function (element) {
                return element.unit2 == null;
            });

            setProductUnit(mainUnit);

            const unitTransfers = response.data;
            const unitTransfersResult =
                unitTransfers.length > 0
                    ? unitTransfers
                          .filter((e) => e.unit2 != null)
                          .map((e) => {
                              return {
                                  id: e.id,
                                  transfer: e.transfer,
                                  unit1: e.unit1,
                                  unit2: e.unit2,
                                  primary: e.primary,
                                  newid: e.newid,
                              };
                          })
                    : [];
            setUnitTransfers(unitTransfersResult);
        };
        fetchData().catch(console.error);
    }, []);

    return (
        <div>
            <SweetAlert2 />
            <div className="container">
                <div className="row">
                    <div className="col-6">
                        <div className="d-flex align-items-center gap-2 gap-lg-3">
                            <h1>{`${translations.Add} ${translations.Ingredient}`}</h1>
                        </div>
                    </div>
                    <div
                        className="col-6"
                        style={{ justifyContent: "end", display: "flex" }}
                    >
                        <div
                            className="flex-center"
                            style={{ display: "flex" }}
                        >
                            <button
                                onClick={clickSubmit}
                                disabled={disableSubmitButton}
                                className="btn btn-primary mx-2"
                                style={{ width: "12rem" }}
                            >
                                {translations.savechanges}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="separator d-flex flex-center my-6">
                <span className="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div className="row">
                <form
                    noValidate
                    validated={true}
                    className="needs-validation"
                    onSubmit={handleMainSubmit}
                >
                    <div className="container">
                        <div className="row">
                            <div className="col-5">
                                <IngredientBasicInfo
                                    translations={translations}
                                    currentObject={currentObject}
                                    onBasicChange={onBasicChange}
                                    units={units}
                                    dir={dir}
                                />
                            </div>
                            <div className="col-7">
                                <div className="card-toolbar">
                                    <ul
                                        className="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold"
                                        role="tablist"
                                    >
                                        <li
                                            className="nav-item"
                                            role="presentation"
                                        >
                                            <a
                                                id="Unit_tab"
                                                onClick={() => setCurrentTab(1)}
                                                className={`nav-link justify-content-center text-active-gray-800 ${
                                                    currentTab === 1
                                                        ? "active"
                                                        : ""
                                                }`}
                                                data-bs-toggle="tab"
                                                role="tab"
                                                href="#Unit"
                                                aria-selected={currentTab === 1}
                                            >
                                                {translations.Unit}
                                            </a>
                                        </li>
                                        <li
                                            className="nav-item"
                                            role="presentation"
                                        >
                                            <a
                                                id="PriceTier_tab"
                                                onClick={() => setCurrentTab(2)}
                                                className={`nav-link justify-content-center text-active-gray-800 ${
                                                    currentTab === 2
                                                        ? "active"
                                                        : ""
                                                }`}
                                                data-bs-toggle="tab"
                                                role="tab"
                                                href="#Management"
                                                aria-selected={currentTab === 2}
                                            >
                                                {translations.PriceTier ||
                                                    "المخزون"}
                                            </a>
                                        </li>
                                        <li
                                            className="nav-item"
                                            role="presentation"
                                        >
                                            <a
                                                id="PriceTier_tab"
                                                onClick={() => setCurrentTab(3)}
                                                className={`nav-link justify-content-center text-active-gray-800 ${
                                                    currentTab === 3
                                                        ? "active"
                                                        : ""
                                                }`}
                                                data-bs-toggle="tab"
                                                role="tab"
                                                href="#inventory"
                                                aria-selected={currentTab === 3}
                                            >
                                                {translations.inventory ||
                                                    "المستودع"}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div className="tab-content">
                                    <div
                                        id="Unit"
                                        className={`card-body p-0 tab-pane fade ${
                                            currentTab === 1
                                                ? "show active"
                                                : ""
                                        }`}
                                        role="tabpanel"
                                        aria-labelledby="Unit_tab"
                                    >
                                        <UnitTransferIngredient
                                            translations={translations}
                                            unitTransfer={unitTransfer}
                                            unitTree={units}
                                            parentHandle={parentHandleTransfer}
                                            handleMainUnit={handleMainUnit}
                                            productUnit={productUnit}
                                            dir={dir}
                                        />
                                    </div>

                                    <div
                                        id="Management"
                                        className={`card-body p-0 tab-pane fade ${
                                            currentTab === 2
                                                ? "show active"
                                                : ""
                                        }`}
                                        role="tabpanel"
                                        aria-labelledby="Management_tab"
                                    >
                                        <InventoryManagement
                                            translations={translations}
                                            currentObject={currentObject}
                                            onBasicChange={onBasicChange}
                                            dir={dir}
                                            vendors={vendor}
                                        />
                                    </div>
                                </div>

                                <div class="tab-content">
                                    <div
                                        id="inventory"
                                        className={`card-body p-0 tab-pane fade ${
                                            currentTab === 3
                                                ? "show active"
                                                : ""
                                        }`}
                                        role="tabpanel"
                                        aria-labelledby="inventory_tab"
                                    >
                                        <IngredinsEstablishment
                                            translations={translations}
                                            dir={dir}
                                            currentObject={currentObject}
                                            onEstablishmentChange={
                                                handleEstablishmentChange
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                            <input type="submit" id="btnMainSubmit" hidden />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default IngredientDetail;

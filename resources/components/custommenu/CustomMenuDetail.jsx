import React, { useEffect, useState } from "react";
import SweetAlert2 from "react-sweetalert2";
import CustomMenuTime from "./CustomMenuTime";
import CustomMenuProduct from "./CustomMenuProduct";
import CustomMenuBasicInfo from "./CustomMenuBasicInfo";
import { defaultMenuTime } from "./DefaultTimesSlots";

const CustomMenuDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    let custommenu = JSON.parse(rootElement.getAttribute("custommenu"));
    const [defaultMenu, setdefaultMenu] = useState([
        { key: "basicInfo", visible: true },
        { key: "times", visible: false },
        { key: "products", visible: false },
    ]);
    const [disableSubmitButton, setSubmitdisableButton] = useState(false);
    const [menu, setMenu] = useState(defaultMenu);
    const [currentObject, setcurrentObject] = useState(custommenu);
    const [customMenuDates, setCustomMenuDates] = useState(
        !!custommenu.dates ? custommenu.dates[0] : defaultMenuTime[0]
    );
    const [selectedProductKeys, setSelectedProductKeys] = useState(
        !!custommenu.products ? custommenu.products : []
    );
    const [showAlert, setShowAlert] = useState(false);
    const [currentTab, setCurrentTab] = useState(1);
    const [selectedTier, setSelectedTier] = useState(null);

    useEffect(() => {}, []);

    const saveChanges = async () => {
        try {
            setSubmitdisableButton(true);
            let r = { ...currentObject };
            r["dates"] = { ...customMenuDates };
            r["products"] = [...selectedProductKeys];
            r["price_tier_id"] = selectedTier ? selectedTier.value : null;

            const response = await axios.post("/customMenu", r);
            if (response.data.message == "Done") {
                window.location.href = "/customMenu";
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
                    setShowAlert(false); // Reset the state after alert is dismissed
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
                setShowAlert(false); // Reset the state after alert is dismissed
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

    const handleChange = (index, value) => {
        let currentMenu = [...menu];
        currentMenu[index].visible = value;
        setMenu([...currentMenu]);
    };

    const clickSubmit = () => {
        let btnSubmit = document.getElementById("btnMainSubmit");
        btnSubmit.click();
    };

    const cancel = () => {
        window.location.href = "/customMenu";
    };

    const onDateTimeChange = (type, key, value, day_no) => {
        if (type == "T") {
            let index = customMenuDates.times.findIndex(
                (x) => x.day_no == day_no
            );
            customMenuDates.times[index][key] = !!value
                ? value.toLocaleTimeString("en-US", { hour12: false })
                : null;
        } else if (type == "D") {
            customMenuDates[key] = !!value
                ? `${value.getFullYear()}-${(value.getMonth() + 1)
                      .toString()
                      .padStart(2, "0")}-${value
                      .getDate()
                      .toString()
                      .padStart(2, "0")}`
                : null;
        } else if (type == "C") {
            let index = customMenuDates.times.findIndex(
                (x) => x.day_no == day_no
            );
            customMenuDates.times[index][key] = value;
        }
        setCustomMenuDates({ ...customMenuDates });
    };

    const onBasicChange = (key, value) => {
        currentObject[key] = value;
        setcurrentObject({ ...currentObject });
    };

    const onProductSelectionChange = (keys) => {
        let prods = keys.map((x) => {
            return { product_id: x };
        });
        setSelectedProductKeys(prods);
    };

    return (
        <div>
            <SweetAlert2 />
            <div class="container">
                <div class="row">
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            <h1>{`${translations.Add} ${translations.customMenu}`}</h1>
                        </div>
                    </div>
                    <div
                        class="col-6"
                        style={{ "justify-content": "end", display: "flex" }}
                    >
                        <div class="flex-center" style={{ display: "flex" }}>
                            <button
                                onClick={clickSubmit}
                                disabled={disableSubmitButton}
                                class="btn btn-primary mx-2"
                                style={{ width: "12rem" }}
                            >
                                {translations.savechanges}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div class="row">
                <form
                    noValidate
                    validated={true}
                    class="needs-validation"
                    onSubmit={handleMainSubmit}
                >
                    <div class="container">
                        <div class="row">
                            <div class="col-5">
                                <CustomMenuBasicInfo
                                    translations={translations}
                                    currentObject={currentObject}
                                    onBasicChange={onBasicChange}
                                    dir={dir}
                                />
                            </div>
                            <div class="col-7">
                                <div class="card-toolbar ">
                                    <ul
                                        class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold"
                                        role="tablist"
                                    >
                                        {menu.map((m, index) => {
                                            return index == 0 ? (
                                                <></>
                                            ) : (
                                                <li
                                                    class="nav-item"
                                                    role="presentation"
                                                >
                                                    <a
                                                        id={`${m.key}_tab`}
                                                        onClick={(e) =>
                                                            setCurrentTab(index)
                                                        }
                                                        class={`nav-link justify-content-center text-active-gray-800 ${
                                                            currentTab == index
                                                                ? "active"
                                                                : ""
                                                        }`}
                                                        data-bs-toggle="tab"
                                                        role="tab"
                                                        href={`#${m.key}`}
                                                        aria-selected="true"
                                                    >
                                                        {translations[m.key]}
                                                    </a>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </div>
                                <div class="tab-content">
                                    <div
                                        id="times"
                                        class="card-body p-0 tab-pane fade show active"
                                        role="tabpanel"
                                        aria-labelledby="times_tab"
                                    >
                                        <CustomMenuTime
                                            translations={translations}
                                            customMenuDates={customMenuDates}
                                            onDateTimeChange={onDateTimeChange}
                                        />
                                    </div>
                                </div>

                                <div class="tab-content ">
                                    <div
                                        id="products"
                                        class="card-body p-0 tab-pane fade show"
                                        role="tabpanel"
                                        aria-labelledby="products_tab"
                                    >
                                        <CustomMenuProduct
                                            translations={translations}
                                            customMenuProducts={
                                                selectedProductKeys
                                            }
                                            onProductSelectionChange={
                                                onProductSelectionChange
                                            }
                                            dir={dir}
                                            onPriceTierChange={setSelectedTier}
                                            custommenu={custommenu}
                                        />
                                    </div>
                                </div>
                            </div>
                            <input
                                type="submit"
                                id="btnMainSubmit"
                                hidden
                            ></input>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CustomMenuDetail;

import { QRCodeCanvas } from "qrcode.react";
import { useEffect, useState, useMemo } from "react";
import { BlockPicker } from "react-color";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";
import axios from "axios";

const animatedComponents = makeAnimated();

/** When PHP emits notices before JSON, axios may leave the body as a string; pull the token from the trailing JSON object. */
function extractMenuTokenFromResponse(data) {
    if (data && typeof data === "object" && typeof data.token === "string") {
        return data.token;
    }
    if (data && typeof data === "object" && typeof data.data?.token === "string") {
        return data.data.token;
    }
    if (typeof data === "string") {
        const start = data.lastIndexOf("{");
        if (start !== -1) {
            try {
                const parsed = JSON.parse(data.slice(start));
                if (typeof parsed.token === "string") {
                    return parsed.token;
                }
            } catch {
                /* ignore */
            }
        }
    }
    return null;
}

const MenuQR = ({ translations, dir }) => {
    const rootElement = document.getElementById("root");
    const logourl = rootElement.getAttribute("logo-url");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const customMenusUrl = JSON.parse(rootElement.getAttribute("custom-menus-url"));

    const [products, setProducts] = useState([]);
    const [establishments, setEstablishments] = useState([]);
    const [customMenus, setCustomMenus] = useState([]);

    const [currentObject, setCurrentObject] = useState({
        selectedEstablishments: [],
        selectedProducts: [],
        menuSections: {
            todays_menu: true,
            location: true,
            smart_menu: true,
            allergy_info: true,
            photos: true,
            feedback: true,
            info: true,
        },
        color: "#000000",
        showLogo: true,
        title: "",
        subTitle: "",
        customMenu: null,
        map_lat: "",
        map_lng: "",
        map_label: "",
        allergyFile: null,
    });

    const [qrInfo, setQrInfo] = useState({});

    const getTranslatedLabel = (key) => {
        const fallbackLabels = {
            todays_menu: dir === "rtl" ? "قائمة اليوم" : "Today's Menu",
            location: dir === "rtl" ? "الموقع الجغرافي" : "Location",
            smart_menu: dir === "rtl" ? "المنيو الذكي" : "Smart Menu",
            allergy_info: dir === "rtl" ? "معلومات الحساسية" : "Allergy Info",
            photos: dir === "rtl" ? "معرض الصور" : "Photos",
            feedback: dir === "rtl" ? "التقييمات والآراء" : "Feedback",
            info: dir === "rtl" ? "معلومات التواصل" : "Information",
        };

        return translations[key] || fallbackLabels[key] || key;
    };

    useEffect(() => {
        getInitialData();
    }, []);

    const getInitialData = async () => {
        try {
            const prodResponse = await axios.get(urlList);
            let accumulatedProducts = [];
            prodResponse.data.forEach((category) => {
                if (Array.isArray(category.children_with_products)) {
                    category.children_with_products.forEach((subCategory) => {
                        if (Array.isArray(subCategory.products_for_sale)) {
                            accumulatedProducts = [...accumulatedProducts, ...subCategory.products_for_sale];
                        }
                    });
                }
            });

            setProducts(
                accumulatedProducts.map((p) => ({
                    value: p.id,
                    label: dir === "rtl" ? p.name_ar : p.name_en,
                }))
            );

            const estResponse = await axios.get("/searchEstablishments?query=");
            const estData = Array.isArray(estResponse.data) ? estResponse.data : [];
            setEstablishments(
                estData.map((e) => ({
                    value: e.id,
                    label: dir === "rtl" ? e.name_ar || e.name : e.name_en || e.name,
                }))
            );

            const cm = await axios.get(customMenusUrl);
            setCustomMenus(
                (cm.data || []).map((m) => ({
                    value: m.id,
                    label: dir === "rtl" ? m.name_ar : m.name_en,
                }))
            );
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    };

    const onChange = (key, val) => {
        setCurrentObject((prev) => ({ ...prev, [key]: val }));
    };

    const establishmentOptions = useMemo(
        () => [
            { value: "all", label: dir === "rtl" ? "اختيار كافة الأفرع" : "Select All Establishments" },
            ...establishments,
        ],
        [establishments, dir]
    );

    const productOptions = useMemo(
        () => [
            { value: "all", label: dir === "rtl" ? "اختيار كافة المنتجات" : "Select All Products" },
            ...products,
        ],
        [products, dir]
    );

    const handleSelectChange = (field, selected, allOptions, originalData) => {
        const vals = selected || [];
        if (vals.some((opt) => opt.value === "all")) {
            onChange(field, originalData);
        } else {
            onChange(field, vals);
        }
    };

    const fillLocationFromBrowser = () => {
        if (!navigator.geolocation) {
            window.Swal.fire({
                icon: "warning",
                title: dir === "rtl" ? "غير مدعوم" : "Not supported",
                text: dir === "rtl" ? "المتصفح لا يدعم تحديد الموقع" : "Geolocation is not supported",
            });
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                onChange("map_lat", String(pos.coords.latitude));
                onChange("map_lng", String(pos.coords.longitude));
            },
            () => {
                window.Swal.fire({
                    icon: "error",
                    title: dir === "rtl" ? "تعذر الموقع" : "Location error",
                    text: dir === "rtl" ? "اسمح بالوصول للموقع أو أدخل الإحداثيات يدوياً" : "Allow location or enter coordinates manually",
                });
            }
        );
    };

    const validateBeforeGenerate = () => {
        const s = currentObject.menuSections;
        if (s.todays_menu && !currentObject.customMenu) {
            window.Swal.fire({
                icon: "error",
                title: dir === "rtl" ? "قائمة اليوم" : "Today's menu",
                text: dir === "rtl" ? "اختر قائمة مخصصة من القائمة المنسدلة" : "Please select a custom menu",
            });
            return false;
        }
        if (s.location) {
            if (currentObject.map_lat === "" || currentObject.map_lng === "") {
                window.Swal.fire({
                    icon: "error",
                    title: dir === "rtl" ? "الموقع" : "Location",
                    text:
                        dir === "rtl"
                            ? "أدخل خط العرض والطول أو استخدم زر موقعي"
                            : "Enter latitude & longitude or use My location",
                });
                return false;
            }
        }
        if (s.allergy_info && !currentObject.allergyFile) {
            window.Swal.fire({
                icon: "error",
                title: dir === "rtl" ? "مسببات الحساسية" : "Allergy info",
                text: dir === "rtl" ? "ارفع ملف PDF أو صورة" : "Please upload a PDF or image file",
            });
            return false;
        }
        return true;
    };

    const generateQR = async () => {
        if (currentObject.selectedEstablishments.length === 0) {
            window.Swal.fire({
                title: "Error",
                html:
                    (translations.establishment || (dir === "rtl" ? "المنشأة" : "Establishment")) +
                    " " +
                    (dir === "rtl" ? "مطلوبة" : "is required"),
                icon: "error",
                timer: 4000,
            });
            return;
        }

        if (!validateBeforeGenerate()) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append("est_ids", JSON.stringify(currentObject.selectedEstablishments.map((e) => e.value)));
            formData.append("products", JSON.stringify(currentObject.selectedProducts.map((p) => p.value)));
            formData.append("title", currentObject.title);
            formData.append("sub_title", currentObject.subTitle);
            formData.append("section_flags", JSON.stringify(currentObject.menuSections));

            if (currentObject.customMenu) {
                formData.append("custom_menu_id", String(currentObject.customMenu.value));
            }
            if (currentObject.menuSections.location) {
                formData.append("map_lat", String(currentObject.map_lat));
                formData.append("map_lng", String(currentObject.map_lng));
                formData.append("map_label", currentObject.map_label || "");
            }
            if (currentObject.allergyFile) {
                formData.append("allergy_document", currentObject.allergyFile);
            }

            const response = await axios.post("/generate-menu-token", formData, {
                headers: { Accept: "application/json" },
            });
            const payload = response.data;
            const token = extractMenuTokenFromResponse(payload);

            if (!token) {
                console.error("generate-menu-token: missing token in response", payload);
                window.Swal.fire({
                    icon: "error",
                    title: "Error",
                    text:
                        dir === "rtl"
                            ? "الخادم لم يُرجع رمز القائمة. جرّب تحديث الصفحة، وتأكد من ترحيل قاعدة البيانات للمستأجر."
                            : "Server did not return a menu token. Refresh the page and ensure tenant migrations have run.",
                });
                return;
            }

            setQrInfo({
                id: `qr-code-canvas`,
                url: `${window.location.origin}/menuSimple/${token}`,
                color: currentObject.color,
                logo: !currentObject.showLogo ? {} : { src: logourl, height: 56, width: 56, excavate: true },
            });
        } catch (error) {
            const msg =
                error?.response?.data?.message ||
                (dir === "rtl" ? "تعذر إنشاء الرابط" : "Could not generate link");
            window.Swal.fire({ icon: "error", title: "Error", text: msg });
            console.error("Error generating QR:", error);
        }
    };

    return (
        <div className="row">
            <div className="col-md-5">
                <div className="card-body" dir={dir}>
                    <div className="d-flex align-items-center mb-4 pt-3 border-bottom pb-3">
                        <label className="fs-6 fw-semibold me-3" style={{ width: "150px" }}>
                            {translations.showLogo || "إظهار اللوجو"}
                        </label>
                        <InputSwitch checked={currentObject.showLogo} onChange={(e) => onChange("showLogo", e.value)} />
                    </div>

                    <div className="form-group mb-4">
                        <label className="col-form-label fw-bold">{translations.establishment || "الأفرع"}</label>
                        <Select
                            options={establishmentOptions}
                            isMulti
                            value={currentObject.selectedEstablishments}
                            onChange={(val) =>
                                handleSelectChange("selectedEstablishments", val, establishmentOptions, establishments)
                            }
                            components={animatedComponents}
                            className="basic-multi-select"
                            placeholder={dir === "rtl" ? "اختر الأفرع..." : "Select establishments..."}
                        />
                    </div>

                    <div className="form-group mb-4">
                        <label className="col-form-label fw-bold">{translations.products || "المنتجات"}</label>
                        <Select
                            options={productOptions}
                            isMulti
                            value={currentObject.selectedProducts}
                            onChange={(val) => handleSelectChange("selectedProducts", val, productOptions, products)}
                            components={animatedComponents}
                            className="basic-multi-select"
                            placeholder={dir === "rtl" ? "اختر المنتجات..." : "Select products..."}
                        />
                    </div>

                    <div className="row mb-4">
                        <div className="col-6">
                            <label className="col-form-label">{translations.title || "العنوان الرئيسي"}</label>
                            <input
                                type="text"
                                className="form-control form-control-solid"
                                value={currentObject.title}
                                onChange={(e) => onChange("title", e.target.value)}
                            />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.subTitle || "العنوان الفرعي"}</label>
                            <input
                                type="text"
                                className="form-control form-control-solid"
                                value={currentObject.subTitle}
                                onChange={(e) => onChange("subTitle", e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="col-12 mt-4 border p-3 rounded bg-light">
                        <h6 className="mb-3 fw-bold border-bottom pb-2 text-primary">
                            {dir === "rtl" ? "أقسام المنيو الظاهرة" : "Visible Menu Sections"}
                        </h6>
                        {Object.keys(currentObject.menuSections).map((key) => (
                            <div
                                key={key}
                                className="d-flex align-items-center justify-content-between mb-2 p-2 border-bottom"
                            >
                                <label className="mb-0 fw-semibold">{getTranslatedLabel(key)}</label>
                                <InputSwitch
                                    checked={currentObject.menuSections[key]}
                                    onChange={(e) =>
                                        onChange("menuSections", { ...currentObject.menuSections, [key]: e.value })
                                    }
                                />
                            </div>
                        ))}
                    </div>

                    {currentObject.menuSections.todays_menu && (
                        <div className="form-group mb-4 mt-3">
                            <label className="col-form-label fw-bold text-danger">
                                * {dir === "rtl" ? "قائمة مخصصة (Custom Menu)" : "Custom menu"}
                            </label>
                            <Select
                                options={customMenus}
                                value={currentObject.customMenu}
                                onChange={(v) => onChange("customMenu", v)}
                                placeholder={dir === "rtl" ? "اختر القائمة المخصصة..." : "Select custom menu..."}
                            />
                        </div>
                    )}

                    {currentObject.menuSections.location && (
                        <div className="border rounded p-3 mb-3 bg-white">
                            <h6 className="fw-bold mb-3">{dir === "rtl" ? "موقع الخريطة" : "Map location"}</h6>
                            <button type="button" className="btn btn-sm btn-light-primary mb-3" onClick={fillLocationFromBrowser}>
                                {dir === "rtl" ? "استخدام موقعي الحالي" : "Use my current location"}
                            </button>
                            <div className="row g-2">
                                <div className="col-6">
                                    <label className="form-label fs-7">Latitude</label>
                                    <input
                                        className="form-control form-control-sm"
                                        value={currentObject.map_lat}
                                        onChange={(e) => onChange("map_lat", e.target.value)}
                                    />
                                </div>
                                <div className="col-6">
                                    <label className="form-label fs-7">Longitude</label>
                                    <input
                                        className="form-control form-control-sm"
                                        value={currentObject.map_lng}
                                        onChange={(e) => onChange("map_lng", e.target.value)}
                                    />
                                </div>
                                <div className="col-12">
                                    <label className="form-label fs-7">{dir === "rtl" ? "وصف الموقع" : "Place label"}</label>
                                    <input
                                        className="form-control form-control-sm"
                                        value={currentObject.map_label}
                                        onChange={(e) => onChange("map_label", e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {currentObject.menuSections.allergy_info && (
                        <div className="border rounded p-3 mb-3 bg-white">
                            <label className="form-label fw-bold">{dir === "rtl" ? "ملف مسببات الحساسية" : "Allergy document"}</label>
                            <input
                                type="file"
                                className="form-control"
                                accept=".pdf,image/png,image/jpeg"
                                onChange={(e) => onChange("allergyFile", e.target.files?.[0] || null)}
                            />
                        </div>
                    )}

                    <div className="mt-5 border-top pt-3">
                        <label className="col-form-label d-block mb-2 fw-bold">{translations.color || "لون الـ QR"}</label>
                        <BlockPicker color={currentObject.color} onChange={(color) => onChange("color", color.hex)} />
                    </div>

                    <button onClick={generateQR} className="btn btn-primary btn-lg w-100 mt-4 shadow-sm">
                        {translations.generateQr || "إنشاء كود QR"}
                    </button>
                </div>
            </div>

            <div className="col-md-7 d-flex justify-content-center align-items-start pt-5">
                <div
                    className="p-4 border rounded shadow bg-white text-center"
                    style={{ width: "400px", position: "sticky", top: "20px" }}
                >
                    <QRCodeCanvas
                        id={qrInfo.id}
                        value={qrInfo.url || " "}
                        size={320}
                        fgColor={qrInfo.color}
                        imageSettings={qrInfo.logo}
                        level="H"
                    />
                    {qrInfo.url && (
                        <div className="mt-4 d-flex justify-content-center gap-3">
                            <button
                                className="btn btn-secondary"
                                onClick={() => {
                                    const canvas = document.getElementById(qrInfo.id);
                                    const link = document.createElement("a");
                                    link.download = "qr-menu.png";
                                    link.href = canvas.toDataURL();
                                    link.click();
                                }}
                            >
                                📥 {dir === "rtl" ? "تحميل" : "Download"}
                            </button>
                            <a href={qrInfo.url} target="_blank" rel="noreferrer" className="btn btn-primary">
                                🔗 {dir === "rtl" ? "معاينة" : "Preview"}
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default MenuQR;

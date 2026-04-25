import { QRCodeCanvas } from "qrcode.react";
import { useEffect, useState, useMemo } from "react";
import { BlockPicker } from "react-color";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";
import axios from "axios";

const animatedComponents = makeAnimated();

const MenuQR = ({ translations, dir }) => {
    const rootElement = document.getElementById("root");
    const logourl = rootElement.getAttribute("logo-url");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));

    const [products, setProducts] = useState([]);
    const [establishments, setEstablishments] = useState([]);

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
        subTitle: ""
    });

    const [qrInfo, setQrInfo] = useState({});

    // دالة خاصة لجلب الترجمة الصحيحة أو البديل العربي
    const getTranslatedLabel = (key) => {
        // إذا المبرمج نسي يضيفها في ملف الترجمة، نضعها هنا كاحتياط
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

            setProducts(accumulatedProducts.map(p => ({
                value: p.id,
                label: dir === "rtl" ? p.name_ar : p.name_en
            })));

            const estResponse = await axios.get("/searchEstablishments?query=");
            const estData = Array.isArray(estResponse.data) ? estResponse.data : [];
            setEstablishments(estData.map(e => ({
                value: e.id,
                label: dir === "rtl" ? (e.name_ar || e.name) : (e.name_en || e.name)
            })));

        } catch (error) {
            console.error("Error fetching data:", error);
        }
    };

    const onChange = (key, val) => {
        setCurrentObject((prev) => ({ ...prev, [key]: val }));
    };

    const establishmentOptions = useMemo(() => [
        { value: "all", label: dir === "rtl" ? "اختيار كافة الأفرع" : "Select All Establishments" },
        ...establishments
    ], [establishments, dir]);

    const productOptions = useMemo(() => [
        { value: "all", label: dir === "rtl" ? "اختيار كافة المنتجات" : "Select All Products" },
        ...products
    ], [products, dir]);

    const handleSelectChange = (field, selected, allOptions, originalData) => {
        const vals = selected || [];
        if (vals.some(opt => opt.value === "all")) {
            onChange(field, originalData);
        } else {
            onChange(field, vals);
        }
    };

    const generateQR = async () => {
        if (currentObject.selectedEstablishments.length === 0) {
            window.Swal.fire({
                title: "Error",
                html: (translations.establishment || (dir === "rtl" ? "المنشأة" : "Establishment")) + " " + (dir === "rtl" ? "مطلوبة" : "is required"),
                icon: "error",
                timer: 4000,
            });
            return;
        }

        try {
            const formData = new FormData();
            formData.append("est_ids", JSON.stringify(currentObject.selectedEstablishments.map(e => e.value)));
            formData.append("products", JSON.stringify(currentObject.selectedProducts.map(p => p.value)));
            formData.append("title", currentObject.title);
            formData.append("sub_title", currentObject.subTitle);

            const response = await axios.post("/generate-menu-token", formData);
            const { token } = response.data;

            const sectionParams = new URLSearchParams(
                Object.entries(currentObject.menuSections).reduce((acc, [key, val]) => {
                    acc[key] = val ? "1" : "0";
                    return acc;
                }, {})
            ).toString();

            setQrInfo({
                id: `qr-code-canvas`,
                url: `${window.location.origin}/menuSimple/${token}?${sectionParams}`,
                color: currentObject.color,
                logo: !currentObject.showLogo ? {} : { src: logourl, height: 56, width: 56, excavate: true }
            });
        } catch (error) {
            console.error("Error generating QR:", error);
        }
    };

    return (
        <div className="row">
            <div className="col-md-5">
                <div className="card-body" dir={dir}>
                    <div className="d-flex align-items-center mb-4 pt-3 border-bottom pb-3">
                        <label className="fs-6 fw-semibold me-3" style={{ width: "150px" }}>{translations.showLogo || "إظهار اللوجو"}</label>
                        <InputSwitch checked={currentObject.showLogo} onChange={(e) => onChange("showLogo", e.value)} />
                    </div>

                    <div className="form-group mb-4">
                        <label className="col-form-label fw-bold">{translations.establishment || "الأفرع"}</label>
                        <Select
                            options={establishmentOptions}
                            isMulti
                            value={currentObject.selectedEstablishments}
                            onChange={(val) => handleSelectChange("selectedEstablishments", val, establishmentOptions, establishments)}
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
                            <input type="text" className="form-control form-control-solid" value={currentObject.title} onChange={(e) => onChange("title", e.target.value)} />
                        </div>
                        <div className="col-6">
                            <label className="col-form-label">{translations.subTitle || "العنوان الفرعي"}</label>
                            <input type="text" className="form-control form-control-solid" value={currentObject.subTitle} onChange={(e) => onChange("subTitle", e.target.value)} />
                        </div>
                    </div>

                    <div className="col-12 mt-4 border p-3 rounded bg-light">
                        <h6 className="mb-3 fw-bold border-bottom pb-2 text-primary">
                            {dir === "rtl" ? "أقسام المنيو الظاهرة" : "Visible Menu Sections"}
                        </h6>
                        {Object.keys(currentObject.menuSections).map((key) => (
                            <div key={key} className="d-flex align-items-center justify-content-between mb-2 p-2 border-bottom">
                                <label className="mb-0 fw-semibold">{getTranslatedLabel(key)}</label>
                                <InputSwitch checked={currentObject.menuSections[key]} onChange={(e) => onChange("menuSections", { ...currentObject.menuSections, [key]: e.value })} />
                            </div>
                        ))}
                    </div>

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
                <div className="p-4 border rounded shadow bg-white text-center" style={{ width: "400px", position: "sticky", top: "20px" }}>
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
                            <button className="btn btn-secondary" onClick={() => {
                                const canvas = document.getElementById(qrInfo.id);
                                const link = document.createElement("a");
                                link.download = "qr-menu.png";
                                link.href = canvas.toDataURL();
                                link.click();
                            }}>📥 {dir === "rtl" ? "تحميل" : "Download"}</button>
                            <a href={qrInfo.url} target="_blank" rel="noreferrer" className="btn btn-primary">🔗 {dir === "rtl" ? "معاينة" : "Preview"}</a>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default MenuQR;

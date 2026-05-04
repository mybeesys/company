import { QRCodeCanvas } from "qrcode.react";
import { useEffect, useState, useMemo } from "react";
import { BlockPicker } from "react-color";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";
import axios from "axios";

const animatedComponents = makeAnimated();

/** أقسام المنيو التي تُعرض في البطاقة الرئيسية؛ «معلومات الحساسية» في العمود الجانبي مع الملف. */
const MENU_SECTION_KEYS_MAIN = ["todays_menu", "location", "smart_menu", "photos", "feedback", "info"];

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
    const menuTokensUrl = JSON.parse(rootElement.getAttribute("menu-tokens-url") || '""');
    const menuTokenUpdateUrlTemplate = JSON.parse(rootElement.getAttribute("menu-token-update-url-template") || '""');
    const customMenuScheduleUrlTemplate = JSON.parse(rootElement.getAttribute("custom-menu-schedule-url-template") || '""');

    const [products, setProducts] = useState([]);
    const [establishments, setEstablishments] = useState([]);
    const [customMenus, setCustomMenus] = useState([]);
    const [menuRecords, setMenuRecords] = useState([]);
    const [menuSearch, setMenuSearch] = useState("");
    const [menuPage, setMenuPage] = useState(1);
    const pageSize = 8;
    const [isSaving, setIsSaving] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const [scheduleMenuId, setScheduleMenuId] = useState(null);
    const [scheduleLoading, setScheduleLoading] = useState(false);
    const [scheduleSaving, setScheduleSaving] = useState(false);
    const [scheduleForm, setScheduleForm] = useState({
        from_date: "",
        to_date: "",
        no_date_limit: false,
        times: [],
    });

    const [currentObject, setCurrentObject] = useState({
        selectedEstablishments: [],
        selectedProducts: [],
        menuSections: {
            todays_menu: false,
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

    const filteredMenuRecords = useMemo(() => {
        const term = (menuSearch || "").trim().toLowerCase();
        if (!term) return menuRecords;
        return menuRecords.filter((row) => {
            const title = (row.title || "").toLowerCase();
            const sub = (row.sub_title || "").toLowerCase();
            const branches = (row.est_names || []).join(" ").toLowerCase();
            return title.includes(term) || sub.includes(term) || branches.includes(term);
        });
    }, [menuRecords, menuSearch]);

    const totalPages = Math.max(1, Math.ceil(filteredMenuRecords.length / pageSize));
    const pagedMenuRecords = filteredMenuRecords.slice((menuPage - 1) * pageSize, menuPage * pageSize);

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
            const customMenuOptions = (cm.data || []).map((m) => ({
                value: m.id,
                label: dir === "rtl" ? m.name_ar : m.name_en,
            }));
            setCustomMenus(customMenuOptions);

            if (menuTokensUrl) {
                const tokensResponse = await axios.get(menuTokensUrl);
                setMenuRecords(tokensResponse.data || []);
            }
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
            setIsSaving(true);
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

            const updateUrl = editingId
                ? menuTokenUpdateUrlTemplate.replace("__ID__", String(editingId))
                : null;
            const response = editingId
                ? await axios.post(updateUrl, (() => { formData.append("_method", "PUT"); return formData; })(), { headers: { Accept: "application/json" } })
                : await axios.post("/generate-menu-token", formData, { headers: { Accept: "application/json" } });
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
            await getInitialData();
        } catch (error) {
            const msg =
                error?.response?.data?.message ||
                (dir === "rtl" ? "تعذر إنشاء الرابط" : "Could not generate link");
            window.Swal.fire({ icon: "error", title: "Error", text: msg });
            console.error("Error generating QR:", error);
        } finally {
            setIsSaving(false);
        }
    };

    const resetForm = () => {
        setEditingId(null);
        setCurrentObject({
            selectedEstablishments: [],
            selectedProducts: [],
            menuSections: {
                todays_menu: false,
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
    };

    const startEdit = (record) => {
        const selectedEstablishments = (record.est_ids || [])
            .map((id) => establishments.find((e) => Number(e.value) === Number(id)))
            .filter(Boolean);
        const selectedProducts = (record.products || [])
            .map((id) => products.find((p) => Number(p.value) === Number(id)))
            .filter(Boolean);
        const selectedCustomMenu = customMenus.find((c) => Number(c.value) === Number(record.custom_menu_id)) || null;
        setEditingId(record.id);
        setCurrentObject((prev) => ({
            ...prev,
            selectedEstablishments,
            selectedProducts,
            title: record.title || "",
            subTitle: record.sub_title || "",
            customMenu: selectedCustomMenu,
            map_lat: record.map_lat || "",
            map_lng: record.map_lng || "",
            map_label: record.map_label || "",
            allergyFile: null,
            menuSections: {
                ...prev.menuSections,
                ...(record.section_flags || {}),
            },
        }));
    };

    const deleteRecord = async (recordId) => {
        const confirmed = await window.Swal.fire({
            icon: "warning",
            title: dir === "rtl" ? "تأكيد الحذف" : "Confirm delete",
            text: dir === "rtl" ? "سيتم حذف القائمة نهائيًا" : "This menu will be deleted permanently",
            showCancelButton: true,
            confirmButtonText: dir === "rtl" ? "حذف" : "Delete",
            cancelButtonText: dir === "rtl" ? "إلغاء" : "Cancel",
        });
        if (!confirmed.isConfirmed) return;
        try {
            const deleteUrl = menuTokenUpdateUrlTemplate.replace("__ID__", String(recordId));
            await axios.delete(deleteUrl);
            await getInitialData();
            if (editingId === recordId) {
                resetForm();
            }
        } catch (e) {
            window.Swal.fire({ icon: "error", title: "Error", text: dir === "rtl" ? "تعذر الحذف" : "Delete failed" });
        }
    };

    const openingBadge = (row) => {
        if (row.opening_status === "open") {
            return <span className="badge badge-light-success">{dir === "rtl" ? "مفتوح" : "Open"}</span>;
        }
        if (row.opening_status === "closed") {
            return <span className="badge badge-light-danger">{dir === "rtl" ? "مغلق" : "Closed"}</span>;
        }
        return <span className="badge badge-light-secondary">{dir === "rtl" ? "غير محدد" : "N/A"}</span>;
    };

    const openingSourceBadge = (row) => {
        if (row.opening_source === "custom_menu") {
            return <span className="badge badge-light-primary">{dir === "rtl" ? "من القائمة المخصصة" : "From custom menu"}</span>;
        }
        if (row.opening_source === "custom_menu_no_schedule") {
            return <span className="badge badge-light-warning">{dir === "rtl" ? "قائمة مخصصة بدون جدول أوقات" : "Custom menu without schedule"}</span>;
        }
        return <span className="badge badge-light-secondary">{dir === "rtl" ? "بدون جدول أوقات" : "No schedule source"}</span>;
    };

    const dayLabels = {
        1: dir === "rtl" ? "الاثنين" : "Monday",
        2: dir === "rtl" ? "الثلاثاء" : "Tuesday",
        3: dir === "rtl" ? "الأربعاء" : "Wednesday",
        4: dir === "rtl" ? "الخميس" : "Thursday",
        5: dir === "rtl" ? "الجمعة" : "Friday",
        6: dir === "rtl" ? "السبت" : "Saturday",
        7: dir === "rtl" ? "الأحد" : "Sunday",
    };

    const toTimeInputValue = (value) => (value || "00:00:00").slice(0, 5);
    const toApiTimeValue = (value) => `${value || "00:00"}:00`;

    const openScheduleModal = async (customMenuId) => {
        if (!customMenuId || !customMenuScheduleUrlTemplate) return;
        setScheduleMenuId(customMenuId);
        setShowScheduleModal(true);
        setScheduleLoading(true);
        try {
            const url = customMenuScheduleUrlTemplate.replace("__ID__", String(customMenuId));
            const response = await axios.get(url);
            const payload = response.data || {};
            setScheduleForm({
                from_date: payload.from_date || "",
                to_date: payload.to_date || "",
                no_date_limit: !!payload.no_date_limit,
                times: payload.times || [],
            });
        } catch (e) {
            window.Swal.fire({ icon: "error", title: "Error", text: dir === "rtl" ? "تعذر تحميل جدول الأوقات" : "Could not load schedule" });
            setShowScheduleModal(false);
        } finally {
            setScheduleLoading(false);
        }
    };

    const updateScheduleRow = (dayNo, key, value) => {
        setScheduleForm((prev) => ({
            ...prev,
            times: (prev.times || []).map((row) =>
                Number(row.day_no) === Number(dayNo) ? { ...row, [key]: value } : row
            ),
        }));
    };

    const copyDayTimeToAllDays = (dayNo) => {
        setScheduleForm((prev) => {
            const source = (prev.times || []).find((row) => Number(row.day_no) === Number(dayNo));
            if (!source) return prev;
            return {
                ...prev,
                times: (prev.times || []).map((row) => ({
                    ...row,
                    from_time: source.from_time,
                    to_time: source.to_time,
                })),
            };
        });
    };

    const copyDayTimeToActiveDays = (dayNo) => {
        setScheduleForm((prev) => {
            const source = (prev.times || []).find((row) => Number(row.day_no) === Number(dayNo));
            if (!source) return prev;
            return {
                ...prev,
                times: (prev.times || []).map((row) =>
                    row.active
                        ? {
                            ...row,
                            from_time: source.from_time,
                            to_time: source.to_time,
                        }
                        : row
                ),
            };
        });
    };

    const setAllDaysActive = (isActive) => {
        setScheduleForm((prev) => ({
            ...prev,
            times: (prev.times || []).map((row) => ({
                ...row,
                active: !!isActive,
            })),
        }));
    };

    const saveSchedule = async () => {
        if (!scheduleMenuId || !customMenuScheduleUrlTemplate) return;
        setScheduleSaving(true);
        try {
            const url = customMenuScheduleUrlTemplate.replace("__ID__", String(scheduleMenuId));
            const payload = {
                no_date_limit: !!scheduleForm.no_date_limit,
                from_date: scheduleForm.no_date_limit ? null : scheduleForm.from_date,
                to_date: scheduleForm.no_date_limit ? null : scheduleForm.to_date,
                times: (scheduleForm.times || []).map((row) => ({
                    day_no: Number(row.day_no),
                    from_time: toApiTimeValue(toTimeInputValue(row.from_time)),
                    to_time: toApiTimeValue(toTimeInputValue(row.to_time)),
                    active: !!row.active,
                })),
            };
            await axios.post(url, { ...payload, _method: "PUT" });
            setShowScheduleModal(false);
            await getInitialData();
            window.Swal.fire({ icon: "success", title: dir === "rtl" ? "تم الحفظ" : "Saved", timer: 1200, showConfirmButton: false });
        } catch (e) {
            const msg = e?.response?.data?.message || (dir === "rtl" ? "تعذر حفظ جدول الأوقات" : "Could not save schedule");
            window.Swal.fire({ icon: "error", title: "Error", text: msg });
        } finally {
            setScheduleSaving(false);
        }
    };

    const ellipsisCell = (text, maxWidth = 220) => (
        <span
            title={text || "-"}
            style={{
                display: "inline-block",
                maxWidth,
                whiteSpace: "nowrap",
                overflow: "hidden",
                textOverflow: "ellipsis",
                verticalAlign: "bottom",
            }}
        >
            {text || "-"}
        </span>
    );

    return (
        <div className="container-fluid py-3" dir={dir}>
            <div className={`row g-4 align-items-start ${dir === "rtl" ? "flex-xl-row-reverse" : ""}`}>
                <div className="col-12 col-xl-8">
                    <div className="card border-0 shadow-sm h-100">
                        <div className="card-body">
                            <h5 className="mb-4 fw-bold text-gray-800 border-bottom pb-3">
                                {dir === "rtl" ? "إعدادات القائمة" : "Menu settings"}
                            </h5>

                            <div className="row g-3 mb-3">
                                <div className="col-md-4">
                                    <label className="form-label fw-bold">{translations.establishment || "الأفرع"}</label>
                                    <Select
                                        options={establishmentOptions}
                                        isMulti
                                        value={currentObject.selectedEstablishments}
                                        onChange={(val) =>
                                            handleSelectChange("selectedEstablishments", val, establishmentOptions, establishments)
                                        }
                                        components={animatedComponents}
                                        className="basic-multi-select"
                                        classNamePrefix="select"
                                        placeholder={dir === "rtl" ? "اختر الأفرع..." : "Select establishments..."}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label fw-bold">{translations.products || "المنتجات"}</label>
                                    <Select
                                        options={productOptions}
                                        isMulti
                                        value={currentObject.selectedProducts}
                                        onChange={(val) => handleSelectChange("selectedProducts", val, productOptions, products)}
                                        components={animatedComponents}
                                        className="basic-multi-select"
                                        classNamePrefix="select"
                                        placeholder={dir === "rtl" ? "اختر المنتجات..." : "Select products..."}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label">{translations.title || "العنوان الرئيسي"}</label>
                                    <input
                                        type="text"
                                        className="form-control form-control-solid"
                                        value={currentObject.title}
                                        onChange={(e) => onChange("title", e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="row g-3 mb-3">
                                <div className="col-md-4">
                                    <label className="form-label">{translations.subTitle || "العنوان الفرعي"}</label>
                                    <input
                                        type="text"
                                        className="form-control form-control-solid"
                                        value={currentObject.subTitle}
                                        onChange={(e) => onChange("subTitle", e.target.value)}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label d-block mb-2 fw-bold">{translations.color || "لون الـ QR"}</label>
                                    <div className="menuqr-color-picker border rounded p-2 bg-light d-flex justify-content-center">
                                        <BlockPicker color={currentObject.color} onChange={(color) => onChange("color", color.hex)} />
                                    </div>
                                </div>
                                <div className="col-md-4 d-flex flex-column justify-content-end">
                                    {currentObject.menuSections.todays_menu ? (
                                        <>
                                            <label className="form-label fw-bold text-danger">
                                                * {dir === "rtl" ? "قائمة مخصصة" : "Custom menu"}
                                            </label>
                                            <Select
                                                options={customMenus}
                                                value={currentObject.customMenu}
                                                onChange={(v) => onChange("customMenu", v)}
                                                placeholder={dir === "rtl" ? "اختر القائمة..." : "Select custom menu..."}
                                                classNamePrefix="select"
                                            />
                                        </>
                                    ) : (
                                        <div className="text-muted small pt-2">
                                            {dir === "rtl"
                                                ? "فعّل «قائمة اليوم» من الأقسام أدناه لاختيار قائمة مخصصة."
                                                : "Enable «Today's menu» in sections below to pick a custom menu."}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="border rounded p-3 mb-3 bg-light">
                                <h6 className="mb-3 fw-bold text-primary">
                                    {dir === "rtl" ? "أقسام المنيو الظاهرة" : "Visible menu sections"}
                                </h6>
                                <div className="row g-2">
                                    {MENU_SECTION_KEYS_MAIN.map((key) => (
                                        <div key={key} className="col-md-4">
                                            <div className="d-flex align-items-center justify-content-between p-2 border rounded bg-white h-100">
                                                <label className="mb-0 fw-semibold small">{getTranslatedLabel(key)}</label>
                                                <InputSwitch
                                                    checked={currentObject.menuSections[key]}
                                                    onChange={(e) =>
                                                        onChange("menuSections", { ...currentObject.menuSections, [key]: e.value })
                                                    }
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {currentObject.menuSections.todays_menu && (
                                <div className="border rounded p-3 mb-3 bg-white">
                                    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <small className="text-muted mb-0">
                                            {dir === "rtl"
                                                ? "أوقات الفتح/الإغلاق تُدار من القائمة المخصصة."
                                                : "Opening hours follow the custom menu schedule."}
                                        </small>
                                        {currentObject.customMenu && (
                                            <button
                                                type="button"
                                                onClick={() => openScheduleModal(currentObject.customMenu.value)}
                                                className="btn btn-sm btn-light-primary"
                                            >
                                                {dir === "rtl" ? "ضبط الأوقات" : "Set hours"}
                                            </button>
                                        )}
                                    </div>
                                    {!currentObject.customMenu && (
                                        <small className="text-warning d-block mt-2">
                                            {dir === "rtl"
                                                ? "اختر قائمة مخصصة أو عطّل قسم قائمة اليوم."
                                                : "Select a custom menu or disable Today's menu."}
                                        </small>
                                    )}
                                </div>
                            )}

                            {currentObject.menuSections.location && (
                                <div className="border rounded p-3 mb-3 bg-white">
                                    <div className="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                        <h6 className="fw-bold mb-0">{dir === "rtl" ? "موقع الخريطة" : "Map location"}</h6>
                                        <button type="button" className="btn btn-sm btn-light-primary" onClick={fillLocationFromBrowser}>
                                            {dir === "rtl" ? "موقعي الحالي" : "My location"}
                                        </button>
                                    </div>
                                    <div className="row g-2">
                                        <div className="col-md-4">
                                            <label className="form-label fs-7">Latitude</label>
                                            <input
                                                className="form-control form-control-sm form-control-solid"
                                                value={currentObject.map_lat}
                                                onChange={(e) => onChange("map_lat", e.target.value)}
                                            />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label fs-7">Longitude</label>
                                            <input
                                                className="form-control form-control-sm form-control-solid"
                                                value={currentObject.map_lng}
                                                onChange={(e) => onChange("map_lng", e.target.value)}
                                            />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label fs-7">{dir === "rtl" ? "وصف الموقع" : "Place label"}</label>
                                            <input
                                                className="form-control form-control-sm form-control-solid"
                                                value={currentObject.map_label}
                                                onChange={(e) => onChange("map_label", e.target.value)}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="d-grid gap-2 d-md-flex">
                                <button onClick={generateQR} className="btn btn-primary btn-lg flex-grow-1 shadow-sm" disabled={isSaving}>
                                    {isSaving
                                        ? (dir === "rtl" ? "جاري الحفظ..." : "Saving...")
                                        : (editingId
                                            ? (dir === "rtl" ? "حفظ التعديلات" : "Save changes")
                                            : (translations.generateQr || "إنشاء كود QR"))}
                                </button>
                                <button type="button" onClick={resetForm} className="btn btn-light btn-lg">
                                    {dir === "rtl" ? "قائمة جديدة" : "New menu"}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-xl-4">
                    <div className="card border-0 shadow-sm sticky-top" style={{ top: "1rem" }}>
                        <div className="card-body text-center">
                            <div className="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <span className="fw-semibold small">{translations.showLogo || (dir === "rtl" ? "الشعار على الـ QR" : "Logo on QR")}</span>
                                <InputSwitch checked={currentObject.showLogo} onChange={(e) => onChange("showLogo", e.value)} />
                            </div>
                            <div className="p-3 rounded-3 bg-light border menuqr-qr-wrap d-inline-block">
                                <QRCodeCanvas
                                    id={qrInfo.id}
                                    value={qrInfo.url || " "}
                                    size={240}
                                    fgColor={qrInfo.color || "#000000"}
                                    imageSettings={qrInfo.logo}
                                    level="H"
                                />
                            </div>
                            {qrInfo.url && (
                                <div className="mt-3 d-flex flex-wrap justify-content-center gap-2">
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-secondary"
                                        onClick={() => {
                                            const canvas = document.getElementById(qrInfo.id);
                                            const link = document.createElement("a");
                                            link.download = "qr-menu.png";
                                            link.href = canvas.toDataURL();
                                            link.click();
                                        }}
                                    >
                                        {dir === "rtl" ? "تحميل PNG" : "Download PNG"}
                                    </button>
                                    <a href={qrInfo.url} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary">
                                        {dir === "rtl" ? "معاينة" : "Preview"}
                                    </a>
                                </div>
                            )}
                            {!qrInfo.url && (
                                <p className="text-muted small mt-3 mb-0">
                                    {dir === "rtl" ? "أنشئ الرابط لعرض معاينة الـ QR." : "Generate the link to preview the QR code."}
                                </p>
                            )}

                            <div className="mt-4 pt-3 border-top text-start">
                                <div className="d-flex align-items-center justify-content-between mb-2">
                                    <label className="fw-semibold small mb-0">{getTranslatedLabel("allergy_info")}</label>
                                    <InputSwitch
                                        checked={currentObject.menuSections.allergy_info}
                                        onChange={(e) =>
                                            onChange("menuSections", { ...currentObject.menuSections, allergy_info: e.value })
                                        }
                                    />
                                </div>
                                <label className="form-label small text-muted mb-1">
                                    {dir === "rtl" ? "ملف مسببات الحساسية (PDF / صورة)" : "Allergy document (PDF / image)"}
                                </label>
                                <input
                                    type="file"
                                    className="form-control form-control-sm"
                                    accept=".pdf,image/png,image/jpeg"
                                    disabled={!currentObject.menuSections.allergy_info}
                                    onChange={(e) => onChange("allergyFile", e.target.files?.[0] || null)}
                                />
                                {currentObject.menuSections.allergy_info && (
                                    <small className="text-muted d-block mt-2">
                                        {dir === "rtl"
                                            ? "عند التفعيل، يجب رفع ملف قبل إنشاء الرابط."
                                            : "When enabled, upload a file before generating."}
                                    </small>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="row mt-4">
                <div className="col-12">
                    <div className="card border-0 shadow-sm">
                        <div className="card-header">
                            <h3 className="card-title mb-0">{dir === "rtl" ? "القوائم المحفوظة" : "Saved menus"}</h3>
                        </div>
                        <div className="card-body">
                            <div className="mb-3">
                                <input
                                    className="form-control form-control-solid"
                                    placeholder={dir === "rtl" ? "بحث بالعنوان أو الفرع..." : "Search by title or branch..."}
                                    value={menuSearch}
                                    onChange={(e) => {
                                        setMenuSearch(e.target.value);
                                        setMenuPage(1);
                                    }}
                                />
                            </div>
                            <div className="table-responsive">
                                <table className="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{dir === "rtl" ? "العنوان" : "Title"}</th>
                                            <th>{dir === "rtl" ? "الأفرع" : "Branches"}</th>
                                            <th>{dir === "rtl" ? "الحالة الآن" : "Current status"}</th>
                                            <th>{dir === "rtl" ? "مصدر الحالة" : "Status source"}</th>
                                            <th>{dir === "rtl" ? "أوقات الفتح اليوم" : "Today's hours"}</th>
                                            <th>{dir === "rtl" ? "التاريخ" : "Created"}</th>
                                            <th>{dir === "rtl" ? "الإجراءات" : "Actions"}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pagedMenuRecords.map((row) => (
                                            <tr key={row.id}>
                                                <td>{row.id}</td>
                                                <td>{ellipsisCell(row.title || "-", 180)}</td>
                                                <td>{ellipsisCell((row.est_names || []).join("، "), 220)}</td>
                                                <td>{openingBadge(row)}</td>
                                                <td>
                                                    <div className="d-flex flex-column gap-1">
                                                        {openingSourceBadge(row)}
                                                        {row.custom_menu_id && (
                                                            <button
                                                                type="button"
                                                                onClick={() => openScheduleModal(row.custom_menu_id)}
                                                                className="btn btn-sm btn-light-primary py-1 px-2"
                                                            >
                                                                {dir === "rtl" ? "تعديل أوقات القائمة" : "Edit menu hours"}
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                                <td>{ellipsisCell(row.opening_hours_text || "-", 190)}</td>
                                                <td>{row.created_at || "-"}</td>
                                                <td className="d-flex flex-wrap gap-2">
                                                    <button type="button" className="btn btn-sm btn-light-primary" onClick={() => startEdit(row)}>
                                                        {dir === "rtl" ? "تعديل" : "Edit"}
                                                    </button>
                                                    <a
                                                        href={`${window.location.origin}/menuSimple/${row.token}`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="btn btn-sm btn-light-info"
                                                    >
                                                        {dir === "rtl" ? "عرض" : "View"}
                                                    </a>
                                                    <button type="button" className="btn btn-sm btn-light-danger" onClick={() => deleteRecord(row.id)}>
                                                        {dir === "rtl" ? "حذف" : "Delete"}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                        {pagedMenuRecords.length === 0 && (
                                            <tr>
                                                <td colSpan={8} className="text-center text-muted">-</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            <div className="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                <div className="text-muted fs-7">
                                    {dir === "rtl"
                                        ? `عدد النتائج: ${filteredMenuRecords.length}`
                                        : `Results: ${filteredMenuRecords.length}`}
                                </div>
                                <div className="d-flex gap-2">
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-light"
                                        disabled={menuPage <= 1}
                                        onClick={() => setMenuPage((p) => Math.max(1, p - 1))}
                                    >
                                        {dir === "rtl" ? "السابق" : "Prev"}
                                    </button>
                                    <span className="px-2 py-1 text-muted fs-7">
                                        {menuPage} / {totalPages}
                                    </span>
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-light"
                                        disabled={menuPage >= totalPages}
                                        onClick={() => setMenuPage((p) => Math.min(totalPages, p + 1))}
                                    >
                                        {dir === "rtl" ? "التالي" : "Next"}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {showScheduleModal && (
                <div className="modal d-block" tabIndex="-1" style={{ background: "rgba(0,0,0,0.35)" }}>
                    <div className="modal-dialog modal-lg modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">{dir === "rtl" ? "تعديل أوقات الفتح" : "Edit Opening Hours"}</h5>
                                <button type="button" className="btn-close" onClick={() => setShowScheduleModal(false)}></button>
                            </div>
                            <div className="modal-body">
                                {scheduleLoading ? (
                                    <div className="text-center text-muted py-4">{dir === "rtl" ? "جاري التحميل..." : "Loading..."}</div>
                                ) : (
                                    <>
                                        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-3 rounded border bg-light">
                                            <div>
                                                <div className="fw-semibold">
                                                    {dir === "rtl" ? "غير مقيد بفترة التواريخ" : "No date range limit"}
                                                </div>
                                                <small className="text-muted">
                                                    {dir === "rtl"
                                                        ? "عند التفعيل تبقى القائمة ضمن نطاق تقويمي واسع؛ يبقى الاعتماد على أوقات الأيام فقط."
                                                        : "When on, the menu stays valid across a wide calendar window; day times still apply."}
                                                </small>
                                            </div>
                                            <InputSwitch
                                                checked={!!scheduleForm.no_date_limit}
                                                onChange={(e) => setScheduleForm((p) => ({ ...p, no_date_limit: e.value }))}
                                            />
                                        </div>
                                        <div className="row g-3 mb-3">
                                            <div className="col-md-6">
                                                <label className="form-label">{dir === "rtl" ? "من تاريخ" : "From date"}</label>
                                                <input
                                                    type="date"
                                                    className="form-control"
                                                    disabled={!!scheduleForm.no_date_limit}
                                                    value={scheduleForm.from_date || ""}
                                                    onChange={(e) => setScheduleForm((p) => ({ ...p, from_date: e.target.value }))}
                                                />
                                            </div>
                                            <div className="col-md-6">
                                                <label className="form-label">{dir === "rtl" ? "إلى تاريخ" : "To date"}</label>
                                                <input
                                                    type="date"
                                                    className="form-control"
                                                    disabled={!!scheduleForm.no_date_limit}
                                                    value={scheduleForm.to_date || ""}
                                                    onChange={(e) => setScheduleForm((p) => ({ ...p, to_date: e.target.value }))}
                                                />
                                            </div>
                                        </div>
                                        <div className="table-responsive">
                                            <div className="d-flex flex-wrap gap-2 mb-3">
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-light-success"
                                                    onClick={() => setAllDaysActive(true)}
                                                >
                                                    {dir === "rtl" ? "تفعيل كل الأيام" : "Enable all days"}
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-light-danger"
                                                    onClick={() => setAllDaysActive(false)}
                                                >
                                                    {dir === "rtl" ? "تعطيل كل الأيام" : "Disable all days"}
                                                </button>
                                            </div>
                                            <table className="table table-striped align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>{dir === "rtl" ? "اليوم" : "Day"}</th>
                                                        <th>{dir === "rtl" ? "نشط" : "Active"}</th>
                                                        <th>{dir === "rtl" ? "من" : "From"}</th>
                                                        <th>{dir === "rtl" ? "إلى" : "To"}</th>
                                                        <th>{dir === "rtl" ? "إجراء سريع" : "Quick action"}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {(scheduleForm.times || []).map((row) => (
                                                        <tr key={row.day_no}>
                                                            <td>{dayLabels[row.day_no] || row.day_no}</td>
                                                            <td>
                                                                <input
                                                                    type="checkbox"
                                                                    checked={!!row.active}
                                                                    onChange={(e) => updateScheduleRow(row.day_no, "active", e.target.checked)}
                                                                />
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="time"
                                                                    className="form-control"
                                                                    value={toTimeInputValue(row.from_time)}
                                                                    onChange={(e) => updateScheduleRow(row.day_no, "from_time", toApiTimeValue(e.target.value))}
                                                                />
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="time"
                                                                    className="form-control"
                                                                    value={toTimeInputValue(row.to_time)}
                                                                    onChange={(e) => updateScheduleRow(row.day_no, "to_time", toApiTimeValue(e.target.value))}
                                                                />
                                                            </td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-sm btn-light-info"
                                                                    onClick={() => copyDayTimeToAllDays(row.day_no)}
                                                                >
                                                                    {dir === "rtl" ? "نسخ لكل الأيام" : "Copy to all"}
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-sm btn-light-primary ms-2"
                                                                    onClick={() => copyDayTimeToActiveDays(row.day_no)}
                                                                >
                                                                    {dir === "rtl" ? "للأيام النشطة فقط" : "Copy to active only"}
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </>
                                )}
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-light" onClick={() => setShowScheduleModal(false)}>
                                    {dir === "rtl" ? "إلغاء" : "Cancel"}
                                </button>
                                <button type="button" className="btn btn-primary" onClick={saveSchedule} disabled={scheduleSaving || scheduleLoading}>
                                    {scheduleSaving ? (dir === "rtl" ? "جاري الحفظ..." : "Saving...") : (dir === "rtl" ? "حفظ" : "Save")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default MenuQR;

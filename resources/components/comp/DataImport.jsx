import React, { useCallback, useMemo, useState } from "react";
import { useDropzone } from "react-dropzone";
import { getRowName } from "../lang/Utils";
import { emsCan } from "../emsCan";

const ACCEPTED = {
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet": [".xlsx"],
  "application/vnd.ms-excel": [".xls"],
  "text/csv": [".csv"],
};

const PREVIEW_COLUMNS = [
  { key: "name_ar", labelKey: "name_ar", editable: true },
  { key: "name_en", labelKey: "name_en", editable: true },
  { key: "category", labelKey: "category", editable: true },
  { key: "subcategory", labelKey: "subcategory", editable: true },
  { key: "main_unit", labelKey: "Unit", editable: true },
  { key: "price_with_tax", labelKey: "price_with_tax", editable: true },
  { key: "tax", labelKey: "tax", editable: true },
  { key: "establishment", labelKey: "establishment", editable: true },
  { key: "SKU", labelKey: "SKU", editable: true },
];

const statusClass = (status) => {
  if (status === "exists") return "pi-row-exists";
  if (status === "error") return "pi-row-error";
  if (status === "imported") return "pi-row-imported";
  return "pi-row-ok";
};

const issueText = (row, translations) => {
  const issues = Array.isArray(row.issues) ? row.issues : [];
  if (!issues.length) {
    if (row.status === "exists") {
      return translations.importStatusExistsHint || "موجود مسبقاً — لن يُضاف";
    }
    if (row.status === "ok") {
      return translations.importStatusOkHint || "جاهز للإضافة";
    }
    return "";
  }
  return issues
    .map((i) => {
      const code = translations[i.code] || i.code;
      const field = translations[i.field] || translations[i.field?.toLowerCase?.()] || i.field;
      const val = i.value ? ` (${i.value})` : "";
      return `${code}: ${field}${val}`;
    })
    .join(" · ");
};

const ImportPreviewTable = ({
  rows,
  translations,
  onCellChange,
  onCellBlur,
  revalidating,
}) => (
  <div className="table-responsive pi-preview-table-wrap">
    <table className="table align-middle gs-0 gy-2 mb-0 pi-preview-table">
      <thead>
        <tr className="fw-bold text-muted">
          <th className="ps-4 min-w-40px">#</th>
          <th className="min-w-110px">{translations.status || "الحالة"}</th>
          {PREVIEW_COLUMNS.map((col) => (
            <th key={col.key} className="min-w-120px">
              {translations[col.labelKey] || col.key}
            </th>
          ))}
          <th className="min-w-180px pe-4">{translations.notes || "الملاحظات"}</th>
        </tr>
      </thead>
      <tbody>
        {rows.map((row, index) => {
          const problems = new Set(row.problem_fields || []);
          const canEdit = row.status === "error";
          return (
            <tr key={row._index ?? index} className={statusClass(row.status)}>
              <td className="ps-4 text-muted">{index + 1}</td>
              <td>
                <span className={`pi-status-badge status-${row.status}`}>
                  {row.status === "exists"
                    ? translations.importStatusExists || "موجود"
                    : row.status === "error"
                    ? translations.importStatusError || "مشكلة"
                    : row.status === "imported"
                    ? translations.importStatusImported || "تم"
                    : translations.importStatusOk || "سليم"}
                </span>
              </td>
              {PREVIEW_COLUMNS.map((col) => {
                const isProblem = problems.has(col.key);
                const value = row[col.key] ?? "";
                if (canEdit && col.editable) {
                  return (
                    <td key={col.key} className={isProblem ? "pi-cell-problem" : ""}>
                      <input
                        type="text"
                        className={`form-control form-control-sm pi-cell-input ${
                          isProblem ? "is-invalid" : ""
                        }`}
                        value={value}
                        disabled={revalidating}
                        onChange={(e) => onCellChange(index, col.key, e.target.value)}
                        onBlur={() => onCellBlur(index)}
                      />
                    </td>
                  );
                }
                return (
                  <td key={col.key} className={isProblem ? "pi-cell-problem" : ""}>
                    {value || "—"}
                  </td>
                );
              })}
              <td className="pe-4 fs-8 text-muted">{issueText(row, translations)}</td>
            </tr>
          );
        })}
      </tbody>
    </table>
  </div>
);

const DataImport = ({ translations, dir }) => {
  const rootElement = document.getElementById("root");
  const templateUrl = rootElement.getAttribute("template-url");
  const type = rootElement.getAttribute("type");
  const dataType = rootElement.getAttribute("data-type");

  const [data, setData] = useState([]);
  const [summary, setSummary] = useState(null);
  const [file, setFile] = useState(null);
  const [previewing, setPreviewing] = useState(false);
  const [importing, setImporting] = useState(false);
  const [revalidating, setRevalidating] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const [previewError, setPreviewError] = useState(null);

  const isProductImport = type === "importProduct";
  const t = (key, fallback) => translations?.[key] || fallback;

  const rules = useMemo(() => {
    if (!isProductImport) {
      return [
        {
          icon: "ki-file-up",
          title: t("importRuleFormatTitle", "صيغة الملف"),
          body: t("importRuleFormatBody", "XLSX أو XLS أو CSV وفق النموذج المعتمد."),
        },
        {
          icon: "ki-verify",
          title: t("importRuleHeadersTitle", "ترويسة الأعمدة"),
          body: t("importRuleHeadersBody", "لا تغيّر أسماء الأعمدة في الصف الأول."),
        },
      ];
    }

    return [
      {
        icon: "ki-file",
        title: t("importRuleTemplateTitle", "ابدأ من النموذج"),
        body: t(
          "importRuleTemplateBody",
          "حمّل النموذج الرسمي واملأ الصفوف دون تعديل أسماء الأعمدة."
        ),
      },
      {
        icon: "ki-element-11",
        title: t("importRuleCategoryTitle", "التصنيفات"),
        body: t(
          "importRuleCategoryBody",
          "التصنيف والتصنيف الجزئي يُنشآن تلقائياً إن لم يكونا موجودين."
        ),
      },
      {
        icon: "ki-information-2",
        title: t("importRuleColorsTitle", "ألوان المعاينة"),
        body: t(
          "importRuleColorsBody",
          "أخضر = سليم · برتقالي = موجود مسبقاً · أحمر = يحتاج تعديلاً داخل الجدول."
        ),
      },
      {
        icon: "ki-parcel",
        title: t("importRuleUnitTitle", "الوحدة والأسماء"),
        body: t(
          "importRuleUnitBody",
          "الاسم العربي والوحدة الرئيسية إلزاميان، ولا يُسمح بتكرار الأسماء."
        ),
      },
      {
        icon: "ki-cloud-add",
        title: t("importRuleSizeTitle", "حجم الملف"),
        body: t(
          "importRuleSizeBody",
          "الحد الأقصى 10 ميغابايت — صيغ XLSX / XLS / CSV فقط."
        ),
      },
    ];
  }, [isProductImport, translations]);

  const applyValidated = (payload) => {
    const rows = Array.isArray(payload?.rows)
      ? payload.rows
      : Array.isArray(payload)
      ? payload
      : [];
    setData(rows);
    setSummary(payload?.summary || null);
    if (!rows.length) {
      setPreviewError(t("importPreviewEmpty", "لا توجد صفوف بيانات في الملف."));
    } else {
      setPreviewError(null);
    }
  };

  const processFile = useCallback(
    (selectedFile) => {
      if (!selectedFile) return;

      setFile(selectedFile);
      setPreviewing(true);
      setData([]);
      setSummary(null);
      setPreviewError(null);

      const formData = new FormData();
      formData.append("file", selectedFile);

      axios
        .post(`/${type}/readData`, formData, {
          headers: { "Content-Type": "multipart/form-data" },
        })
        .then((response) => {
          applyValidated(response.data);
        })
        .catch((error) => {
          const serverMsg =
            error?.response?.data?.detail ||
            error?.response?.data?.message ||
            error?.response?.data?.errors?.file?.[0];
          setPreviewError(
            serverMsg ||
              t("importPreviewFailed", "تعذر قراءة الملف. تحقق من الصيغة.")
          );
          setData([]);
        })
        .finally(() => setPreviewing(false));
    },
    [type, translations]
  );

  const onDrop = useCallback(
    (acceptedFiles) => {
      setDragActive(false);
      if (acceptedFiles?.[0]) processFile(acceptedFiles[0]);
    },
    [processFile]
  );

  const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    onDrop,
    accept: ACCEPTED,
    multiple: false,
    noClick: true,
    noKeyboard: true,
    disabled: !emsCan("create") || previewing || importing,
    onDragEnter: () => setDragActive(true),
    onDragLeave: () => setDragActive(false),
  });

  const handleCellChange = (index, key, value) => {
    setData((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [key]: value };
      return next;
    });
  };

  const revalidateRows = async (rowsOverride) => {
    const rows = rowsOverride || data;
    if (!rows.length) return;
    setRevalidating(true);
    try {
      const response = await axios.post(`/${type}/validateRows`, { rows });
      applyValidated(response.data);
    } catch (e) {
      // keep local edits
    } finally {
      setRevalidating(false);
    }
  };

  const handleCellBlur = () => {
    revalidateRows();
  };

  const getErrorMessage = (errors) => {
    if (!Array.isArray(errors) || !errors.length) {
      return `<div>${t("errorInImport", "خطأ في الاستيراد")}</div>`;
    }
    return errors
      .map((element) => {
        const productLabel = getRowName(element.row) || "—";
        const code = element?.message?.message || "INVALID_import";
        const parts = String(code)
          .split("_")
          .map((m) => translations[m] || m)
          .join(" ");
        const details = Array.isArray(element?.message?.data)
          ? element.message.data
              .map((m) => (translations[m] ? translations[m] : m))
              .join("، ")
          : "";
        return `<div class="mb-2 text-start" dir="${dir}">
          <strong>${t("product", "منتج")}:</strong> ${productLabel}<br/>
          <span>${parts}${details ? `: ${details}` : ""}</span>
        </div>`;
      })
      .join("");
  };

  const handleFileUpload = async () => {
    if (!data.length && !file) {
      Swal.fire({
        title: t("selectFileFirst", "اختر ملفاً أولاً"),
        icon: "warning",
        confirmButtonText: t("close", "إغلاق"),
      });
      return;
    }

    const importable = data.filter((r) => r.status === "ok");
    const errorCount = data.filter((r) => r.status === "error").length;
    const existsCount = data.filter((r) => r.status === "exists").length;

    if (!importable.length) {
      Swal.fire({
        title: t("errorInImport", "خطأ في الاستيراد"),
        html:
          errorCount > 0
            ? t(
                "importFixErrorsFirst",
                "صلّح الصفوف الحمراء داخل الجدول ثم أعد الاستيراد."
              )
            : t(
                "importNothingToImport",
                "لا يوجد منتجات جديدة للاستيراد (الكل موجود مسبقاً)."
              ),
        icon: "warning",
        confirmButtonText: t("close", "إغلاق"),
      });
      return;
    }

    setImporting(true);
    try {
      // Import all current rows: service skips exists and reports errors
      const response = await axios.post(`/${type}/upload`, { rows: data });
      const payload = response.data || {};

      if (payload.rows) {
        applyValidated(payload);
      }

      if (payload.message === "Done") {
        Swal.fire({
          title: t("importSuccessTitle", "تم الاستيراد بنجاح"),
          html: `<div class="fs-5">${t(dataType, t("products", "المنتجات"))}</div>
            <div class="text-muted mt-2">${t("importedsuccessfully", "تم الاستيراد")} — ${
            payload.imported || importable.length
          }</div>
            ${
              payload.skipped
                ? `<div class="text-warning mt-1">${t(
                    "importSkippedExists",
                    "تم تخطي الموجود"
                  )}: ${payload.skipped}</div>`
                : ""
            }`,
          icon: "success",
          confirmButtonText: t("close", "إغلاق"),
        });
        setFile(null);
        setData([]);
        setSummary(null);
      } else if (payload.message === "Partial") {
        Swal.fire({
          title: t("importPartialTitle", "استيراد جزئي"),
          html: `<div>${t("importPartialBody", "تم استيراد جزء من المنتجات.")}</div>
            <div class="mt-2">${t("importStatusOk", "سليم")}: ${payload.imported || 0}</div>
            <div>${t("importStatusExists", "موجود")}: ${payload.skipped || 0}</div>
            <div>${t("importStatusError", "مشكلة")}: ${payload.failed || 0}</div>
            ${getErrorMessage(payload.errors)}`,
          icon: "warning",
          width: 560,
          confirmButtonText: t("close", "إغلاق"),
        });
      } else {
        Swal.fire({
          title: t("errorInImport", "خطأ في الاستيراد"),
          html: `<div class="mb-3">${t(dataType, t("products", "المنتجات"))} — ${t(
            "errorInImport",
            "خطأ في الاستيراد"
          )}</div>${getErrorMessage(payload.errors)}
          ${
            existsCount
              ? `<div class="text-warning mt-2">${t(
                  "importSkippedExists",
                  "تم تخطي الموجود"
                )}: ${existsCount}</div>`
              : ""
          }`,
          icon: "error",
          width: 560,
          confirmButtonText: t("close", "إغلاق"),
        });
      }
    } catch (error) {
      const msg =
        error?.response?.data?.detail ||
        error?.response?.data?.message ||
        t("importUploadFailed", "فشل رفع الملف");
      Swal.fire({
        title: t("errorInImport", "خطأ في الاستيراد"),
        html: String(msg),
        icon: "error",
        confirmButtonText: t("close", "إغلاق"),
      });
    } finally {
      setImporting(false);
    }
  };

  const clearFile = () => {
    setFile(null);
    setData([]);
    setSummary(null);
    setPreviewError(null);
  };

  const activeDrop = isDragActive || dragActive;
  const okCount = summary?.ok ?? data.filter((r) => r.status === "ok").length;
  const existsCount =
    summary?.exists ?? data.filter((r) => r.status === "exists").length;
  const errorCount =
    summary?.error ?? data.filter((r) => r.status === "error").length;

  return (
    <div className="product-import-page" dir={dir}>
      <div className="pi-hero card border-0 mb-5">
        <div className="card-body py-6 px-6 px-lg-8">
          <div className="d-flex flex-wrap align-items-start justify-content-between gap-4">
            <div className="pi-hero-copy">
              <div className="pi-kicker mb-2">
                {t("importStudioKicker", "استوديو الاستيراد")}
              </div>
              <h2 className="pi-title mb-2">
                {t(type, t("importProduct", "استيراد المنتجات"))}
              </h2>
              <p className="pi-subtitle mb-0 text-muted">
                {t(
                  "importStudioHint",
                  "ارفع الملف، راجع الألوان، عدّل الصفوف الحمراء داخل الجدول، ثم استورد."
                )}
              </p>
            </div>
            <a
              href={templateUrl}
              className="btn btn-light-primary btn-sm pi-template-btn"
              download
            >
              <i className="ki-outline ki-file-down fs-3 me-1" />
              {t("downloadTemplate", "تحميل النموذج")}
            </a>
          </div>
        </div>
      </div>

      <div className="row g-5 mb-5">
        <div className="col-xl-4">
          <div className="card border-0 h-100 pi-rules-card">
            <div className="card-header border-0 pt-6 pb-0">
              <h3 className="card-title fw-bold fs-4 mb-0">
                {t("importRulesTitle", "قواعد الرفع")}
              </h3>
            </div>
            <div className="card-body pt-4">
              <div className="pi-rules-rail">
                {rules.map((rule, idx) => (
                  <div className="pi-rule" key={idx}>
                    <div className="pi-rule-index">
                      {String(idx + 1).padStart(2, "0")}
                    </div>
                    <div className="pi-rule-icon">
                      <i className={`ki-outline ${rule.icon} fs-2`} />
                    </div>
                    <div className="pi-rule-body">
                      <div className="pi-rule-title">{rule.title}</div>
                      <div className="pi-rule-text">{rule.body}</div>
                    </div>
                  </div>
                ))}
              </div>
              <div className="pi-legend mt-5">
                <div className="pi-legend-item">
                  <span className="pi-dot ok" />
                  {t("importStatusOk", "سليم")}
                </div>
                <div className="pi-legend-item">
                  <span className="pi-dot exists" />
                  {t("importStatusExists", "موجود")}
                </div>
                <div className="pi-legend-item">
                  <span className="pi-dot error" />
                  {t("importStatusError", "مشكلة")}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="col-xl-8">
          <div className="card border-0 h-100 pi-upload-card">
            <div className="card-header border-0 pt-6 pb-0">
              <h3 className="card-title fw-bold fs-4 mb-0">
                {t("importUploadTitle", "منطقة الرفع")}
              </h3>
            </div>
            <div className="card-body">
              {emsCan("create") ? (
                <>
                  <div
                    {...getRootProps({
                      className: `pi-dropzone ${activeDrop ? "is-active" : ""} ${
                        file || data.length ? "has-file" : ""
                      }`,
                    })}
                  >
                    <input {...getInputProps()} />
                    <div className="pi-dropzone-glow" aria-hidden="true" />
                    <div className="pi-dropzone-inner text-center">
                      <div className="pi-drop-icon mb-4">
                        <i className="ki-outline ki-cloud-add fs-3x" />
                      </div>
                      <h4 className="fw-bold mb-2">
                        {activeDrop
                          ? t("importDropNow", "أفلت الملف هنا")
                          : t("importDropTitle", "اسحب ملف Excel وأفلته هنا")}
                      </h4>
                      <p className="text-muted mb-4">
                        {t(
                          "importDropHint",
                          "أو اختر الملف من جهازك — XLSX / XLS / CSV"
                        )}
                      </p>
                      <button
                        type="button"
                        className="btn btn-primary"
                        onClick={open}
                        disabled={previewing || importing}
                      >
                        <i className="ki-outline ki-folder-up fs-3 me-1" />
                        {t("browseFiles", "استعراض الملفات")}
                      </button>
                    </div>
                  </div>

                  {(file || data.length > 0) && (
                    <div className="pi-file-chip mt-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                      <div className="d-flex align-items-center gap-3 min-w-0">
                        <div className="pi-file-badge">
                          <i className="ki-outline ki-file fs-2" />
                        </div>
                        <div className="min-w-0">
                          <div className="fw-bold text-truncate">
                            {file?.name || t("importPreviewTitle", "معاينة البيانات")}
                          </div>
                          <div className="text-muted fs-7">
                            {previewing
                              ? t("importReading", "جاري القراءة...")
                              : `${okCount} ${t("importStatusOk", "سليم")} · ${existsCount} ${t(
                                  "importStatusExists",
                                  "موجود"
                                )} · ${errorCount} ${t("importStatusError", "مشكلة")}`}
                          </div>
                        </div>
                      </div>
                      <div className="d-flex gap-2">
                        <button
                          type="button"
                          className="btn btn-light btn-sm"
                          onClick={clearFile}
                          disabled={importing}
                        >
                          {t("clear", "إزالة")}
                        </button>
                        <button
                          type="button"
                          className="btn btn-primary btn-sm"
                          onClick={handleFileUpload}
                          disabled={importing || previewing || (!data.length && !file)}
                        >
                          {importing ? (
                            <>
                              <span className="spinner-border spinner-border-sm me-2" />
                              {t("importing", "جاري الاستيراد...")}
                            </>
                          ) : (
                            <>
                              <i className="ki-outline ki-check fs-4 me-1" />
                              {t("import1", "استيراد")}
                            </>
                          )}
                        </button>
                      </div>
                    </div>
                  )}
                </>
              ) : (
                <div className="alert alert-warning mb-0">
                  {t("noPermission", "لا تملك صلاحية الاستيراد")}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="card border-0 pi-preview-card">
        <div className="card-header border-0 pt-6">
          <h3 className="card-title align-items-start flex-column">
            <span className="card-label fw-bold fs-4 mb-1">
              {t("importPreviewTitle", "معاينة البيانات")}
            </span>
            <span className="text-muted fw-semibold fs-7">
              {data.length
                ? `${data.length} ${t("rowsReady", "صف")} — ${t(
                    "importEditHint",
                    "عدّل الخلايا الحمراء ثم انقر خارجها لإعادة التحقق"
                  )}`
                : t("importPreviewEmpty", "ستظهر هنا صفوف الملف بعد اختياره")}
            </span>
          </h3>
        </div>
        <div className="card-body pt-0">
          {previewing ? (
            <div className="text-center py-10 text-muted">
              <span className="spinner-border mb-3" />
              <div>{t("importReading", "جاري القراءة...")}</div>
            </div>
          ) : data.length ? (
            <ImportPreviewTable
              rows={data}
              translations={translations}
              onCellChange={handleCellChange}
              onCellBlur={handleCellBlur}
              revalidating={revalidating}
            />
          ) : (
            <div className="pi-empty-preview text-center py-10">
              <i className="ki-outline ki-row-horizontal fs-3x text-muted mb-3 d-block" />
              <div className="text-muted">
                {previewError ||
                  t("importPreviewEmpty", "ستظهر هنا صفوف الملف بعد اختياره")}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default DataImport;

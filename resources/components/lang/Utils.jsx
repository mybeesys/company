export const getName = (name_en, name_ar, dir) => {
    const d = String(dir ?? "").trim().toLowerCase();
    if (d === "ltr") return name_en;
    return name_ar;
};

/**
 * Prefer snake_case; accept camelCase from some payloads.
 * `dir` should match <html dir> (ltr / rtl).
 */
export const getRowName = (row, dir) => {
    if (!row || typeof row !== "object") return "";
    const nameAr = row.name_ar ?? row.nameAr;
    const nameEn = row.name_en ?? row.nameEn;
    const d = String(dir ?? "").trim().toLowerCase();
    if (d === "ltr") {
        if (nameEn != null && String(nameEn).length) return String(nameEn);
        if (nameAr != null && String(nameAr).length) return String(nameAr);
        return row.name != null ? String(row.name) : "";
    }
    if (nameAr != null && String(nameAr).length) return String(nameAr);
    if (nameEn != null && String(nameEn).length) return String(nameEn);
    return row.name != null ? String(row.name) : "";
};

export const toDate = (dateTimeString, type) =>{
  if(!!!dateTimeString) return null;
  if(type == 'D')
      return new Date(dateTimeString);
  else
      return new Date(`01/01/2024 ${dateTimeString}`)
}

export const roundDecimal = (value, maxDecimals = 6) => {
  if (value === null || value === undefined || value === "") return 0;
  const num = Number(value);
  if (!Number.isFinite(num)) return 0;
  const factor = 10 ** maxDecimals;
  return Math.round(num * factor) / factor;
};

export const formatDecimal = (value, maxDecimals = 6) => {
  if (value === null || value === undefined || value === "") return "";
  const num = Number(value);
  if (!Number.isFinite(num)) return "";
  const rounded = roundDecimal(num, maxDecimals);
  return rounded
    .toFixed(maxDecimals)
    .replace(/(\.\d*?[1-9])0+$/, "$1")
    .replace(/\.0+$/, "");
};
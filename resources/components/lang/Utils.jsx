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

export const formatDecimal = (value) => {
  return parseFloat(value).toFixed(2);
};
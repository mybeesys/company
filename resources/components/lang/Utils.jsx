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

/** Recipe row id e.g. "12-i" → list type for getUnitsTransferList */
export const parseRecipeNewId = (newid) => {
  if (newid == null || newid === "") {
    return { itemId: null, listType: null };
  }
  const raw = String(newid);
  if (raw.includes("-")) {
    const [id, type] = raw.split("-");
    const listType =
      type === "p" ? "product" : type === "m" ? "modifier" : "ingredint";
    return { itemId: id, listType };
  }
  return { itemId: raw, listType: "ingredint" };
};

export const getUnitTransferId = (unitTransfer) => {
  if (unitTransfer == null) return null;
  if (typeof unitTransfer === "object") {
    return (
      unitTransfer.id ??
      unitTransfer.value ??
      unitTransfer.data?.id ??
      null
    );
  }
  return unitTransfer;
};

/** Product of transfer factors from selected unit up to the main unit (unit2 = null). */
export const getUnitFactorToMain = (unitTransferId, units) => {
  if (!units?.length || unitTransferId == null) return 1;

  const unitsMap = Object.fromEntries(units.map((u) => [u.id, u]));
  const main = units.find((u) => u.unit2 == null);
  if (!main) return 1;
  if (Number(unitTransferId) === Number(main.id)) return 1;

  let factor = 1;
  let currentId = unitTransferId;
  const visited = new Set();

  while (currentId != null && Number(currentId) !== Number(main.id)) {
    if (visited.has(String(currentId))) return null;
    visited.add(String(currentId));
    const unit = unitsMap[currentId];
    if (!unit) return null;
    const transfer = parseFloat(unit.transfer);
    if (!Number.isFinite(transfer) || transfer <= 0) break;
    factor *= transfer;
    currentId = unit.unit2;
  }

  return factor;
};

/** itemCost is per main/base unit; quantity is in the selected unit_transfer. */
export const calculateRecipeLineCost = (
  quantity,
  itemCost,
  unitTransferId,
  units
) => {
  const qty = parseFloat(quantity);
  const cost = parseFloat(itemCost);
  if (!Number.isFinite(qty) || !Number.isFinite(cost)) return 0;

  const factor = getUnitFactorToMain(unitTransferId, units);
  if (!factor || factor <= 0) {
    return roundDecimal(qty * cost, 4);
  }

  return roundDecimal((qty / factor) * cost, 4);
};
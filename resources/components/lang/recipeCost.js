import axios from "axios";
import {
  calculateRecipeLineCost,
  getUnitTransferId,
  parseRecipeNewId,
} from "./Utils";

const unitsCache = {};

export async function fetchRecipeItemUnits(newid) {
  const { itemId, listType } = parseRecipeNewId(newid);
  if (!itemId || !listType) return [];

  const cacheKey = `${listType}/${itemId}`;
  if (unitsCache[cacheKey]) return unitsCache[cacheKey];

  const response = await axios.get(
    `/getUnitsTransferList/${listType}/${itemId}`
  );
  unitsCache[cacheKey] = response.data;
  return response.data;
}

export async function computeRecipeRowCost(
  newid,
  quantity,
  unitTransfer,
  ingredientCost
) {
  const units = await fetchRecipeItemUnits(newid);
  const unitTransferId = getUnitTransferId(unitTransfer);
  return calculateRecipeLineCost(
    quantity,
    ingredientCost,
    unitTransferId,
    units
  );
}

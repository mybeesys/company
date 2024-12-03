export const getName = (name_en, name_ar, dir) => {
    if (dir == 'ltr')
      return name_en;
    else
      return name_ar
}

export const getRowName = (row, dir) => {
  if (dir == 'ltr')
    return !!row.name_en ? row.name_en : row.name;
  else
    return !!row.name_ar ? row.name_ar : row.name;
}

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
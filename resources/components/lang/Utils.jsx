export const getName = (name_en, name_ar, dir) => {
    if (dir == 'ltr')
      return name_en;
    else
      return name_ar
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
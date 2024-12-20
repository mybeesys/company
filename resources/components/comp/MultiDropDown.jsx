import React, { useEffect } from "react";
import Select from "react-select";
import makeAnimated from "react-select/animated";

//const animatedComponents = makeAnimated();

function MultiDropDown({ key, options, value, onChange }) {
  // Ensure selected value is always in sync with the options
  const getValue = () => {
    return options.filter((opt) => value.some((val) => val.value === opt.value));
  };

  return (
    <Select
      id={key}
      isMulti={true}
      options={options}
      closeMenuOnSelect={false}
      //components={animatedComponents}
      value={getValue()} // Ensure value matches current options
      onChange={(selected) => onChange(selected)} // Pass changes upstream
      menuPortalTarget={document.body}
      styles={{ menuPortal: (base) => ({ ...base, zIndex: 100000 }) }}
    />
  );
}

export default MultiDropDown;
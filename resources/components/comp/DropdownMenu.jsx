import React, { useState } from "react";
import "./DropdownMenu.css"; // Import the CSS for styling

const DropdownMenu = ({actions, data, translations, afterExecute}) => {
  const [isOpen, setIsOpen] = useState(false);

  // Toggle dropdown visibility
  const toggleDropdown = () => {
    setIsOpen(!isOpen);
  };

  // Close dropdown when clicking outside
  const closeDropdown = (event) => {
    if (!event.target.closest(".dropdown")) {
      setIsOpen(false);
    }
  };

  React.useEffect(() => {
    document.addEventListener("click", closeDropdown);
    return () => document.removeEventListener("click", closeDropdown);
  }, []);

  return (
    <div className="dropdown">
      <button className="dropdown-button" onClick={toggleDropdown}>
        •••
      </button>
      {isOpen && (
        <div className="dropdown-content">
          {actions.map((action) => 
            <a href="javascript:void(0);" onClick={(e)=> {
              action.action(data);
              afterExecute();
            }}>{translations[action.key]}</a>
          )}
        </div>
      )}
    </div>
  );
};

export default DropdownMenu;
import React, { useState } from "react";
import "./DropdownMenu.css"; // Import the CSS for styling

const DropdownMenu = ({ actions, data, translations, afterExecute }) => {
    const [isOpen, setIsOpen] = useState(false);

    // Toggle dropdown visibility
    const toggleDropdown = (e) => {
        setIsOpen(!isOpen);
    };

    const openMenu = (e) => {
        e.preventDefault();
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
            <button className="dropdown-button" onClick={(e) => openMenu(e)}>
                •••
            </button>
            {isOpen && (
                <div className="dropdown-content">
                    {actions.map((action) => (
                        <a
                            key={action.key}
                            href="javascript:void(0);"
                            onClick={(e) => {
                                e.preventDefault(); // Prevent default anchor action
                                toggleDropdown(); // Toggle the dropdown visibility
                                action.action(data, afterExecute); // Call the action function
                            }}
                        >
                            {translations[action.key]}
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
};

export default DropdownMenu;

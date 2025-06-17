import { useEffect, useState } from "react";
import EstablishmentTable from "../comp/EstablishmentTable";

const IngredinsEstablishment = ({
    translations,
    dir,
    currentObject,
    onEstablishmentChange,
}) => {
    const [selectedEstablishments, setSelectedEstablishments] = useState([]);
    const isEditMode = window.location.href.includes("edit");
    useEffect(() => {
        const allEstablishmentIds = currentObject.establishments.map(
            (establishment) =>
                isEditMode ? establishment.establishment.id : establishment.id
        );
        setSelectedEstablishments(allEstablishmentIds);
        onEstablishmentChange(allEstablishmentIds);
    }, [currentObject.establishments]);

    const handleSelectChange = (id) => {
        setSelectedEstablishments((prevSelected) => {
            const newSelected = prevSelected.includes(id)
                ? prevSelected.filter((item) => item !== id)
                : [...prevSelected, id];
            onEstablishmentChange(newSelected);
            return newSelected;
        });
    };

    return (
        <EstablishmentTable
            translations={translations}
            establishments={currentObject.establishments}
            onSelectChange={handleSelectChange}
            selectedEstablishments={selectedEstablishments}
        />
    );
};

export default IngredinsEstablishment;

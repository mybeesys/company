import { useEffect, useState } from "react";
import EstablishmentTable from "../comp/EstablishmentTable";

const ProductEstablishment = ({
    translations,
    dir,
    currentObject,
    onEstablishmentChange,
}) => {
    const [selectedEstablishments, setSelectedEstablishments] = useState([]);

    useEffect(() => {
        const allEstablishmentIds = currentObject.establishments.map(
            (establishment) => establishment.id
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

export default ProductEstablishment;

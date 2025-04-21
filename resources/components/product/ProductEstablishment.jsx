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

    const handleDelete = (row) =>{
        let index = currentObject.establishments.findIndex(x=>x.id == row.id);
        currentObject.establishments.splice(index, 1); // Removes 1 element at index 2
        onBasicChange("establishment", currentObject.establishments);
        return { message : 'Done'};
    }

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
            onDelete={handleDelete}
            onSelectChange={handleSelectChange}
            selectedEstablishments={selectedEstablishments}
        />
    );
};

export default ProductEstablishment;

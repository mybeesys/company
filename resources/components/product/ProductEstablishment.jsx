import { useEffect, useState } from "react";
import EstablishmentTable from "../comp/EstablishmentTable";

const ProductEstablishment = ({
    translations,
    dir,
    currentObject,
    onEstablishmentChange,
}) => {
    const [selectedEstablishments, setSelectedEstablishments] = useState([]);
    const [allEstablishments, setAllEstablishments] = useState([]);
    const isEditMode = window.location.href.includes("edit");
    useEffect(() => {
        if (isEditMode && currentObject.allEstablishments) {
            const productEstablishmentIds = currentObject.establishments
                .map((est) => est.establishment?.id)
                .filter((id) => id !== undefined);

            if (productEstablishmentIds.length === 0) {
                setSelectedEstablishments(
                    currentObject.allEstablishments.map((est) => est.id)
                );
            } else {
                setSelectedEstablishments(productEstablishmentIds);
            }
            onEstablishmentChange(productEstablishmentIds);
            setAllEstablishments(currentObject.allEstablishments);
        } else {
            const allEstablishmentIds = currentObject.establishments.map(
                (est) => est.id
            );
            setSelectedEstablishments(allEstablishmentIds);
            onEstablishmentChange(allEstablishmentIds);
            setAllEstablishments(currentObject.establishments);
        }
    }, [
        currentObject.establishments,
        currentObject.allEstablishments,
        isEditMode,
    ]);

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
            dir={dir}
            establishments={
                isEditMode ? allEstablishments : currentObject.establishments
            }
            onSelectChange={handleSelectChange}
            selectedEstablishments={selectedEstablishments}
        />
    );
};

export default ProductEstablishment;

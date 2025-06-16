import React from "react";
import { Table } from "react-bootstrap";

const EstablishmentTable = ({
    translations,
    dir,
    establishments,
    onSelectChange,
    selectedEstablishments,
}) => {
    const handleSelectChange = (id) => {
        if (onSelectChange) {
            onSelectChange(id);
        }
    };
    const isEditMode = window.location.href.includes("edit");

    return (
        <div className="card mb-5 mb-xl-8">
            <div className="card-header">
                <h3 className="card-title">{translations.establishments}</h3>
            </div>
            <div className="card-body">
                <Table striped bordered hover>
                    <thead>
                        <tr>
                            <th>{translations.select}</th>
                            <th>{translations.establishment_name}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {establishments.map((establishment) => {
                            const establishmentId = isEditMode
                                ? establishment.id
                                : establishment.id;
                            return (
                                <tr key={establishmentId}>
                                    <td>
                                        <input
                                        
                                            type="checkbox"
                                            checked={selectedEstablishments.includes(
                                                establishmentId
                                            )}
                                            onChange={() =>
                                                handleSelectChange(
                                                    establishmentId
                                                )
                                            }
                                        />
                                    </td>
                                    <td>{establishment.name}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </Table>
            </div>
        </div>
    );
};

export default EstablishmentTable;

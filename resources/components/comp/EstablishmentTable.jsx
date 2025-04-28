import React from "react";
import { Table } from "react-bootstrap";

const EstablishmentTable = ({
    translations,
    establishments,
    onSelectChange,
    selectedEstablishments,
}) => {
    const handleSelectChange = (id) => {
        onSelectChange(id);
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
                            console.log("Establishment Data:", establishment);
                            return (
                                <tr key={establishment.id}>
                                    <td>
                                        <input
                                            type="checkbox"
                                            checked={selectedEstablishments.includes(
                                                isEditMode
                                                    ? establishment
                                                          .establishment.id
                                                    : establishment.id
                                            )}
                                            onChange={() =>
                                                handleSelectChange(
                                                    isEditMode
                                                        ? establishment
                                                              .establishment.id
                                                        : establishment.id
                                                )
                                            }
                                        />
                                    </td>
                                    <td>
                                        {isEditMode
                                            ? establishment.establishment.name
                                            : establishment.name}
                                    </td>
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

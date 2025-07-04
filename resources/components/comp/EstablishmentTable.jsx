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
        <div className="card1 m-5 m-xl-5">
            <div className="card-header">
                <h3 className="card-title my-4">{translations.establishments}</h3>
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
                                            style={{
                                                transform: "scale(1.5)",
                                                margin: "5px",
                                                cursor: "pointer",
                                                width: "17px",
                                                height: "17px",
                                            }}
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

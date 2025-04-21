import React from "react";
import { Table } from "react-bootstrap";

const EstablishmentTable = ({
    translations,
    establishments,
    onDelete,
    onSelectChange,
    selectedEstablishments,
}) => {
    const handleSelectChange = (id) => {
        onSelectChange(id);
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: translations.confirmDelete,
            text: translations.confirmDeleteMessage,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: translations.yes,
            cancelButtonText: translations.no,
        }).then((result) => {
            if (result.isConfirmed) {
                onDelete(id);
            }
        });
    };

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
                            <th>{translations.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {establishments.map((establishment) => (
                            <tr key={establishment.id}>
                                <td>
                                    <input
                                        type="checkbox"
                                        checked={selectedEstablishments.includes(
                                            establishment.id
                                        )}
                                        onChange={() =>
                                            handleSelectChange(establishment.id)
                                        }
                                    />
                                </td>
                                <td>{establishment.name}</td>
                                <td>
                                    <div className="flex flex-wrap gap-2">
                                        <a
                                            href="javascript:void(0)"
                                            className="btn btn-icon btn-bg-light btn-active-color-primary btn-sm"
                                            onClick={() =>
                                                handleDelete(establishment.id)
                                            }
                                        >
                                            <i className="ki-outline ki-trash fs-2"></i>
                                        </a>
                                        <span>{translations.delete}</span>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </div>
        </div>
    );
};

export default EstablishmentTable;

import axios from "axios";
import { useState } from "react";
import SweetAlert2 from "react-sweetalert2";
import DropdownMenu from "../../comp/DropdownMenu";
import TreeTableComponent from "../../comp/TreeTableComponent";
import React from "react";

const TransferTable = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const [showAlert, setShowAlert] = useState(false);
    const [currentRow, setCurrentRow] = useState({});

    const handleActionClick = (actionKey, data) => {
        if (actionKey === "partiallyReceived") {
            const transferId = data.id;
            const type = "partiallyReceived";
            window.location.href = `/transfer/${transferId}/edit?type=${type}`;
        }
        if (actionKey === "fullyReceived") {
            axios
                .post(`${window.location.origin}/transfer/full-receiving`, {
                    id: data.id,
                })
                .then((response) => {})
                .catch((error) => {
                    alert("error");
                });
        }
        if (actionKey === "inTransit") {
            axios
                .post(`${window.location.origin}/transfer/inTransit`, {
                    id: data.id,
                })
                .then((response) => {})
                .catch((error) => {
                    alert("error");
                });
        }
        if (actionKey === "rejected") {
            axios
                .post(`${window.location.origin}/transfer/rejected`, {
                    id: data.id,
                })
                .then((response) => {})
                .catch((error) => {
                    alert("error");
                });
        }
    };

    const statusCell = (data, key, editMode, editable) => {
        return !!editMode ? (
            <></>
        ) : (
            <span class={`status status${data[key]}`}>{` ${
                translations[data[key]]
            }`}</span>
        );
    };

    const dropdownCell = (data, key, editMode, editable, refreshTree) => {
        let actions = [];
        if (
            data.transfer_status !== "fullyReceived" &&
            data.transfer_status !== "partiallyReceived" &&
            data.transfer_status !== "rejected"
        ) {
            actions.push(
                {
                    key: "partiallyReceived",
                    action: () => handleActionClick("partiallyReceived", data),
                },
                {
                    key: "fullyReceived",
                    action: () => handleActionClick("fullyReceived", data),
                },
                {
                    key: "inTransit",
                    action: () => handleActionClick("inTransit", data),
                },
                {
                    key: "rejected",
                    action: () => handleActionClick("rejected", data),
                }
            );
        } else if (data.transfer_status === "partiallyReceived") {
            actions.push({
                key: "partiallyReceived",
                action: () => handleActionClick("partiallyReceived", data),
            });
        }

        return (
            <DropdownMenu
                actions={actions}
                data={data}
                translations={translations}
                afterExecute={refreshTree}
            />
        );
    };

    const canEditRow = (data) => {
        return data.status == "draft";
    };

    const prepareData = (data) => {
        return data.map((row) => {
            return { key: row.id, data: { ...row } };
        });
    };

    return (
        <div>
            <SweetAlert2 />
            <TreeTableComponent
                translations={translations}
                dir={dir}
                urlList={`${urlList}`}
                editUrl={"transfer/%/edit"}
                addUrl={"transfer/create"}
                canEditRow={canEditRow}
                canAddInline={false}
                title="transfers"
                cols={[
                    {
                        key: "ref_no",
                        title: "number",
                        autoFocus: true,
                        type: "Text",
                        width: "15%",
                    },
                    {
                        key: "establishment",
                        title: "from",
                        autoFocus: true,
                        type: "AsyncDropDown",
                        width: "15%",
                    },
                    {
                        key: "toEstablishment",
                        title: "to",
                        autoFocus: true,
                        type: "AsyncDropDown",
                        width: "15%",
                    },
                    {
                        key: "transaction_date",
                        title: "date",
                        autoFocus: true,
                        type: "Date",
                        width: "15%",
                    },
                    {
                        key: "transfer_status",
                        title: "op_status",
                        autoFocus: true,
                        type: "Date",
                        width: "15%",
                        customCell: statusCell,
                    },
                    {
                        key: "dd",
                        autoFocus: true,
                        type: "Date",
                        width: "15%",
                        customCell: dropdownCell,
                    },
                ]}
                prepareData={prepareData}
            />
        </div>
    );
};

export default TransferTable;

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
        if (data.status != "approved")
            actions.push(
                {
                    key: "partiallyReceived",
                    action: () => handleActionClick("partiallyReceived", data),
                },
                {
                    key: "fullyReceived",
                },
                {
                    key: "pending",
                },
                {
                    key: "inTransit",
                },
                {
                    key: "rejected",
                }
            );
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
                        key: "status",
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

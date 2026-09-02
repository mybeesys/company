import axios from "axios";
import { useState } from "react";
import SweetAlert2 from "react-sweetalert2";
import DropdownMenu from "../../comp/DropdownMenu";
import TreeTableComponent from "../../comp/TreeTableComponent";
import Swal from "sweetalert2";
import { emsCan } from "../../emsCan";

const WasteTable = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const [showAlert, setShowAlert] = useState(false);
    const [currentRow, setCurrentRow] = useState({});

    const changeStatus = (data, status, afterExecute) => {
        axios
            .post("statusUpdate", { id: data.id, status: status })
            .then((resp) => {
                const updatedData = { ...data, status: resp.data.status };
                afterExecute(updatedData);
                Swal.fire({
                    title: `${resp.data.ref_no} ${
                        translations[updatedData["status"]]
                    }`,
                    icon: "success",
                    timer: 2000,
                    showCancelButton: false,
                    showConfirmButton: false,
                });
            })
            .catch((ex) => {
                console.error("Error updating status:", ex);
            });
    };

    const statusCell = (data, key, editMode, editable) => {
        return !!editMode ? (
            <></>
        ) : (
            <span className={`status status-${data[key]}`}>
                {translations[data[key]]}
            </span>
        );
    };

    const dropdownCell = (data, key, editMode, editable, refreshTree) => {
        let actions = [];
        if (data.status !== "approved" && emsCan("update")) {
            actions.push({
                key: "approved",
                action: (data, afterExecute) => {
                    changeStatus(data, "approved", afterExecute);
                },
            });
        }
        actions.push({
            key: "view",
            action: () => {
                showDetails(data);
            },
        });

        return (
            <DropdownMenu
                actions={actions}
                data={data}
                translations={translations}
                afterExecute={refreshTree}
            />
        );
    };

    const showDetails = (data) => {
        const details = data.sell_lines.map((line) => ({
            product:
                dir === "rtl" ? line.product.name_ar : line.product.name_en,
            reason: data.description || "",
            quantity: line.qyt,
            employee:
                dir === "rtl" ? data.created_by.name : data.created_by.name_en,
            date: data.transaction_date,
        }));

        const tableContent = details
            .map(
                (detail) => `
            <tr>
                <td>${detail.product}</td>
                <td>${detail.reason}</td>
                <td>${detail.quantity}</td>
                <td>${detail.employee}</td>
                <td>${detail.date}</td>
            </tr>
        `
            )
            .join("");

        Swal.fire({
            title: translations["waste_details"],
            html: `<div style="overflow-x:auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 16px;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">${
                                dir === "rtl"
                                    ? translations.products
                                    : translations.products
                            }</th> 
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">${
                            dir === "rtl"
                                ? translations.reason
                                : translations.reason
                        }</th> 
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">${
                            dir === "rtl"
                                ? translations.quantity
                                : translations.quantity
                        }</th> 
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">${
                            dir === "rtl"
                                ? translations.employee
                                : translations.employee
                        }</th> 
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #f2f2f2;">${
                            dir === "rtl"
                                ? translations.date
                                : translations.date
                        }</th>  
                        </tr>
                    </thead>
                    <tbody>
                        ${tableContent}
                    </tbody>
                </table>
            </div>`,
            showCloseButton: true,
            focusConfirm: false,
            confirmButtonText: translations["close"],
            width: "80%",
            height: "auto",
        });
    };

    const canEditRow = (data) => {
        return data.status === "draft";
    };

    const onSave = (data) => {};

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
                editUrl={"waste/%/edit"}
                addUrl={"waste/create"}
                canEditRow={canEditRow}
                canAddInline={false}
                title="waste"
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
                        title: "status",
                        autoFocus: true,
                        type: "Text",
                        width: "15%",
                        customCell: statusCell,
                    },
                    {
                        key: "actions",
                        autoFocus: true,
                        type: "Text",
                        width: "15%",
                        customCell: dropdownCell,
                    },
                ]}
                prepareData={prepareData}
            />
        </div>
    );
};

export default WasteTable;

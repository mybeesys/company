import { useEffect, useState } from "react";
import TreeTableComponent from "../../comp/TreeTableComponent";
import { getName, getRowName } from "../../lang/Utils";
import makeAnimated from "react-select/animated";
import Select from "react-select";
import ProductTransactionModal from "./ProductTransactionModal";

const animatedComponents = makeAnimated();
const ProductInventoryTable = ({ dir, translations, p_type }) => {
    const rootElement = document.getElementById("root");
    const baseListUrl = JSON.parse(rootElement.getAttribute("list-url"));
    const summaryUrl = JSON.parse(rootElement.getAttribute("summary-url") || '""');
    const criticalCsvUrl = JSON.parse(rootElement.getAttribute("critical-csv-url") || '""');
    const [urlList, setUrlList] = useState(baseListUrl);
    const [searchBy, setSearchBy] = useState({
        label: translations.product,
        value: 1,
    });
    const [searchText, setSearchText] = useState("");
    const [statusFilter, setStatusFilter] = useState("all");
    const [isTransactionModalVisible, setIsTransactionModalVisible] =
        useState(false);
    const [productTrnsaction, setProductTransaction] = useState([]);
    const [summary, setSummary] = useState(null);
    const [summaryLoading, setSummaryLoading] = useState(false);
    /*    const canEditRow = (data) => {
        return data.type == "product" || data.type == "Ingredient";
    };*/

    const labels = {
        all: dir === "rtl" ? "الكل" : "All",
        out_of_stock: dir === "rtl" ? "نفد المخزون" : "Out of stock",
        low_stock: dir === "rtl" ? "مخزون منخفض" : "Low stock",
        normal: dir === "rtl" ? "مخزون طبيعي" : "Normal",
        total_items: dir === "rtl" ? "إجمالي الأصناف" : "Total Items",
        total_cost: dir === "rtl" ? "قيمة المخزون بالتكلفة" : "Inventory Cost Value",
        critical_items: dir === "rtl" ? "العناصر الحرجة" : "Critical Items",
        warehouse: dir === "rtl" ? "المستودع/الفرع" : "Warehouse/Branch",
        product: dir === "rtl" ? "الصنف" : "Product",
        qty: dir === "rtl" ? "الكمية" : "Qty",
        threshold: dir === "rtl" ? "حد إعادة الطلب" : "Reorder Threshold",
        status: dir === "rtl" ? "الحالة" : "Status",
        export_critical_csv: dir === "rtl" ? "تصدير CSV للعناصر الحرجة" : "Export Critical CSV",
        stock_status_filter: dir === "rtl" ? "فلتر حالة المخزون" : "Stock Status Filter",
        reset: dir === "rtl" ? "إعادة ضبط" : "Reset",
    };

    const buildListUrl = (text, status) => {
        const key = text ? encodeURIComponent(text) : "";
        const queryStatus = status || "all";
        return `${baseListUrl}?by=${searchBy.value}&key=${key}&status=${queryStatus}`;
    };

    const fetchSummary = () => {
        if (!summaryUrl) {
            return;
        }
        setSummaryLoading(true);
        axios
            .get(summaryUrl)
            .then((response) => setSummary(response.data))
            .catch(() => setSummary(null))
            .finally(() => setSummaryLoading(false));
    };

    useEffect(() => {
        fetchSummary();
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            setUrlList(buildListUrl(searchText, statusFilter));
        }, 450);
        return () => clearTimeout(timer);
    }, [searchText, statusFilter, searchBy]);

    const resetFilters = () => {
        const defaultSearchBy = { label: translations.product, value: 1 };
        setSearchText("");
        setStatusFilter("all");
        setSearchBy(defaultSearchBy);
        setUrlList(`${baseListUrl}?by=${defaultSearchBy.value}&key=&status=all`);
    };

    const print = (type) => {
        if (!!searchText) {
            window.open(
                `../productInventoryReport/1/productInventory_${type}?type=${
                    p_type == "product" ? "p" : "i"
                }&by=${searchBy.value}&key=${searchText}&t=1`,
                "_blank"
            );
        } else {
            window.open(
                `../productInventoryReport/1/productInventory_${type}?type=${
                    p_type == "product" ? "p" : "i"
                }&by=${-1}&key=&t=1`,
                "_blank"
            );
        }
    };

    const openTransactionModel = (data) => {
        axios
            .get(
                `/listTransactions?est=${data.establishment_id}&typ=${data.type}&id=${data.id}`
            )
            .then((response) => {
                console.log("RES", response.data);
                setProductTransaction(response.data);
            })
            .catch((error) => {
                console.error("Error fetching transactions:", error);
            });

        setIsTransactionModalVisible(true);
    };

    const handleClose = () => {
        setIsTransactionModalVisible(false);
    };
    const canEditRow = () => false;
    return (
        <div>
            <div className="row g-3 mb-4">
                <div className="col-md-2">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body py-3">
                            <div className="text-muted fs-7">{labels.total_items}</div>
                            <div className="fw-bold fs-2">{summaryLoading ? "..." : (summary?.kpis?.total_items ?? 0)}</div>
                        </div>
                    </div>
                </div>
                <div className="col-md-2">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body py-3">
                            <div className="text-muted fs-7">{labels.out_of_stock}</div>
                            <div className="fw-bold fs-2 text-danger">{summaryLoading ? "..." : (summary?.kpis?.out_of_stock ?? 0)}</div>
                        </div>
                    </div>
                </div>
                <div className="col-md-2">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body py-3">
                            <div className="text-muted fs-7">{labels.low_stock}</div>
                            <div className="fw-bold fs-2 text-warning">{summaryLoading ? "..." : (summary?.kpis?.low_stock ?? 0)}</div>
                        </div>
                    </div>
                </div>
                <div className="col-md-2">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body py-3">
                            <div className="text-muted fs-7">{labels.normal}</div>
                            <div className="fw-bold fs-2 text-success">{summaryLoading ? "..." : (summary?.kpis?.normal ?? 0)}</div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div className="text-muted fs-7">{labels.total_cost}</div>
                                <div className="fw-bold fs-2">{summaryLoading ? "..." : (summary?.kpis?.total_cost_value ?? 0).toLocaleString()}</div>
                            </div>
                            {!!criticalCsvUrl && (
                                <a href={criticalCsvUrl} className="btn btn-sm btn-light-primary">
                                    {labels.export_critical_csv}
                                </a>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm mb-4">
                <div className="card-body">
                    <div className="row g-3 align-items-end">
                        <div className="col-md-3">
                            <label htmlFor="name_ar" className="col-form-label">
                            {translations.search}
                        </label>
                        <ProductTransactionModal
                            visible={isTransactionModalVisible}
                            onClose={handleClose}
                            transactions={productTrnsaction}
                            translations={translations}
                        />
                        <Select
                            id="search_id"
                            isMulti={false}
                            options={[
                                { label: translations.product, value: 1 },
                            ]}
                            closeMenuOnSelect={true}
                            components={animatedComponents}
                            value={searchBy}
                            onChange={(val) => setSearchBy(val)}
                            menuPortalTarget={document.body}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 100000,
                                }),
                            }}
                        />
                    </div>
                        <div className="col-md-3">
                        <input
                            type="text"
                            className="form-control form-control-solid"
                            id="name_en"
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                        ></input>
                        </div>
                        <div className="col-md-2">
                            <label className="col-form-label">{labels.stock_status_filter}</label>
                        <select
                            className="form-control form-control-solid"
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                        >
                            <option value="all">{labels.all}</option>
                            <option value="out_of_stock">{labels.out_of_stock}</option>
                            <option value="low_stock">{labels.low_stock}</option>
                            <option value="normal">{labels.normal}</option>
                        </select>
                        </div>
                        <div className="col-md-2 d-flex gap-2">
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="btn btn-light w-100"
                            >
                                {labels.reset}
                            </button>
                        </div>
                        <div className="col-md-2 d-flex justify-content-end gap-2">
                            <button
                                type="button"
                                title={translations.visitLink}
                                onClick={() => print("xls")}
                                className="btn btn-light-success btn-sm"
                            >
                                Excel
                            </button>
                            <button
                                type="button"
                                onClick={() => print("pdf")}
                                className="btn btn-light-primary btn-sm"
                            >
                                PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="card border-0 shadow-sm mb-4">
                <div className="card-header border-0 pt-4">
                    <h3 className="card-title">{labels.critical_items}</h3>
                </div>
                <div className="card-body pt-0">
                    <div className="table-responsive">
                        <table className="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>{labels.warehouse}</th>
                                    <th>{labels.product}</th>
                                    <th>{labels.qty}</th>
                                    <th>{labels.threshold}</th>
                                    <th>{labels.status}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(summary?.critical_items || []).map((item, idx) => (
                                    <tr key={`${item.product_name}-${idx}`}>
                                        <td>{item.establishment_name}</td>
                                        <td>{item.product_name}</td>
                                        <td>{Number(item.qty || 0).toFixed(2)}</td>
                                        <td>{Number(item.threshold || 0).toFixed(2)}</td>
                                        <td>
                                            <span className={`badge ${item.stock_status === "out_of_stock" ? "badge-light-danger" : "badge-light-warning"}`}>
                                                {labels[item.stock_status] || item.stock_status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                                {(summary?.critical_items || []).length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="text-center text-muted">-</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div className="pt-3">
                <TreeTableComponent
                    translations={translations}
                    dir={dir}
                    urlList={urlList}
                    addUrl={null}
                    canAddInline={false}
                    canEditRow={canEditRow}
                    title={`${p_type}s`}
                    expander
                    cols={[
                        {
                            key: "name",
                            autoFocus: true,
                            options: [],
                            type: "Text",
                            width: "50%",
                            customCell: (data, key, editMode, editable) => {
                                return (
                                    <>
                                        <span>{getRowName(data, dir)}</span>
                                    </>
                                );
                            },
                        },
                        {
                            key: "qty",
                            autoFocus: false,
                            options: [],
                            type: "Decimal",
                            width: "50%",
                        },
                    ]}
                    actions={[]}
                    onUpdate={null}
                    onDelete={null}
                />
            </div>
        </div>
    );
};

export default ProductInventoryTable;

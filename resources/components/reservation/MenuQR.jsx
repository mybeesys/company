import { QRCodeCanvas } from "qrcode.react";
import { useEffect, useState } from "react";
import AsyncSelectComponent from "../comp/AsyncSelectComponent";
import { BlockPicker } from "react-color";
import { getRowName } from "../lang/Utils";
import SweetAlert2 from "react-sweetalert2";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();
const MenuQR = ({ translations, dir }) => {
    const rootElement = document.getElementById("root");
    const logourl = rootElement.getAttribute("logo-url");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const [showAlert, setShowAlert] = useState(false);
    const [currentObject, setCurrentObject] = useState({
        selectedProducts: [],
    });
    const [qrInfo, setQrInfo] = useState({});
    const [products, setProducts] = useState([]);
    useEffect(() => {
        getProducts();
    }, []);

    const getProducts = async () => {
        try {
            const response = await axios.get(urlList);
            const result = response.data;
            let accumulatedProducts = [];

            result.forEach((category) => {
                if (Array.isArray(category.children_with_products)) {
                    category.children_with_products.forEach((subCategory) => {
                        if (Array.isArray(subCategory.products_for_sale)) {
                            accumulatedProducts = [
                                ...accumulatedProducts,
                                ...subCategory.products_for_sale,
                            ];
                        }
                    });
                }
            });

            setProducts(
                accumulatedProducts.map((product) => ({
                    value: product.id,
                    label: dir === "rtl" ? product.name_ar : product.name_en,
                    price: product.price,
                    category_id: product.category_id,
                }))
            );
        } catch (error) {
            console.error("Error fetching products:", error);
        }
    };

    const onChange = (key, val) => {
        setCurrentObject((prev) => {
            const updatedObject = { ...prev, [key]: val };
            return updatedObject;
        });
    };

    const generateQR = () => {
        if (!currentObject.establishment) {
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: "Error",
                html: `${translations.establishment} ${translations.required}`,
                icon: "error",
                timer: 4000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false);
            });
            return;
        }

        const selectedProducts = Array.isArray(currentObject.selectedProducts)
            ? currentObject.selectedProducts
            : [];

        const productIds = selectedProducts
            .map((product) => product.value)
            .join(",");
        setQrInfo({
            id: `qr-${getRowName(currentObject.establishment, dir)}`,
            url: `${window.location.origin}/menuSimple?est_id=${
                currentObject.establishment.id
            }&title=${currentObject.title ?? ""}&sub_title=${
                currentObject.subTitle ?? ""
            }&products=${productIds}`,
            color: !currentObject.color ? "#000000" : currentObject.color,
            logo: !currentObject.showLogo
                ? {}
                : {
                      src: logourl,
                      x: undefined,
                      y: undefined,
                      height: 56,
                      width: 56,
                      excavate: true,
                  },
        });
    };

    const downloadQRCode = () => {
        const canvas = document.getElementById(qrInfo.id);
        canvas.toBlob((blob) => {
            saveAs(blob, `qr-menu.png`);
        });
    };

    return (
        <div class="row">
            <div class="col-5">
                <div class="card-body" dir={dir}>
                    <div class="d-flex align-items-center pt-3">
                        <label
                            class="fs-6 fw-semibold mb-2 me-3"
                            style={{ width: "150px" }}
                        >
                            {translations.showLogo}
                        </label>
                        <div class="form-check form-switch">
                            <InputSwitch
                                checked={!!currentObject.showLogo}
                                onChange={(e) => onChange("showLogo", e.value)}
                            />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-12">
                                <label for="name_ar" class="col-form-label">
                                    {translations.establishment}
                                </label>
                                <AsyncSelectComponent
                                    field="establishment"
                                    dir={dir}
                                    searchUrl={"searchEstablishments"}
                                    currentObject={currentObject.establishment}
                                    onBasicChange={(field, val) => {
                                        onChange(field, val);
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-12">
                                <label class="col-form-label">
                                    {translations.products}
                                </label>
                                <Select
                                    options={products}
                                    isMulti
                                    value={currentObject.selectedProducts}
                                    onChange={(selected) => {
                                        onChange(
                                            "selectedProducts",
                                            selected || []
                                        );
                                    }}
                                    components={animatedComponents}
                                    className="basic-multi-select"
                                    classNamePrefix="select"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-6">
                                <label for="name_ar" class="col-form-label">
                                    {translations.title}
                                </label>
                                <input
                                    type="text"
                                    class="form-control form-control-solid custom-height"
                                    id="name_ar"
                                    value={currentObject.title || ""}
                                    onChange={(e) =>
                                        onChange("title", e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div class="col-6">
                                <label for="name_en" class="col-form-label">
                                    {translations.subTitle}
                                </label>
                                <input
                                    type="text"
                                    class="form-control form-control-solid custom-height"
                                    id="name_en"
                                    value={currentObject.subTitle || ""}
                                    onChange={(e) =>
                                        onChange("subTitle", e.target.value)
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>
                    <div class="row pt-5">
                        <div class="col-3">
                            <label for="name_ar" class="col-form-label">
                                {translations.color}
                            </label>
                        </div>
                        <div class="col-8">
                            <div>
                                <div className="blockpicker">
                                    <div
                                        style={{
                                            backgroundColor: `${currentObject.color}`,
                                            width: 100,
                                            height: 50,
                                            border: "2px solid white",
                                        }}
                                    />
                                    <BlockPicker
                                        color={currentObject.color}
                                        onChange={(color) => {
                                            onChange("color", color.hex);
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-left pt-3" style={{ display: "flex" }}>
                        <button
                            onClick={generateQR}
                            class="btn btn-primary mx-2"
                            style={{ width: "12rem" }}
                        >
                            {translations.generateQr}
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-7">
                <div class="d-flex justify-content-center">
                    <div
                        style={{
                            border: "1px solid #ddd",
                            borderRadius: "10px",
                            padding: "15px",
                            textAlign: "center",
                            width: "350px",
                            backgroundColor: "#ffffff",
                            boxShadow: "0 4px 6px rgba(0, 0, 0, 0.1)",
                            margin: "10px",
                        }}
                    >
                        <QRCodeCanvas
                            id={qrInfo.id}
                            value={qrInfo.url}
                            size={300}
                            bgColor="#FFFFFF"
                            fgColor={qrInfo.color}
                            level={"H"}
                            imageSettings={qrInfo.logo}
                        />
                        <div
                            style={{
                                display: "flex",
                                justifyContent: "center",
                                gap: "10px",
                                marginTop: "10px",
                            }}
                        >
                            <button
                                onClick={downloadQRCode}
                                style={{
                                    border: "none",
                                    backgroundColor: "#f8f9fa",
                                    cursor: "pointer",
                                    padding: "5px 10px",
                                    borderRadius: "5px",
                                    boxShadow: "0 1px 3px rgba(0, 0, 0, 0.2)",
                                }}
                                title={translations.downloadQRCode}
                            >
                                📥
                            </button>
                            <a
                                href={qrInfo.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{
                                    border: "none",
                                    backgroundColor: "#f8f9fa",
                                    cursor: "pointer",
                                    padding: "5px 10px",
                                    borderRadius: "5px",
                                    boxShadow: "0 1px 3px rgba(0, 0, 0, 0.2)",
                                    textDecoration: "none",
                                }}
                                title={translations.visitLink}
                            >
                                🔗
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default MenuQR;

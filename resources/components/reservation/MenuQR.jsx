import { QRCodeCanvas } from "qrcode.react";
import { useState } from "react";
import AsyncSelectComponent from "../comp/AsyncSelectComponent";
import { BlockPicker } from "react-color";
import { getRowName } from "../lang/Utils";
import SweetAlert2 from 'react-sweetalert2';
import { InputSwitch } from "primereact/inputswitch";

const MenuQR = ({ translations, dir }) => {
    const rootElement = document.getElementById('root');
    const logourl = rootElement.getAttribute('logo-url');
    const [showAlert, setShowAlert] = useState(false);
    const [currentObject, setCurrrentObject] = useState({ color: '#000000' });
    const [qrInfo, setQrInfo] = useState({});

    const onChange = (key, val) => {
        currentObject[key] = val;
        setCurrrentObject({ ...currentObject });
    }

    const generateQR = () =>{
        if(!!!currentObject.establishment){
            setShowAlert(true);
            Swal.fire({
                show: showAlert,
                title: 'Error',
                html: `${translations.establishment} ${translations.required}`,
                icon: "error",
                timer: 4000,
                showCancelButton: false,
                showConfirmButton: false,
            }).then(() => {
                setShowAlert(false); // Reset the state after alert is dismissed
            });
            return;
        }
        setQrInfo({
            id : `qr-${getRowName(currentObject.establishment, dir)}`,
            url : `${window.location.origin}/menuSimple?est_id=${currentObject.establishment.id}&title=${currentObject.title ?? ''}&sub_title=${currentObject.subTitle ?? ''}`,
            color : !!!currentObject.color? '#000000' : currentObject.color,
            logo : !!!currentObject.showLogo ? {} : {
                src: logourl, // URL of the logo to embed
                x: undefined, // X-coordinate of the logo (centered by default)
                y: undefined, // Y-coordinate of the logo (centered by default)
                height: 56, // Height of the logo
                width: 56, // Width of the logo
                excavate: true, // Whether to "excavate" (clear the area behind the logo)
            }
        });
    }

    const downloadQRCode = () => {
        const canvas = document.getElementById(qrId);
        canvas.toBlob((blob) => {
            saveAs(blob, `qr-menu.png`);
        });
    };

    return (
        <div class="row">
            <div class="col-5">
                <div class="card-body" dir={dir} >
                    <div class="d-flex  align-items-center pt-3">
                        <label class="fs-6 fw-semibold mb-2 me-3 "
                            style={{ width: "150px" }}>{translations.showLogo}</label>
                        <div class="form-check form-switch">
                            <InputSwitch checked={!!currentObject.showLogo ? !!currentObject.showLogo : false}
                                onChange={(e) => onChange('showLogo', e.value)} />
                        </div>
                    </div>  
                    <div class="form-group">
                        <div class="row">
                            <div class="col-12">
                                <label for="name_ar" class="col-form-label">{translations.establishment}</label>
                                <AsyncSelectComponent
                                    field='establishment'
                                    dir={dir}
                                    searchUrl={'searchEstablishments'}
                                    currentObject={currentObject.establishment}
                                    onBasicChange={(field, val) => {
                                        onChange(field, val);
                                    }} />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-6">
                            <label for="name_ar" class="col-form-label">{translations.title}</label>
                            <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={!!currentObject.title ? currentObject.title : ''}
                                onChange={(e) => onChange('title', e.target.value)} required></input>
                            </div>
                            <div class="col-6">
                            <label for="name_en" class="col-form-label">{translations.subTitle}</label>
                            <input type="text" class="form-control form-control-solid custom-height" id="name_en" value={!!currentObject.subTitle ? currentObject.subTitle : ''}
                                onChange={(e) => onChange('subTitle', e.target.value)} required></input>
                            </div>
                        </div>
                    </div>
                    <div class="row pt-5">
                        <div class="col-3">
                            <label for="name_ar" class="col-form-label">{translations.color}</label>
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
                                    ></div>
                                    {/* Block Picker from react-color and handling color on onChange event */}
                                    <BlockPicker
                                        color={currentObject.color}
                                        onChange={(color) => {
                                            onChange('color', color.hex)
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-left pt-3" style={{ "display": "flex" }}>
                        <button onClick={generateQR} class="btn btn-primary mx-2"
                            style={{ "width": "12rem" }}>{translations.generateQr}</button>
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

                        {/* QR Code */}
                        <QRCodeCanvas
                            id={qrInfo.id}
                            value={qrInfo.url}
                            size={300}
                            bgColor='#FFFFFF'
                            fgColor={qrInfo.color}
                            level={"H"} // Error correction level (supports L, M, Q, H)
                            imageSettings={qrInfo.logo}
                        />

                        {/* Buttons */}
                        <div
                            style={{
                                display: "flex",
                                justifyContent: "center",
                                gap: "10px",
                                marginTop: "10px",
                            }}
                        >
                            {/* Download Button */}
                            <button
                                onClick={(e) => downloadQRCode()}
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

                            {/* Visit Link Button */}
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

}

export default MenuQR;
import { QRCodeCanvas } from "qrcode.react";


const MenuQR = ({ translations, dir }) => {

    const downloadQRCode = () => {
        const canvas = document.getElementById(`qr-menu`);
        canvas.toBlob((blob) => {
            saveAs(blob, `qr-menu.png`);
        });
    };

    return (
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
                id={`qr-menu`}
                value={`${window.location.origin}/menuSimple`}
                size={300}
                bgColor="#ffffff"
                fgColor="#000000"
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
                    href={`${window.location.origin}/menuSimple`}
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
    );

}

export default MenuQR;
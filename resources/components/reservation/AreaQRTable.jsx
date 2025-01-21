import React, { useEffect, useState } from "react";
import { QRCodeCanvas } from "qrcode.react";
import { getRowName } from "../lang/Utils";

const AreaQRTable = ({translations, dir }) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    const [nodes, setNodes] = useState([]);

    useEffect(() => {
        refreshTree();
    }, []);

    const refreshTree = () => {
        try {
            axios.get(urlList).then(response => {
                let result = response.data;
                setNodes(result);
            });
        } catch (error) {
            console.error('There was an error get the product!', error);
        }
    }

    const downloadQRCode = (code) => {
        const canvas = document.getElementById(`qr-${code}`);
        canvas.toBlob((blob) => {
            saveAs(blob, `${code}-qr.png`);
        });
    };

    return (
        nodes.map((area)=>
            area.data.empty =='Y' ? <></> :
        <div>
            <div class="container">
                <span class="title">{getRowName(area.data, dir)}</span>
                <div class="badge">{`${area.children.length} ${translations.tables1}`}</div>
            </div>
            <div style={{ display: "flex", gap: "20px", flexWrap: "wrap", padding: "20px" }}>
                {area.children.map((table) => (
                    table.data.empty =='Y' ? <></> :
                    <div
                        style={{
                            border: "1px solid #ddd",
                            borderRadius: "10px",
                            padding: "15px",
                            textAlign: "center",
                            width: "200px",
                            backgroundColor: "#ffffff",
                            boxShadow: "0 4px 6px rgba(0, 0, 0, 0.1)",
                            margin: "10px",
                        }}
                    >
                        <div class="row" style={{"padding-bottom" : "5px"}}>
                            <div class="col-4 table-id">{table.data.code}</div>
                            <div class="col-8 table-seats">{`${table.data.steating_capacity} ${translations.steatingCapacity1}`}</div>
                        </div>  

                        {/* QR Code */}
                        <QRCodeCanvas
                            id={`qr-${table.data.code}`}
                            value={JSON.stringify(table.data)}
                            size={150}
                            bgColor="#ffffff"
                            fgColor="#000000"
                        />

                        {/* Table Info */}
                        <p style={{ fontSize: "14px", margin: "10px 0" }}>Table {table.data.code}</p>

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
                                onClick={(e)=>downloadQRCode(table.data.code)}
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
                                href={table.data.link}
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
                ))}
            </div>
        </div>)
    );
};

export default AreaQRTable;
import React, { useState, useCallback, useEffect } from "react";
import { useDropzone } from "react-dropzone";
import { BlockPicker } from "react-color";

const ProductDisplay = ({
    translations,
    parentHandlechanges,
    product,
    saveChanges,
}) => {
    const rootElement = document.getElementById("root");
    const imageurl = rootElement.getAttribute("image-url");
    const blankurl = rootElement.getAttribute("blank-url");
    const dir = rootElement.getAttribute("dir");

    const [currentObject, setcurrentObject] = useState({ ...product });
    const [files, setFiles] = useState([]);
    const [imageSrc, setimageSrc] = useState(!imageurl ? blankurl : imageurl);
    const [blockPickerColor, setBlockPickerColor] = useState(
        currentObject.color || "#37d67a"
    );

    useEffect(() => {
        setcurrentObject((prev) => ({ ...prev, ...product }));
    }, [product]);

    const onDrop = useCallback((acceptedFiles) => {
        const mappedFiles = acceptedFiles.map((file) => ({
            ...file,
            preview: URL.createObjectURL(file),
        }));
        setFiles(mappedFiles);
        handleChange("image_file", mappedFiles[0]);
        setimageSrc(mappedFiles[0].preview);
    }, []);

    const { getRootProps, getInputProps } = useDropzone({
        onDrop,
        accept: "image/*",
        multiple: false,
    });

    const deleteImage = () => {
        setFiles([]);
        setimageSrc(blankurl);
        handleChange("image_file", null);
    };

    const handleChange = (key, value) => {
        setcurrentObject((prev) => {
            const updatedObject = { ...prev, [key]: value };
            if (key === "image_file" && !value) {
                updatedObject.image_deleted = 1;
            }
            parentHandlechanges(updatedObject);
            return updatedObject;
        });
    };

    const clickSubmit = (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.checkValidity()) {
            form.classList.add("was-validated");
            return;
        }
        saveChanges();
    };

    return (
        <div className="card-body" dir={dir}>
            <form onSubmit={clickSubmit}>
                <div className="form-group pt-10">
                    <div className="row">
                        <div className="col-3">
                            <label className="col-form-label">
                                {translations.image}
                            </label>
                        </div>
                        <div className="col-3">
                            <div
                                className="image-input image-input-empty image-input-outline mb-3 mx-auto text-center"
                                style={{
                                    maxWidth: "180px",
                                    position: "relative",
                                }}
                            >
                                <div
                                    {...getRootProps({
                                        className:
                                            "image-input-wrapper w-150px h-150px mx-auto",
                                    })}
                                >
                                    <input {...getInputProps()} />
                                    <img
                                        src={imageSrc}
                                        style={{ maxWidth: "180px" }}
                                        alt="Preview"
                                    />
                                </div>
                                <label
                                    className="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    onClick={deleteImage}
                                    style={{
                                        position: "absolute",
                                        top: "10px",
                                        right: "10px",
                                    }}
                                >
                                    <i className="ki-outline ki-cross fs-7"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div className="row pt-20">
                        <div className="col-3">
                            <label className="col-form-label">
                                {translations.color}
                            </label>
                        </div>
                        <div className="col-8">
                            <div className="blockpicker">
                                <div
                                    style={{
                                        backgroundColor: blockPickerColor,
                                        width: 100,
                                        height: 50,
                                        border: "2px solid white",
                                    }}
                                />
                                <BlockPicker
                                    color={blockPickerColor}
                                    onChange={(color) => {
                                        handleChange("color", color.hex);
                                        setBlockPickerColor(color.hex);
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    <input type="submit" id="btnSubmit" hidden />
                </div>
            </form>
        </div>
    );
};

export default ProductDisplay;

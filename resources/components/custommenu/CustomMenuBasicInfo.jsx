import { useEffect, useState } from "react";
import axios from "axios";

const CustomMenuBasicInfo = ({
    translations,
    currentObject,
    onBasicChange,
    dir,
}) => {
    const [modesStations, setModesStations] = useState({
        modes: [],
        stations: [],
    });
    const [applicationTypeValues, setApplicationTypeValues] = useState([]);
    const [applicationType, setApplicationType] = useState(
        currentObject.application_type
    );

    const getName = (name_en, name_ar) => {
        return dir === "ltr" ? name_en : name_ar;
    };

    useEffect(() => {
        const fetchData = async () => {
            const res2 = await axios.get("/application-type-values");
            const appType = res2.data;
            const res3 = await axios.get("/mode-values");
            const res4 = await axios.get("/stations");
            const ms = {
                modes: res3.data.map((e) => ({
                    name: e.name,
                    value: e.value,
                })),
                stations: res4.data.map((e) => ({
                    name: getName(e.name_en, e.name_ar),
                    value: e.id,
                })),
            };
            setApplicationTypeValues(appType);
            setModesStations(ms);
        };
        fetchData().catch(console.error);
        console.log("Application type:", applicationType);
    }, [applicationType]);

    const getStationsModesSource = () => {
        return currentObject.application_type === "3"
            ? modesStations.stations
            : modesStations.modes;
    };

    const getStationsModesValues = (name) => {
        return currentObject.application_type === "3"
            ? name
            : translations[name];
    };

    const getStationsModesKey = () => {
        return currentObject.application_type === "3" ? "station_id" : "mode";
    };

    const handleApplicationTypeChange = (value) => {
        setApplicationType(value);
        onBasicChange("application_type", value);
    };

    return (
        <div className="card-body" dir={dir}>
            <div className="form-group">
                <div className="row">
                    <div className="col-6">
                        <label htmlFor="name_ar" className="col-form-label">
                            {translations.name_ar}
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-solid custom-height"
                            id="name_ar"
                            value={currentObject.name_ar}
                            onChange={(e) =>
                                onBasicChange("name_ar", e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="col-6">
                        <label htmlFor="name_en" className="col-form-label">
                            {translations.name_en}
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-solid custom-height"
                            id="name_en"
                            value={currentObject.name_en}
                            onChange={(e) =>
                                onBasicChange("name_en", e.target.value)
                            }
                            required
                        />
                    </div>
                </div>
            </div>
            <div className="form-group">
                <div className="row">
                    <div className="col-6">
                        <label
                            htmlFor="application_type"
                            className="col-form-label"
                        >
                            {translations.application_type}
                        </label>
                        <select
                            className="form-control form-control-solid custom-height selectpicker"
                            style={{ height: "50px" }}
                            value={applicationType}
                            onChange={(e) =>
                                handleApplicationTypeChange(e.target.value)
                            }
                        >
                            {applicationTypeValues.map((applicationType) => (
                                <option
                                    key={applicationType.value}
                                    value={applicationType.value}
                                >
                                    {translations[applicationType.name]}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-6">
                        <label
                            htmlFor="mode_station_name"
                            className="col-form-label"
                        >
                            {translations.mode_station_name}
                        </label>
                        <select
                            className="form-control form-control-solid custom-height selectpicker"
                            style={{ height: "50px" }}
                            disabled={applicationType == "1"}
                            value={currentObject[getStationsModesKey()]}
                            onChange={(e) =>
                                onBasicChange(
                                    getStationsModesKey(),
                                    e.target.value
                                )
                            }
                        >
                            {applicationType !== "1" &&
                                getStationsModesSource().map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {getStationsModesValues(option.name)}
                                    </option>
                                ))}
                        </select>
                    </div>
                </div>
            </div>
            <div className="d-flex align-items-center pt-3">
                <label
                    className="fs-6 fw-semibold mb-2 me-3"
                    style={{ width: "150px" }}
                >
                    {translations.active}
                </label>
                <div className="form-check">
                    <input
                        type="checkbox"
                        style={{ border: "1px solid #9f9f9f" }}
                        className="form-check-input my-2"
                        id="active"
                        checked={!!currentObject.active === 1}
                        onChange={(e) =>
                            onBasicChange("active", e.target.checked ? 1 : 0)
                        }
                    />
                </div>
            </div>
        </div>
    );
};

export default CustomMenuBasicInfo;

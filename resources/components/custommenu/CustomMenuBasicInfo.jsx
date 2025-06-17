import { useEffect, useState } from "react";
import axios from "axios";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();

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
    const getStationsModesKey = () => {
        return applicationType === "3" ? "station_id" : "mode";
    };
    const selectedValues = currentObject[getStationsModesKey()] || [];
    const [valueArray, setValueArray] = useState([]);
    const normalizedValueArray = Array.isArray(selectedValues)
        ? selectedValues
        : [selectedValues];

    const getName = (name_en, name_ar) => {
        return dir === "ltr" ? name_en : name_ar;
    };
    useEffect(() => {
        const fetchData = async () => {
            try {
                const res2 = await axios.get("/application-type-values");
                const appType = res2.data;
                const res3 = await axios.get("/mode-values");
                const res4 = await axios.get("/stations");
                const ms = {
                    stations: res4.data.map((e) => ({
                        name: getName(e.name_en, e.name),
                        value: e.id,
                    })),
                    modes: res3.data.map((e) => ({
                        name: e.name,
                        value: e.value,
                    })),
                };
                setApplicationTypeValues(appType);
                setModesStations(ms);
            } catch (error) {
                console.error("Error fetching data:", error);
            }
        };
        fetchData();
    }, []);
    const getStationsModesSource = () => {
        return applicationType === "3"
            ? modesStations.stations
            : modesStations.modes;
    };

    const getStationsModesValues = (name) => {
        return applicationType === "3" ? name : translations[name];
    };

    const handleApplicationTypeChange = (value) => {
        setApplicationType(value);
        onBasicChange("application_type", value);
        setValueArray([]);
    };

    const handleStationChange = (selectedValues) => {
        onBasicChange(getStationsModesKey(), selectedValues);
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
                            {applicationTypeValues.map((appType) => (
                                <option
                                    key={appType.value}
                                    value={appType.value}
                                >
                                    {translations[appType.name]}
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
                        <Select
                            isMulti
                            options={getStationsModesSource().map((option) => ({
                                value: option.value,
                                label: getStationsModesValues(option.name),
                            }))}
                            className="basic-multi-select"
                            styles={{
                                control: (base) => ({
                                    ...base,
                                    minHeight: "50px",
                                    height: "auto",
                                    minWidth: "200px",
                                    flexWrap: "wrap",
                                    display: "flex",
                                    backgroundColor: "#F9F9F9",
                                }),
                            }}
                            classNamePrefix="select"
                            isDisabled={applicationType === "1"}
                            value={valueArray.map((value) => {
                                const originalOption =
                                    getStationsModesSource().find(
                                        (option) => option.value === value
                                    );
                                const label = originalOption
                                    ? getStationsModesValues(
                                          originalOption.name
                                      )
                                    : "";
                                return {
                                    value,
                                    label,
                                };
                            })}
                            onChange={(selectedOptions) => {
                                const selectedValues = selectedOptions.map(
                                    (option) => option.value
                                );
                                setValueArray(selectedValues);
                                handleStationChange(selectedValues);
                            }}
                        />
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
                        checked={currentObject.active === 1}
                        onChange={(e) => {
                            const isChecked = e.target.checked ? 1 : 0;
                            onBasicChange("active", isChecked);
                        }}
                    />
                </div>
            </div>
        </div>
    );
};

export default CustomMenuBasicInfo;

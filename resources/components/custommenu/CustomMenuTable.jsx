import React, { useEffect, useState } from "react";
import TreeTableCustomMenu from "./TreeTableCustomMenu";

const CustomMenuTable = ({ dir, translations }) => {
    const rootElement = document.getElementById("root");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const applicationTypeUrl = JSON.parse(
        rootElement.getAttribute("application-type-url")
    );
    const modeUrl = JSON.parse(rootElement.getAttribute("mode-url"));
    let stationUrl = JSON.parse(rootElement.getAttribute("station-url"));

    const [modesStations, setModesStations] = useState({
        modes: [],
        stations: [],
    });
    const [applicationTypeValues, setApplicationTypeValues] = useState([]);

    const getName = (name_en, name_ar) => {
        if (dir == "ltr") return name_en;
        else return name_ar;
    };

    useEffect(() => {
        const fetchData = async () => {
            const res2 = await axios.get(applicationTypeUrl);
            const appType = res2.data.map((e) => {
                return { name: translations[e.name], value: e.value };
            });
            const res3 = await axios.get(modeUrl);
            const res4 = await axios.get(stationUrl);
            const ms = {
                modes: res3.data.map((e) => {
                    return { name: translations[e.name], value: e.value };
                }),
                stations: res4.data.map((e) => {
                    return { name: getName(e.name_en, e.name), value: e.id };
                }),
            };
            setApplicationTypeValues(appType);
            setModesStations(ms);
        };
        fetchData().catch(console.error);
    }, [stationUrl, applicationTypeUrl]);

    return (
        <div>
            <TreeTableCustomMenu
                urlList={urlList}
                rootElement={rootElement}
                modesStations={modesStations}
                translations={translations}
                applicationTypeValues={applicationTypeValues}
                dir={dir}
            />
        </div>
    );
};

export default CustomMenuTable;

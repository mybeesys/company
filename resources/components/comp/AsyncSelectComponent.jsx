import { useEffect, useState } from "react";
import AsyncSelect from "react-select/async";
import { getName, getRowName } from "../lang/Utils";

const AsyncSelectComponent = ({
    field,
    currentObject,
    onBasicChange,
    dir,
    searchUrl,
    isMulti,
    classNamePrefix,
}) => {
    const [selectedOption, setSelectedOption] = useState(null); // State to store the selected value
    const [options, setOptions] = useState([]); // State to store the options
    const urlParams = new URLSearchParams(window.location.search);
    const urlPar = urlParams.get("type");
    const customStyles = {
        menu: (provided) => ({
            ...provided,
            maxHeight: 200, // Limit the height of the dropdown
            position: "absolute", // Enable vertical scrolling if content exceeds max height
            zIndex: 9999,
        }),
    };

    useEffect(() => {
        fetchOptions();
    }, [currentObject, searchUrl]);

    const fetchOptions = async () => {
        let url = searchUrl.includes("?")
            ? `/${searchUrl}&key=`
            : `/${searchUrl}?key=`;
        axios
            .get(url)
            .then((response) => {
                // Format the response data as needed by react-select
                let options = response.data.map((item) => ({
                    label: getRowName(item, dir), // The text shown in the select options
                    value: item.id, // The value of the selected option
                    data: item,
                }));
                if (
                    !!currentObject &&
                    !!currentObject.id &&
                    !!!options.find((x) => x.value == currentObject.id)
                )
                    options.push({
                        value: currentObject.id, // Option value
                        label: getRowName(currentObject, dir), // Option label
                        data: currentObject,
                    });
                setOptions(options);
            })
            .catch((error) => {
                console.error("Error fetching data:", error);
                reject([]); // Reject the Promise with an empty array in case of an error
            });
        // let optionsData = [];
        // // Replace this with your API call
        // if (!!currentObject)
        //     optionsData = [{
        //         value: currentObject.id,  // Option value
        //         label: getName(currentObject.name_en, currentObject.name_ar, dir) // Option label
        //     }];

        // setOptions(optionsData); // Set the options in state
    };

    const filterUnits = async (inputValue, resolve) => {
        let url = searchUrl.includes("?")
            ? `/${searchUrl}&key=${inputValue}`
            : `/${searchUrl}?key=${inputValue}`;
        axios
            .get(url)
            .then((response) => {
                // Format the response data as needed by react-select
                const options = response.data.map((item) => ({
                    label: getRowName(item, dir), // The text shown in the select options
                    value: item.id, // The value of the selected option
                    data: item,
                }));
                if (!!resolve) resolve(options); // Resolve the Promise with the formatted options
                setOptions(options);
            })
            .catch((error) => {
                console.error("Error fetching data:", error);
                reject([]); // Reject the Promise with an empty array in case of an error
            });
    };

    const setCurrentValue = () => {
        // Example of fetching the current value, this can be an API call or logic
        if (!!currentObject)
            setSelectedOption({
                value: currentObject.id, // Option value
                label: getRowName(currentObject, dir), // Option label
                data: currentObject,
            });
        else setSelectedOption(null);
    };

    useEffect(() => {
        if (options.length > 0) {
            setCurrentValue(); // Set current value once options are available
        }
    }, [options, currentObject]);

    const promiseOptions = (inputValue) =>
        new Promise((resolve) => {
            setTimeout(() => {
                filterUnits(inputValue, resolve);
            }, 1000);
        });

    return (
        <AsyncSelect
            cacheOptions
            loadOptions={promiseOptions}
            isMulti={isMulti}
            options={options}
            defaultOptions={options}
            value={selectedOption}
            classNamePrefix={classNamePrefix || undefined}
            className={classNamePrefix || undefined}
            isDisabled={!field.editable && urlPar === "partiallyReceived"}
            styles={{
                container: (base) => ({
                    ...base,
                    width: "100%",
                    minWidth: 0,
                }),
                control: (base) => ({
                    ...base,
                    minHeight: 42,
                    height: 42,
                    width: "100%",
                    minWidth: 0,
                    borderRadius: 8,
                    boxShadow: "none",
                }),
                valueContainer: (base) => ({
                    ...base,
                    padding: "0 10px",
                }),
                singleValue: (base) => ({
                    ...base,
                    maxWidth: "100%",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    whiteSpace: "nowrap",
                }),
                option: (base) => ({
                    ...base,
                    whiteSpace: "nowrap",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                }),
                menu: (base) => ({
                    ...base,
                    width: "100%",
                    minWidth: "100%",
                    maxWidth: "100%",
                    borderRadius: 8,
                    overflow: "hidden",
                }),
                menuPortal: (base, props) => {
                    const width =
                        props?.rect?.width ||
                        props?.controlElement?.getBoundingClientRect?.().width;
                    return {
                        ...base,
                        zIndex: 100000,
                        ...(width
                            ? { width, minWidth: width, maxWidth: width }
                            : {}),
                    };
                },
            }}
            menuPortalTarget={document.body}
            onChange={(e) =>
                onBasicChange(field, {
                    ...(e.data && typeof e.data === "object" ? e.data : {}),
                    id: e.value,
                    name: e.data?.name ?? e.label,
                    name_ar: e.data?.name_ar ?? e.data?.name ?? e.label,
                    name_en: e.data?.name_en ?? e.label,
                    data: e.data,
                })
            }
        />
    );
};
export default AsyncSelectComponent;

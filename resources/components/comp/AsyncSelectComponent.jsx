import { useEffect, useState } from "react";
import AsyncSelect from 'react-select/async';
import { getName } from "../lang/Utils";


const AsyncSelectComponent = ({ field, currentObject, onBasicChange, dir, searchUrl }) => {
    const [selectedOption, setSelectedOption] = useState(null); // State to store the selected value
    const [options, setOptions] = useState([]); // State to store the options

    useEffect(() => {
        fetchOptions();
    }, []);

    const fetchOptions = async () => {
        let optionsData = [];
        // Replace this with your API call
        if (!!currentObject)
            optionsData = [{
                value: currentObject.id,  // Option value
                label: getName(currentObject.name_en, currentObject.name_ar, dir) // Option label
            }];

        setOptions(optionsData); // Set the options in state
    };

    const filterUnits = async (inputValue, resolve) => {
        axios
            .get(`/${searchUrl}?key=${inputValue}`)
            .then((response) => {
                // Format the response data as needed by react-select
                const options = response.data.map(item => ({
                    label: getName(item.name_en, item.name_ar, dir),  // The text shown in the select options
                    value: item.id,    // The value of the selected option
                }));
                resolve(options); // Resolve the Promise with the formatted options
                setOptions(options);
            })
            .catch((error) => {
                console.error('Error fetching data:', error);
                reject([]); // Reject the Promise with an empty array in case of an error
            });
    };

    const setCurrentValue = () => {
        // Example of fetching the current value, this can be an API call or logic
        if (!!currentObject)
            // Set the current value of the Select component
            setSelectedOption({
                value: currentObject.id,  // Option value
                label: getName(currentObject.name_en, currentObject.name_ar, dir) // Option label
            });
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
            options={options}
            value={selectedOption}
            onChange={(e) => onBasicChange(field, {
                id: e.value,
                name_er: e.label,
                name_en: e.label
            })} />
    );
}
export default AsyncSelectComponent;
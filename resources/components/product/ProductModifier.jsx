import React, { useEffect, useState } from 'react';
import Select from 'react-select';
import ProductModiferDetail from './ProductModiferDetail';
import makeAnimated from 'react-select/animated';

const animatedComponents = makeAnimated();

const ProductModifier = ({translations, urlList, productId, productModifiers, onChange, onSelectAll}) => {
    const rootElement = document.getElementById('root');
    let dir = rootElement.getAttribute('dir');
    const [data, setData] = useState([]);
    const [selectedModifiers, setSelectedModifiers] = useState([]);  // Added state for selected modifiers

    useEffect(() => {
        axios.get(urlList)
        .then(response => {
          setData(response.data);
        })
        .catch(error => {
          console.error('Error fetching translations', error);
        });
    }, []);

    const handleChange = (modifierId, key, value) =>{
        onChange(modifierId, key, value)
    }

    const handleSelectAll = () =>{
        onSelectAll(data);
    }

    // Handle multi-select change
    const handleMultiSelectChange = (selectedOptions) => {
        setSelectedModifiers(selectedOptions);  // Update selected modifiers
    }

    return (
        <>
            <Select
                isMulti
                options={data.filter(x => x.data.empty !== 'Y').map(modifierClass => ({
                    label: dir === 'rtl' ? modifierClass.data.name_ar : modifierClass.data.name_en,
                    value: modifierClass.data.id,
                }))}
                value={selectedModifiers}
                onChange={handleMultiSelectChange}
                components={animatedComponents}
                className="basic-multi-select"
                classNamePrefix="select"
            />

            {/* Display details of selected modifiers */}
            {selectedModifiers.map(selectedModifier => {
                const modifierClass = data.find(m => m.data.id === selectedModifier.value);
                return modifierClass && (
                    <ProductModiferDetail
                        key={modifierClass.data.id}
                        translations={translations}
                        productId={productId}
                        modifierId={modifierClass.data.id}
                        title={dir === 'rtl' ? modifierClass.data.name_ar : modifierClass.data.name_en}
                        data={modifierClass.children || []}
                        productModifiers={productModifiers}
                        onchange={handleChange}
                        onSelectAll={handleSelectAll}
                    />
                );
            })}
        </>
    );
};

export default ProductModifier;

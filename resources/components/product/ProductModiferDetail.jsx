import React, { useEffect, useState } from 'react';
import Select from "react-select";
import makeAnimated from 'react-select/animated';

const animatedComponents = makeAnimated();

const defaultModifierValues =[{
    active : 0,
    required : 0,
    default: 0,
    min_modifiers: 0,
    max_modifiers: 0,
    display_order: 0,
    button_display: 0,
    modifier_display : 0
  }];

const ProductModiferDetail = ({ translations, modifierId, title, productModifiers, onchange, onSelectAll }) => {
    
    const displayOptions = [
        {label: translations.buttons , value: 0},
        {label: translations.dropdownlist , value: 1}
    ];
    
    const modifierDisplayOptions = [
        {label: `${translations.name} ${translations.only}` , value: 0},
        {label: `${translations.name} ${translations.and} ${translations.price}` , value: 1}
    ];

    const [modifierClass, setModifierClass] = useState({defaultModifierValues}); 
    
    const handleChange = (key, value) => {
        onchange(modifierId, key, value);
    }

    const handleSelectAll = () =>{
        onSelectAll();
    }

    useEffect(() => {
        let m = !!productModifiers.filter(m=> m.modifier_id  == modifierId).length > 0 ?
                productModifiers.filter(m=> m.modifier_id == modifierId) : defaultModifierValues;
        setModifierClass({...m[0]});
    }, [productModifiers]);
    
    return (
        <section class="product spad">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="trending__product">
                            <div class="row border-bottom">
                                <div class="col-lg-8 col-md-8 col-sm-8">
                                    <div class="section-title">
                                        <h4>{title}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4">
                                    <div class="btn__all">
                                        <a href="#" class="primary-btn" onClick={handleSelectAll}>Select All<span class="arrow_right"></span></a>
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                            <div class="row border-bottom border-dark">
                                <form onSubmit={(event) => clickSubmit(event)}>
                                    <div class="form-group" style={{ paddingtop: '5px' }}>
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="d-flex  align-items-center ">
                                                    <label class="fs-6 fw-semibold mb-2 me-3 "
                                                        style={{width: "150px"}}>{translations.active}</label>
                                                    <div class="form-check">
                                                        <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                                                            class="form-check-input my-2"
                                                            id="active" checked={modifierClass.active ==0 ? false : true}
                                                            onChange={(e) => handleChange('active', e.target.checked ? 1 : 0)}/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="d-flex  align-items-center ">
                                                    <label class="fs-6 fw-semibold mb-2 me-3 "
                                                        style={{width: "150px"}}>{translations.required}</label>
                                                    <div class="form-check">
                                                        <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                                                            class="form-check-input my-2"
                                                            id="required" checked={modifierClass.required ==0 ? false : true}
                                                            onChange={(e) => handleChange('required', e.target.checked ? 1 : 0)}/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                            <div class="d-flex  align-items-center ">
                                                    <label class="fs-6 fw-semibold mb-2 me-3 "
                                                        style={{width: "150px"}}>{translations.required}</label>
                                                    <div class="form-check">
                                                        <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                                                            class="form-check-input my-2"
                                                            id="required" checked={modifierClass.default ==0 ? false : true}
                                                            onChange={(e) => handleChange('default', e.target.checked ? 1 : 0)}/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="min_modifiers" class="col-form-label">{translations.min}</label>
                                                <input type="number" id="min_modifiers" min="0" class="form-control form-control-solid custom-height" value={modifierClass.min_modifiers}
                                                    onChange={(e) => handleChange('min_modifiers', e.target.value)}
                                                    required></input>
                                            </div>
                                            <div class="col-4">
                                                <label for="max_modifiers" class="col-form-label">{translations.max}</label>
                                                <input type="number" id="max_modifiers" min="0" class="form-control form-control-solid custom-height" value={modifierClass.max_modifiers}
                                                    onChange={(e) => handleChange('max_modifiers', e.target.value)}
                                                ></input>
                                            </div>
                                            <div class="col-4">
                                                <label for="display_order" class="col-form-label">{`${translations.display} ${translations.order}`}</label>
                                                <input type="number" id="display_order" class="form-control form-control-solid custom-height" value={modifierClass.display_order}
                                                    onChange={(e) => handleChange('display_order', e.target.value)}
                                                    required></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-4">
                                            <label for="button_display" class="col-form-label">{`${translations.button} ${translations.display}`}</label>
                                            <Select
                                                id="button_display"
                                                isMulti={false}
                                                options={displayOptions}
                                                closeMenuOnSelect={true}
                                                components={animatedComponents}
                                                value={displayOptions[modifierClass.button_display]}
                                                onChange={val => handleChange('button_display', val.value, val)}
                                                menuPortalTarget={document.body} 
                                                styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                                            />
                                            </div>
                                            <div class="col-4">
                                                <label for="modifier_display" class="col-form-label">{`${translations.modifier} ${translations.display}`}</label>
                                                <Select
                                                    id="modifier_display"
                                                    isMulti={false}
                                                    options={modifierDisplayOptions}
                                                    closeMenuOnSelect={true}
                                                    components={animatedComponents}
                                                    value={modifierDisplayOptions[modifierClass.modifier_display]}
                                                    onChange={val => handleChange('modifier_display', val.value, val)}
                                                    menuPortalTarget={document.body} 
                                                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default ProductModiferDetail;
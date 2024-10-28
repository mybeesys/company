import React, { useEffect, useState } from 'react';

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
                                                <label class="col-form-label col-12" >
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <input type="checkbox" class="form-check-input" id="active" checked={modifierClass.active ==0 ? false : true}
                                                                onChange={(e) => handleChange('active', e.target.checked ? 1 : 0)}
                                                            />
                                                        </div>
                                                        <div class="col-8">{translations.active}</div>
                                                    </div>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <label class="col-form-label col-12" >
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <input type="checkbox" class="form-check-input" id="required" checked={modifierClass.required ==0 ? false : true}
                                                                onChange={(e) => handleChange('required', e.target.checked ? 1 : 0)}
                                                            />
                                                        </div>
                                                        <div class="col-8">{translations.required}</div>
                                                    </div>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <label class="col-form-label col-12" >
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <input type="checkbox" class="form-check-input" id="default" checked={modifierClass.default ==0 ? false : true}
                                                                onChange={(e) => handleChange('default', e.target.checked ? 1 : 0)}
                                                            />
                                                        </div>
                                                        <div class="col-8">{translations.defaultModifier}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="min_modifiers" class="col-form-label">{translations.min}</label>
                                                <input type="number" id="min_modifiers" min="0" class="form-control" value={modifierClass.min_modifiers}
                                                    onChange={(e) => handleChange('min_modifiers', e.target.value)}
                                                    required></input>
                                            </div>
                                            <div class="col-4">
                                                <label for="max_modifiers" class="col-form-label">{translations.max}</label>
                                                <input type="number" id="max_modifiers" min="0" class="form-control" value={modifierClass.max_modifiers}
                                                    onChange={(e) => handleChange('max_modifiers', e.target.value)}
                                                ></input>
                                            </div>
                                            <div class="col-4">
                                                <label for="display_order" class="col-form-label">{`${translations.display} ${translations.order}`}</label>
                                                <input type="number" id="display_order" class="form-control" value={modifierClass.display_order}
                                                    onChange={(e) => handleChange('display_order', e.target.value)}
                                                    required></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="button_display" class="col-form-label">{`${translations.button} ${translations.display}`}</label>
                                                <select class="form-control selectpicker" value={modifierClass.button_display} id="button_display" 
                                                    onChange={(e) => handleChange('button_display', e.target.value)} >
                                                       <option value="0" label={translations.buttons}/>
                                                       <option value="1" label={translations.dropdownlist}/>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="modifier_display" class="col-form-label">{`${translations.modifier} ${translations.display}`}</label>
                                                <select class="form-control selectpicker" value={modifierClass.modifier_display} id ="modifier_display" 
                                                    onChange={(e) => handleChange('modifier_display', e.target.value)} >
                                                       <option value="0" label={`${translations.name} ${translations.only}`}/>
                                                       <option value="1" label={`${translations.name} ${translations.and} ${translations.price}`}/>
                                                </select>
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
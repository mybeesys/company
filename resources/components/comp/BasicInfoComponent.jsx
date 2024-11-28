import { useEffect, useState } from "react";
import AsyncSelectComponent from "./AsyncSelectComponent";
import { Calendar } from "primereact/calendar";
import { formatDecimal, toDate } from "../lang/Utils";


const BasicInfoComponent = ({ fields, translations, currentObject, onBasicChange, dir }) => {

    const renderInput = (field)=>{
        if(field.type == "Empty"){
            return (<></>)
        }
        if(field.type == "Async"){
            return (
                <>
                    <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    <AsyncSelectComponent
                    field={field.key}
                    dir={dir}
                    searchUrl={field.searchUrl}
                    currentObject={currentObject[field.key]}
                    onBasicChange={onBasicChange} />
                </>)
        }
        if(field.type == "Date"){
            return (<>
                    <div class="col-12">
                        <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    </div>
                    <div class="col-12">
                        <Calendar value={toDate(currentObject[field.key], 'D') } 
                        onChange={(e) => onBasicChange(field.key, 
                            !!e.value ? `${e.value.getFullYear()}-${(e.value.getMonth()+1).toString().padStart(2, '0')}-${e.value.getDate().toString().padStart(2, '0')}` 
                            : null)}
                        required={!!field.required}
                        readOnly={!!field.readOnly}/>
                    </div>
                </>
            );
        }
        if(field.type == "Decimal"){
            return (
                <>
                    <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    <input type="number" min="0" step=".01" class="form-control" id={field.key} 
                    value={!!currentObject[field.key] ? formatDecimal(currentObject[field.key]) : ''}
                        onChange={(e) => onBasicChange(field.key, e.target.value)}
                        required={!!field.required}
                        readOnly={!!field.readOnly}/>
                </>
            )
        }
        if(field.type == "Number"){
            return (
                <>
                    <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    <input type="number" min="0" class="form-control" id={field.key} 
                    value={currentObject[field.key]}
                        onChange={(e) => onBasicChange(field.key, e.target.value)}
                        required={!!field.required}
                        readOnly={!!field.readOnly}/>
                </>
            )
        }
        if(field.type == "Text"){
            return (
                <>
                    <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    <input type="text" class="form-control" id={field.key} value={currentObject[field.key]}
                        onChange={(e) => onBasicChange(field.key, e.target.value)} 
                        required={!!field.required}
                        readOnly={!!field.readOnly}/>
                </>
            )
        }
        if(field.type == "TextArea"){
            return (
                <>
                    <label for={field.key} class="col-form-label">{translations[field.title]}</label>
                    <textarea class="form-control" id={field.key} value={currentObject[field.key]}
                        onChange={(e) => onBasicChange(field.key, e.target.value)} 
                        required={!!field.required}
                        readOnly={!!field.readOnly}/>
                </>
            )
        }
    }

    return (
        <div class="card-body" dir={dir}>
            <div class="row">
                {fields.map((field, index) => {
                    const isNewRow = field.newRow;
                    return (
                        <>
                        {isNewRow && index !== 0 && <div className="w-100"></div>} {/* Start a new row */}
                                    <div key={field.key} className="col-6 col-md-6 mb-3">
                                        {renderInput(field)}
                            </div>
                        </>
                    );
                })}
            </div>
        </div>
    );
}
export default BasicInfoComponent;
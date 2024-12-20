import React, { useEffect, useState } from 'react';
import { TreeTable } from 'primereact/treetable';
import { Column } from 'primereact/column';
import { Calendar } from 'primereact/calendar';
import '../theme.css'

const CustomMenuTime = ({ translations, customMenuDates, onDateTimeChange }) => {

    const [editMode, SetEditMode] = useState(false);

    useEffect(() => {
    }, []);

    const hanldeTimeChange = (key, day_no, value)=>{
        onDateTimeChange("T", key, value, day_no);
    }

    const hanldeActiveChange = (key, day_no, value)=>{
        onDateTimeChange("C", key, value, day_no);
    }
    
    const hanldeDateChange = (key, value)=>{
        onDateTimeChange("D", key, value);
    }
    

    const renderCheckCell = (node, key, autoFocus) => {
        return (
            editMode ?
                <div>
                    <input type="checkbox" checked={node[key] == 1 ? true : false}
                        class="form-check-input" data-kt-check={node[key]}
                        data-kt-check-target=".widget-10-check"
                        onChange={(e) => {
                            hanldeActiveChange(key, node.day_no, e.target.checked ? 1 : 0)
                        }
                        }
                    />
                </div>
                :
                <div>
                    <input type="checkbox" defaultChecked={false} checked={node[key]}
                        class="form-check-input" data-kt-check={node[key]}
                        data-kt-check-target=".widget-10-check" disabled />
                </div>
        )
    }

    const renderDayCell = (node, key, autoFocus) => {
        return (
            <span>{translations[`day${node[key]}`]}</span>
        )
    }


    const renderTimeCell = (node, key) => {
        return (
            editMode?
            <Calendar 
                // minDate={key == 'toTime' ? node.data.fromTime : null} 
                // maxDate={key == 'fromTime' ? node.data.toTime : null} 
                timeOnly showTime hourFormat="24" 
                value={toDate(node[key], 'T')} 
                onChange={(e) => hanldeTimeChange(key, node.day_no, e.value)}  
             />
            :
            <span>{node[key]}</span>
        )
    }

    const toDate = (dateTimeString, type) =>{
        if(!!!dateTimeString) return null;
        if(type == 'D')
            return new Date(dateTimeString);
        else
            return new Date(`01/01/2024 ${dateTimeString}`)
    }

    const renderDateCell = (node, key, min, max) => {
        return (
            editMode?
            <Calendar minDate={min} maxDate={max} value={toDate(node[key], 'D') } onChange={(e) => hanldeDateChange(key, e.value)}></Calendar>
            :
            <span>{!!node[key] ? node[key] : ''}</span>
        )
    }

    return (
        <section class="product spad">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="trending__product">
                            <div class="row border-bottom">
                                <div class="col-lg-8 col-md-8 col-sm-8">
                                    <div class="section-title">
                                        <h4>{translations.effectiveDateTime}</h4>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4">
                                    <div class="btn__all">
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                                <div class="row border-bottom border-dark">
                                    <form >
                                        <TreeTable
                                        footer={
                                            <>
                                            <div class="row">
                                                <div class="col-4"><span><h5>{translations.fromDate}</h5></span></div>
                                                <div class="col-4"><span><h5>{translations.toDate}</h5></span></div>
                                                <div class="col-12"/>     
                                            </div>
                                            <div class="row">
                                                <div class="col-4"><span>{renderDateCell(customMenuDates, 'from_date', null, toDate(customMenuDates.to_date,'D'))}</span></div>
                                                <div class="col-4"><span>{renderDateCell(customMenuDates, 'to_date', toDate(customMenuDates.from_date, 'D'), null)}</span></div>
                                                <div class="col-1"/>
                                                <div class="col-3">
                                                    <a href='javascript:void(0)' onClick={(e) => SetEditMode(!editMode)}>
                                                        {translations.editEffectiveDateTime}
                                                    </a>
                                                </div>    
                                            </div>
                                            </>
                                        }
                                        value={customMenuDates.times} tableStyle={{ minWidth: '50rem' }} className={"custom-tree-table"}>
                                            <Column header={translations.day} style={{ width: '20%' }} body={(node) => (renderDayCell(node, 'day_no', true))} sortable expander></Column>
                                            <Column header="" style={{ width: '20%' }} body={(node) => (renderTimeCell(node, 'from_time'))} sortable></Column>
                                            <Column header="" style={{ width: '20%' }} body={(node) => (renderTimeCell(node, 'to_time'))} sortable></Column>
                                            <Column header="" style={{ width: '10%' }} body={(node) => (renderCheckCell(node, 'active'))} sortable> </Column>
                                        </TreeTable>
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

export default CustomMenuTime;
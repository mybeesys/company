import React, { useState, useCallback, useRef } from 'react';
import TreeTableEditorLocal from '../comp/TreeTableEditorLocal';

const UnitTransferIngredient = ({ translations, unitTransfer, unitTree, parentHandle, handleMainUnit, productUnit, dir }) => {


    const [nodes, setNodes] = useState(unitTransfer);
    const [globalId, setGlobalId] = useState(-2);
    const [innerUnits, setUnits] = useState(unitTree);
    const [mainUnit, setMainUnit] = useState(productUnit);
    const [showAlert, setShowAlert] = useState(false);

    React.useEffect(() => {
        setNodes(unitTransfer);
        setMainUnit(productUnit);
        if(!!!innerUnits || innerUnits.length ==0)
            setUnits(unitTree);
    }, [unitTransfer, productUnit, unitTree, innerUnits]);

    const handleDelete = (row) =>{
        let index = nodes.findIndex(x=>x.id == row.id);
        if(nodes.findIndex( x=>x.unit2 == row.id) > 0)
        {
            return { message : 'relatedUnitTransfer'}
        }
        nodes.splice(index, 1); // Removes 1 element at index 2
        setNodes([...nodes]);
        parentHandle(nodes);
        return { message : 'Done'};
     }

    const handleEditorChange = (nodes) => {
        setNodes(nodes);
        parentHandle(nodes);
        return { message:"Done" };
    }

    const handleChange = (value) => {
        if(!!!value)
        {
            
        }
        let main = { ...mainUnit }
        main.unit1 = value;
        main.id = 0;
        setMainUnit(main);
        handleMainUnit(main);

        let newUnits = [...innerUnits]
        let index = newUnits.findIndex((object) => object.value == 0 || object.value == main.id);

        if (index !== -1){
            newUnits[index] = {label : main.unit1 , value: main.id };
        }
        else
            newUnits.push({ label: value, value: 0 });

        setUnits(newUnits);
    }

    return (
        <>
            <div class="card-body" dir={dir}>
                <div class="form-group" style={{ marginBottom: "14px" }}>
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">{translations.Unit}</label>
                            <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={!!mainUnit ? mainUnit.unit1 : ''}
                                onChange={(e) => handleChange(e.target.value)}
                                ></input>
                        </div>
                    </div>
                </div>
                <div>
                <TreeTableEditorLocal
                translations={translations}
                dir={dir}
                header={false}
                addNewRow={true}
                type={"recipe"}
                title={translations.recipe}
                currentNodes={[...nodes]}
                defaultValue={{ }}
                rowTitle= "unit1"
                cols={[
                    {
                        key: "unit2", title: "Unit", autoFocus: true, options: innerUnits, type: "DropDown", width: '25%',
                        editable: true, required: true
                    },
                    {key : "transfer", autoFocus: false, type :"Decimal", width:'25%', 
                        editable:true, required:true
                    },
                    {
                    key: "unit1", autoFocus: false, type: "Text", width: '20%',
                    editable: true, required: true,
                    onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                            if(!!!nodes[rowKey].data.id){
                                nodes[rowKey].data.id = globalId - 1;
                                setGlobalId(globalId - 1);
                            }
                            let index = innerUnits.findIndex((object) => object.value == nodes[rowKey].data.id);
                            // Replace if the item exists
                            if (index !== -1)
                                innerUnits[index] = {label : nodes[rowKey].data.unit1 , value: nodes[rowKey].data.id };
                            else
                                innerUnits.push({ label: nodes[rowKey].data.unit1, value: nodes[rowKey].data.id });
                            setUnits(innerUnits);
                            postExecute(nodes);
                        }
                    },
                    {
                    key: "primiry", autoFocus: false, type: "Check", width: '20%',
                    editable: false, required: false
                    }
                ]}
                actions={[]}
                onUpdate={(nodes) => handleEditorChange(nodes)}
                onDelete={(row)=> handleDelete(row)} />
                </div>
            </div>
        </>

    );
};


export default UnitTransferIngredient;
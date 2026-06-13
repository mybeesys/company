import React, { useState } from "react";
import TreeTableEditorUnit from "../comp/TreeTableEditorUnit";

const UnitTransferModifier = ({
    translations,
    unitTransfer,
    unitTree,
    parentHandle,
    handleMainUnit,
    modifierUnit,
    dir,
}) => {
    const [nodes, setNodes] = useState(unitTransfer);
    const [globalId, setGlobalId] = useState(-2);
    const [innerUnits, setUnits] = useState(unitTree);
    const [mainUnit, setMainUnit] = useState(modifierUnit);

    React.useEffect(() => {
        setNodes(unitTransfer);
        setMainUnit(modifierUnit);
        if (!!!innerUnits || innerUnits.length == 0) setUnits(unitTree);
    }, [unitTransfer, modifierUnit, unitTree, innerUnits]);

    const handleDelete = (row) => {
        let index = nodes.findIndex((x) => x.id == row.id);
        if (index != -1) {
            if (nodes.findIndex((x) => x.unit2 == row.id) > 0) {
                return { message: "relatedUnitTransfer" };
            }
            nodes.splice(index, 1);
            let index1 = innerUnits.findIndex(
                (object) => object.value == row.id
            );
            innerUnits.splice(index1, 1);
        }
        setNodes([...nodes]);
        parentHandle(nodes);
        return { message: "Done" };
    };

    const handleEditorChange = (nodes) => {
        setNodes(nodes);
        parentHandle(nodes);
        return { message: "Done" };
    };

    const handleChange = (value) => {
        let main = { ...mainUnit };
        main.unit1 = value;
        main.id = 0;
        setMainUnit(main);
        handleMainUnit(main);

        let newUnits = [...innerUnits];
        let index = newUnits.findIndex(
            (object) => object.value == 0 || object.value == main.id
        );

        if (index !== -1) {
            newUnits[index] = { label: main.unit1, value: main.id };
        } else newUnits.push({ label: value, value: 0 });

        setUnits(newUnits);
    };

    return (
        <div className="unit-transfer-panel" dir={dir}>
            <div className="row g-3 mb-4">
                <div className="col-12 col-md-6 col-lg-5">
                    <label htmlFor="modifier_main_unit" className="form-label fw-semibold">
                        {translations.Unit}
                    </label>
                    <input
                        type="text"
                        className="form-control form-control-solid custom-height"
                        id="modifier_main_unit"
                        value={!!mainUnit ? mainUnit.unit1 : ""}
                        onChange={(e) => handleChange(e.target.value)}
                    />
                </div>
            </div>
            <div className="table-responsive unit-transfer-table">
                <TreeTableEditorUnit
                    translations={translations}
                    dir={dir}
                    header={false}
                    addNewRow={true}
                    type={"unit"}
                    title={translations.recipe}
                    currentNodes={[...nodes]}
                    defaultValue={{}}
                    rowTitle="unit1"
                    cols={[
                        {
                            key: "unit2",
                            title: "Unit",
                            autoFocus: true,
                            options: innerUnits,
                            type: "DropDown",
                            width: "25%",
                            editable: true,
                            required: true,
                        },
                        {
                            key: "transfer",
                            title: "transfer1",
                            autoFocus: false,
                            type: "Decimal",
                            width: "25%",
                            editable: true,
                            required: true,
                        },
                        {
                            key: "unit1",
                            title: "newUnit",
                            autoFocus: false,
                            type: "Text",
                            width: "20%",
                            editable: true,
                            required: true,
                            onChangeValue: (
                                nodes,
                                key,
                                val,
                                rowKey,
                                postExecute
                            ) => {
                                if (!!!nodes[rowKey].data.id) {
                                    nodes[rowKey].data.id = globalId - 1;
                                    setGlobalId(globalId - 1);
                                }
                                let index = innerUnits.findIndex(
                                    (object) =>
                                        object.value ==
                                        nodes[rowKey].data.id
                                );
                                if (index !== -1)
                                    innerUnits[index] = {
                                        label: nodes[rowKey].data.unit1,
                                        value: nodes[rowKey].data.id,
                                    };
                                else
                                    innerUnits.push({
                                        label: nodes[rowKey].data.unit1,
                                        value: nodes[rowKey].data.id,
                                    });
                                setUnits(innerUnits);
                                postExecute(nodes);
                            },
                        },
                        {
                            key: "primiry",
                            autoFocus: false,
                            type: "Check",
                            width: "20%",
                            editable: false,
                            required: false,
                        },
                    ]}
                    actions={[]}
                    onUpdate={(nodes) => handleEditorChange(nodes)}
                    onDelete={(row) => handleDelete(row)}
                />
            </div>
        </div>
    );
};

export default UnitTransferModifier;

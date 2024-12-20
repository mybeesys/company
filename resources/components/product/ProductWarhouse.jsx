import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";

const ProductWarhouse = ({ translations, dir, currentObject, onBasicChange }) => {


    const handleDelete = (row) =>{
        let index = currentObject.warhouses.findIndex(x=>x.id == row.id);
        currentObject.warhouses.splice(index, 1); // Removes 1 element at index 2
        onBasicChange("warhouses", currentObject.warhouses);
        return { message : 'Done'};
    }

    return (
        <TreeTableEditorLocal
            translations={translations}
            dir={dir}
            header={false}
            addNewRow={true}
            type={"warehouses"}
            title={translations.warhouses}
            currentNodes={[...currentObject.warhouses]}
            defaultValue={{ }}
            cols={[
                {
                    key: "warhouse", autoFocus: true, searchUrl: "searchWarhouse", type: "AsyncDropDown", width: '80%',
                    editable: true, required: true
                }
            ]}
            actions={[]}
            onUpdate={(nodes) => onBasicChange("warhouses", nodes)}
            onDelete={handleDelete} />
    )
}

export default ProductWarhouse;
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";

const ProductEstablishment = ({ translations, dir, currentObject, onBasicChange }) => {


    const handleDelete = (row) =>{
        let index = currentObject.establishments.findIndex(x=>x.id == row.id);
        currentObject.establishments.splice(index, 1); // Removes 1 element at index 2
        onBasicChange("establishment", currentObject.establishments);
        return { message : 'Done'};
    }

    return (
        <TreeTableEditorLocal
            translations={translations}
            dir={dir}
            header={false}
            addNewRow={true}
            type={"establishment"}
            title={translations.establishments}
            currentNodes={[...currentObject.establishments]}
            defaultValue={{ }}
            cols={[
                {
                    key: "establishment", autoFocus: true, searchUrl: "searchEstablishments", type: "AsyncDropDown", width: '80%',
                    editable: true, required: true
                }
            ]}
            actions={[]}
            onUpdate={(nodes) => onBasicChange("establishments", nodes)}
            onDelete={handleDelete} />
    )
}

export default ProductEstablishment;
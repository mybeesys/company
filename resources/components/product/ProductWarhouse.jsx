import TreeTableComponentLocal from "../comp/TreeTableComponentLocal";

const ProductWarhouse = ({ translations, dir, currentObject, onBasicChange }) => {


    return (
        <TreeTableComponentLocal
            translations={translations}
            dir={dir}
            header={true}
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
            onDelete={(nodes) => onBasicChange("warhouses", nodes)} />
    )
}

export default ProductWarhouse;
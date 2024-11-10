
import TreeTableComponentLocal from '../comp/TreeTableComponentLocal';


const LinkedComboGroup = ({dir, translations, currentObject, products, onComboChange}) => {
  
  return (
    <div>
       <TreeTableComponentLocal
        translations={translations}
        dir={dir}
        header={true}
        type= {"productCombo"}
        title={translations.groups}
        currentNodes={[...currentObject.combos]}
        defaultValue={{quantity : 1, combo_saving : 0}}
        cols={[
            {key : "name_en", autoFocus: true, options: [], type :"Text", width:'15%', editable:true, required:true},
            {key : "name_ar", autoFocus: false, options: [], type :"Text", width:'15%', editable:true, required:true},
            {key : "barcode", autoFocus: false, options: [], type :"Text", width:'12%', editable:true, required:false},
            {key : "products", autoFocus: false, options: products, type :"MultiDropDown", width:'25%', editable:true, required:true},
            {key : "order", autoFocus: false, options: [], type : "Number", width:'10%', editable:true, required:true},
            {key : "quantity", autoFocus: false, options: [], type :"Number", width:'11%', editable:true, required:true}
        ]}
        actions = {[]}
        onUpdate={(nodes)=> onComboChange("combos", nodes)}
        onDelete={null}/>
    </div>
  );
};

export default LinkedComboGroup;
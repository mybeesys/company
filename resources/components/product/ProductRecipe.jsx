import React, { useEffect } from 'react';
import TreeTableEditorLocal from '../comp/TreeTableEditorLocal';

const ProductRecipe = ({ translations, productRecipe, product, ingredientTree, onBasicChange, dir }) => {
  
  useEffect(() => {
  }, [product, ingredientTree]);

    const handleDelete = (row) =>{
      let index = productRecipe.findIndex(x=>x.id == row.id);
      productRecipe.splice(index, 1); // Removes 1 element at index 2
      onBasicChange("recipe", productRecipe);
      return { message : 'Done'};
   }

    return (
       <>
       <TreeTableEditorLocal
            translations={translations}
            dir={dir}
            header={false}
            addNewRow={true}
            type={"recipe"}
            title={translations.recipe}
            currentNodes={[...productRecipe]}
            defaultValue={{ }}
            cols={[
                {
                    key: "newid", title: "Ingredient", autoFocus: true, options: ingredientTree, type: "DropDown", width: '25%',
                    editable: true, required: true,
                    onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                      if (!!val && !!nodes[rowKey].data['quantity']) {
                        let cost = ingredientTree.find(e => e.value == val).cost;
                        nodes[rowKey].data['cost'] = nodes[rowKey].data['quantity'] * cost;
                      }
                      postExecute(nodes);
                    }
                },
                {key : "unit_transfer", autoFocus: true, type :"AsyncDropDown", width:'25%', editable:true,required:true,
                    searchUrl:"searchUnitTransfers",
                    relatedTo:{
                        key: "id",
                        relatedKey : "newid"
                    }
                },
                {
                  key: "quantity", autoFocus: false, type: "Decimal", width: '20%',
                  editable: true, required: true,
                  onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                    if (!!nodes[rowKey].data['newid'] && !!val) {
                      let cost = ingredientTree.find(e => e.value == nodes[rowKey].data['newid']).cost;
                      nodes[rowKey].data['cost'] = val * cost;
                    }
                    postExecute(nodes);
                  }
                },
                {
                  key: "cost", autoFocus: false, type: "Decimal", width: '20%',
                  editable: false, required: false
                }
            ]}
            actions={[]}
            onUpdate={(nodes) => onBasicChange('recipe', nodes)}
            onDelete={handleDelete} />
        <div class="row" style={{ paddingtop: '20px' }}>
          <div class="col-6">
            <label for="recipe_yield" class="col-form-label">{translations.recipe_yield}</label>
            <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="recipe_yield" value={!!product.recipe_yield ? product.recipe_yield : ''}
              onChange={(e) => onBasicChange('recipe_yield', e.target.value)}
              ></input>
          </div>
        </div>
          <div class="d-flex  align-items-center pt-3">
            <label class="fs-6 fw-semibold mb-2 me-3 "
              style={{ width: "150px" }}>{translations.prep_recipe}</label>
            <div class="form-check">
              <input type="checkbox" style={{ border: "1px solid #9f9f9f" }}
                class="form-check-input my-2"
                id="prep_recipe" checked={product.prep_recipe}
                onChange={(e) => onBasicChange('prep_recipe', e.target.checked ? 1 : 0)} />
            </div>
          </div>
    </>

  );
};


export default ProductRecipe;
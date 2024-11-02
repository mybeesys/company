import { Column } from "primereact/column";
import { TreeTable } from "primereact/treetable";
import { useEffect, useState } from "react";



const SelectTree = ({ translations, title, selectedItems, itemType, itemKey, itemsUrl, onItemSelectionChange }) => {

    const [items, setItems] = useState([]);
    const [selectedNodeKeys, setSelectedNodeKeys] = useState([]);
    
    const cleanItemsData = (items, cleanItems) =>{
        for (let index = 0; index < items.length; index++) {
            const item = items[index];
            if(!!item.data.empty)
                continue;
            let cleanChildren = [];
            if(!!item.children)
                cleanItemsData(item.children, cleanChildren);
            let cleanItem = item;
            cleanItem.children = cleanChildren;
            cleanItems.push(cleanItem);
        }
    }

    const checkSelected = (item, sKeys) => {
        let ss = {checked :false, partialChecked: false};
        if(item.data.type == itemType &&
            selectedItems.filter(x=> x[itemKey] == item.data.id).length >0){
                sKeys[item.key] = {checked :true, partialChecked: false};
        }
        else{
            
            if(!!item.children && item.children.length > 0){
                let checked = null;
                ss = {checked :false, partialChecked: false};
                for (let index1 = 0; index1 < item.children.length; index1++) {
                    const child = item.children[index1];
                    let result =  checkSelected(child, sKeys);
                    if(!!!checked)
                        checked = result.checked;
                    if(result.checked || result.partialChecked)
                        ss.partialChecked = true;
                    checked = checked && result.checked;    
                }
                ss[checked] = checked;
                sKeys[item.key] = {...ss};
            }
        }
        return !!sKeys[item.key] ? sKeys[item.key] : ss;
    }

    const setInitialSelectedNode = (Items) =>{
        let sKeys = [];
        for (let index = 0; index < Items.length; index++) {
            const item = Items[index];
            checkSelected(item, sKeys);
        }
        setSelectedNodeKeys({...sKeys});
    }

    useEffect(() => {
        axios.get(itemsUrl)
        .then(response => {
            let cleanItems = [];
            cleanItemsData(response.data, cleanItems);
            setItems(cleanItems);
            setInitialSelectedNode(response.data);
        })
        .catch(error => {
          console.error('Error fetching translations', error);
        });
    }, [itemsUrl]);

    const fillSelectedItems = (items, keys, selectedItemIds) => {
        for (let index = 0; index < items.length; index++) {
            const item = items[index];
            if(Object.keys(keys).filter(x => x == item.key).length > 0 && 
                item.data.type == itemType &&
                keys[item.key].checked)
                selectedItemIds.push(item.data.id);
            if(!!item.children)
                fillSelectedItems(item.children, keys, selectedItemIds);
        }
    }

    const onSelectedItemChange = (keys) =>{
        let selectedItems = [];
        fillSelectedItems(items, keys, selectedItems);
        setSelectedNodeKeys(keys);
        onItemSelectionChange(selectedItems);
        console.log(selectedItems);
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
                                    <h4>{title}</h4>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="btn__all">
                                </div>
                            </div>
                        </div>
                        <div class="container">
                            <div class="row border-bottom border-dark">
                            <TreeTable 
                                selectionMode="checkbox"
                                selectionKeys={selectedNodeKeys}
                                onSelectionChange={(e) => onSelectedItemChange(e.value)}
                                value={items} 
                                tableStyle={{ minWidth: '50rem' }} 
                                className={"custom-tree-table"}>
                                <Column  style={{ width: '20%' }} field="name_en" expander></Column>
                                <Column  style={{ width: '20%' }} field="name_ar" ></Column>
                            </TreeTable>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default SelectTree;
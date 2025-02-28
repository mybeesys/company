import React, { useState, useCallback, useEffect } from "react";
import ReactDOM from "react-dom/client";
import CategoryTree from "./product/CategoryTree";
import Modifiertree from "./modifier/modifiertree";
import Attributetree from "./attributes/attributetree";
import CustomMenuTable from "./custommenu/CustomMenuTable";
import CustomMenuDetail from "./custommenu/CustomMenuDetail";
import ServiceFeeTable from "./serviceFee/ServiceFeeTable";
import TypeOfServiceTable from "./typesOfService/TypeOfServiceTable";
import ServiceFeeDetail from "./serviceFee/ServiceFeeDetail";
import DiscountTable from "./discount/DiscountTable";
import IngredientDetail from "./ingredients/IngredientDetail";
import DiscountDetail from "./discount/DiscountDetail";
import LinkedComboTable from "./linkedCombo/LinkedComboTable";
import LinkedComboDetail from "./linkedCombo/LinkedComboDetail";
import ProductInventoryTable from "./Inventory/product/ProductInventoryTable";
import ProductInventoryDetail from "./Inventory/product/ProductInventoryDetail";
import PurchaseOrderDetail from "./Inventory/purchaseOrder/PurchaseOrderDetail";
import PurchaseOrderTable from "./Inventory/purchaseOrder/PurchaseOrderTable";
import PurchaseOrderReceive from "./Inventory/purchaseOrder/PurchaseOrderReceive";
import PrepTable from "./Inventory/prep/PrepTable";
import PrepDetail from "./Inventory/prep/PrepDetail";
import RmaDetail from "./Inventory/rma/RmaDetail";
import RmaTable from "./Inventory/rma/RmaTable";
import WasteTable from "./Inventory/waste/WasteTable";
import WasteDetail from "./Inventory/waste/WasteDetail";
import TransferTable from "./Inventory/transfer/TransferTable";
import TransferDetail from "./Inventory/transfer/TransferDetail";
import WarehouseTree from "./Inventory/warehouse/WarehouseTree";
import ProductComponent1 from "./product/ProductComponent1";
import PriceTierTree from "./priceTier/warehouse/PriceTierTree";
import ModifierComponent from "./modifier/ModifierComponent";
import DataImport from "./comp/DataImport";
import AreaTable from "./reservation/AreaTable";
import AreaQRTable from "./reservation/AreaQRTable";
import Menu from "./reservation/Menu";
import MenuSimple from "./reservation/MenuSimple";
import MenuQR from "./reservation/MenuQR";
import TableAreaTable from "./reservation/TableAreaTable";
import Ingredient from "./ingredients/ingredient";
import ProductBarcode from "./reports/ProductBarcode";

const App = ({ nodeType, dir }) => {
    const [translations, setTranslations] = useState({});
    const [loading, setLoading] = useState(true);

    const nodeElement = {
        category: <CategoryTree translations={translations} dir={dir} />,
        product: <ProductComponent1 translations={translations} dir={dir} />,
        modifier: <Modifiertree translations={translations} dir={dir} />,
        modifierdetail: (
            <ModifierComponent translations={translations} dir={dir} />
        ),
        attribute: <Attributetree translations={translations} dir={dir} />,
        custommenu: <CustomMenuTable translations={translations} dir={dir} />,
        custommenuedit: (
            <CustomMenuDetail translations={translations} dir={dir} />
        ),
        serviceFee: <ServiceFeeTable translations={translations} dir={dir} />,
        typeService: (
            <TypeOfServiceTable translations={translations} dir={dir} />
        ),

        servicefeeedit: (
            <ServiceFeeDetail translations={translations} dir={dir} />
        ),
        ingredient: <Ingredient translations={translations} dir={dir} />,
        ingredientedit: (
            <IngredientDetail translations={translations} dir={dir} />
        ),
        discount: <DiscountTable translations={translations} dir={dir} />,
        discountedit: <DiscountDetail translations={translations} dir={dir} />,
        linkedCombo: <LinkedComboTable translations={translations} dir={dir} />,
        linkedComboedit: (
            <LinkedComboDetail translations={translations} dir={dir} />
        ),
        productinventory: (
            <ProductInventoryTable
                translations={translations}
                dir={dir}
                p_type={"product"}
            />
        ),
        productinventoryedit: (
            <ProductInventoryDetail
                translations={translations}
                dir={dir}
                p_type={"product"}
            />
        ),
        purchaseOrder: (
            <PurchaseOrderTable translations={translations} dir={dir} />
        ),
        purchaseorderedit: (
            <PurchaseOrderDetail translations={translations} dir={dir} />
        ),
        purchaseorderrecieve: (
            <PurchaseOrderReceive translations={translations} dir={dir} />
        ),
        prep: <PrepTable translations={translations} dir={dir} />,
        prepedit: <PrepDetail translations={translations} dir={dir} />,
        rma: <RmaTable translations={translations} dir={dir} />,
        rmaedit: <RmaDetail translations={translations} dir={dir} />,
        ingredientinventory: (
            <ProductInventoryTable
                translations={translations}
                dir={dir}
                p_type={"ingredient"}
            />
        ),
        ingredientinventoryedit: (
            <ProductInventoryDetail
                translations={translations}
                dir={dir}
                p_type={"ingredient"}
            />
        ),
        waste: <WasteTable translations={translations} dir={dir} />,
        wasteedit: <WasteDetail translations={translations} dir={dir} />,
        transfer: <TransferTable translations={translations} dir={dir} />,
        transferedit: <TransferDetail translations={translations} dir={dir} />,
        warehouse: <WarehouseTree translations={translations} dir={dir} />,
        priceTier: <PriceTierTree translations={translations} dir={dir} />,
        importProduct: <DataImport translations={translations} dir={dir} />,
        openInventoryImport: (
            <DataImport translations={translations} dir={dir} />
        ),
        area: <AreaTable translations={translations} dir={dir} />,
        table: <TableAreaTable translations={translations} dir={dir} />,
        areaQR: <AreaQRTable translations={translations} dir={dir} />,
        menu: <Menu translations={translations} dir={dir} />,
        menuSimple: <MenuSimple translations={translations} dir={dir} />,
        menuQR: <MenuQR translations={translations} dir={dir} />,
        productBarcode: (
            <ProductBarcode translations={translations} dir={dir} />
        ),
    };

    useEffect(() => {
        const loadTranslations = async () => {
            let transaltion = {};
            if (dir == "ltr") {
                await import("./style.scss");
                transaltion = await import("./lang/en.json");
            } else {
                await import("./style.rtl.scss");
                transaltion = await import("./lang/ar.json");
            }
            setTranslations(transaltion);
            setLoading(false);
        };
        loadTranslations();
    }, []);

    if (loading) {
        return <div></div>;
    }

    let comp = nodeElement[nodeType];

    return comp;
};

var htmlElement = document.querySelector("html");
const dir = htmlElement.getAttribute("dir");
const element = document.getElementById("root");
const root = ReactDOM.createRoot(element);
root.render(<App dir={dir} nodeType={element.getAttribute("type")} />);

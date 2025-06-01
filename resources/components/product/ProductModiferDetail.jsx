import React, { useEffect, useState } from "react";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const animatedComponents = makeAnimated();

const ProductModiferDetail = ({
    translations,
    modifierId,
    title,
    productModifiers,
    onchange,
    onSelectAll,
    productId,
}) => {
    const [modifierClass, setModifierClass] = useState({
        min_modifiers: productModifiers.class.min_modifiers || 0,
        max_modifiers: productModifiers.class.max_modifiers || 0,
        free_quantity: productModifiers.class.free_quantity || 0,
        free_type: productModifiers.class.free_type || 0,
    });

    const [modifiers, setModifiers] = useState(
        productModifiers.modifiers
            ?.filter((mod) => mod && mod.id)
            .map((mod) => ({
                ...mod,
                checked: mod.active || 0,
                display_order: mod.display_order || 0,
                default: mod.default || 0,
            })) || []
    );

    const handleSelectAll = () => {
        const updatedModifiers = modifiers.map((mod) => ({
            ...mod,
            checked: 1,
        }));
        setModifiers(updatedModifiers);
        sendUpdatesToParent(modifierId, updatedModifiers);
    };
    const handleDeselectAll = () => {
        const updatedModifiers = modifiers.map((mod) => ({
            ...mod,
            checked: 0,
            default: 0,
        }));
        setModifiers(updatedModifiers);
        sendUpdatesToParent(modifierId, updatedModifiers);
    };
    const handleChange = (key, value) => {
        const updatedClass = { ...modifierClass, [key]: value };
        setModifierClass(updatedClass);
        onchange(
            { ...modifierId, product_id: productId, ...updatedClass },
            key,
            value
        );
    };

    const handleCheck = (index, type) => {
        const updatedModifiers = [...modifiers];

        if (type === "checked") {
            updatedModifiers[index].checked = updatedModifiers[index].checked
                ? 0
                : 1;
            //updatedModifiers[index].active = updatedModifiers[index].checked;
        } else if (type === "default") {
            updatedModifiers[index].default = updatedModifiers[index].default
                ? 0
                : 1;
        }

        setModifiers(updatedModifiers);
        sendUpdatesToParent(modifierId, updatedModifiers);
    };

    const handleOrderChange = (index, newValue) => {
        const updatedModifiers = [...modifiers];
        updatedModifiers[index].display_order = newValue;
        setModifiers(updatedModifiers);
        sendUpdatesToParent(modifierId, updatedModifiers);
    };
    const sendUpdatesToParent = (modifierId, updatedModifiers) => {
        const activeModifiers = updatedModifiers
            .filter((m) => m && m.id && m.checked)
            .map((m) => ({
                ...m,
                product_id: productId,
                modifier_class_id: modifierId.modifier_class_id,
                modifier_id: m.modifier_id || m.id,
                //active: m.checked ? 1 : 0,
            }));

        onchange(
            {
                modifier_class_id: modifierId.modifier_class_id,
                ...modifierId,
                product_id: productId,
                ...modifierClass,
            },
            "modifiers",
            activeModifiers
        );
    };

    return (
        <section className="product spad">
            <div className="container mt-5">
                <div className="row">
                    <div className="col-lg-12">
                        <div className="trending__product">
                            <div className="row border-bottom">
                                <div className="col-lg-8 col-md-8 col-sm-12">
                                    <div className="section-title">
                                        <h4>{title}</h4>
                                    </div>
                                </div>
                            </div>
                            <div className="container">
                                <div className="row border-bottom border-dark">
                                    <form onSubmit={(e) => e.preventDefault()}>
                                        <div className="form-group">
                                            <div className="row">
                                                <div className="col-12 col-md-6">
                                                    <div className="d-flex align-items-center">
                                                        <label
                                                            htmlFor="free_quantity"
                                                            className="col-form-label"
                                                        >
                                                            {
                                                                translations.free_quantity
                                                            }
                                                        </label>
                                                        <input
                                                            type="number"
                                                            id="free_quantity"
                                                            min="0"
                                                            className="form-control form-control-solid custom-height"
                                                            value={
                                                                modifierClass.free_quantity
                                                            }
                                                            onChange={(e) =>
                                                                handleChange(
                                                                    "free_quantity",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="col-12 col-md-6">
                                                    <div className="d-flex align-items-center">
                                                        <label
                                                            htmlFor="free_type"
                                                            className="col-form-label mr-4"
                                                        >
                                                            {translations.free_type ||
                                                                "Free Type"}
                                                        </label>
                                                        <select
                                                            id="free_type"
                                                            className="form-control form-control-solid custom-height"
                                                            value={
                                                                modifierClass.free_type
                                                            }
                                                            style={{
                                                                height: "43px",
                                                            }}
                                                            onChange={(e) =>
                                                                handleChange(
                                                                    "free_type",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                        >
                                                            <option value="0">
                                                                {translations.price_type ||
                                                                    "Price Type"}
                                                            </option>
                                                            <option value="1">
                                                                {translations.quantity_type ||
                                                                    "Quantity Type"}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="col-6">
                                                    <label
                                                        htmlFor="min_modifiers"
                                                        className="col-form-label"
                                                    >
                                                        {translations.min}
                                                    </label>
                                                    <input
                                                        type="number"
                                                        id="min_modifiers"
                                                        min="0"
                                                        className="form-control form-control-solid custom-height"
                                                        value={
                                                            modifierClass.min_modifiers
                                                        }
                                                        onChange={(e) =>
                                                            handleChange(
                                                                "min_modifiers",
                                                                e.target.value
                                                            )
                                                        }
                                                        required
                                                    />
                                                </div>
                                                <div className="col-6">
                                                    <label
                                                        htmlFor="max_modifiers"
                                                        className="col-form-label"
                                                    >
                                                        {translations.max}
                                                    </label>
                                                    <input
                                                        type="number"
                                                        id="max_modifiers"
                                                        min="0"
                                                        className="form-control form-control-solid custom-height"
                                                        value={
                                                            modifierClass.max_modifiers
                                                        }
                                                        onChange={(e) =>
                                                            handleChange(
                                                                "max_modifiers",
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                            <div className="row mt-6">
                                                <div className="col-12 text-end">
                                                    {" "}
                                                    <div className="d-inline-block me-3">
                                                        <a
                                                            href="#"
                                                            className="text-decoration-none text-primary"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleSelectAll();
                                                            }}
                                                            style={{
                                                                cursor: "pointer",
                                                            }}
                                                        >
                                                            <i className="fas fa-check-circle me-1"></i>
                                                            {
                                                                translations.select_all
                                                            }
                                                        </a>
                                                    </div>
                                                    <div className="d-inline-block">
                                                        <a
                                                            href="#"
                                                            className="text-decoration-none text-danger"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleDeselectAll();
                                                            }}
                                                            style={{
                                                                cursor: "pointer",
                                                            }}
                                                        >
                                                            <i className="fas fa-times-circle me-1"></i>
                                                            {
                                                                translations.deselect_all
                                                            }
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            {modifiers.length > 0 ? (
                                                <table className="table table-responsive">
                                                    <thead>
                                                        <tr>
                                                            <th>
                                                                {
                                                                    translations.available_add_ons
                                                                }
                                                            </th>
                                                            <th>
                                                                {
                                                                    translations.active
                                                                }
                                                            </th>
                                                            <th>
                                                                {
                                                                    translations.by_default
                                                                }
                                                            </th>
                                                            <th>
                                                                {
                                                                    translations.arranging
                                                                }
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {modifiers.map(
                                                            (item, index) => (
                                                                <tr
                                                                    key={index}
                                                                    className={
                                                                        index %
                                                                            2 ===
                                                                        0
                                                                            ? "table-light"
                                                                            : "table-white"
                                                                    }
                                                                >
                                                                    <td>
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </td>
                                                                    <td>
                                                                        <input
                                                                            type="checkbox"
                                                                            checked={
                                                                                item.checked ===
                                                                                1
                                                                            }
                                                                            onChange={() =>
                                                                                handleCheck(
                                                                                    index,
                                                                                    "checked"
                                                                                )
                                                                            }
                                                                            style={{
                                                                                width: "15px",
                                                                                height: "15px",
                                                                                transform:
                                                                                    "scale(1.5)",
                                                                                margin: "5px",
                                                                            }}
                                                                        />
                                                                    </td>
                                                                    <td>
                                                                        <input
                                                                            type="checkbox"
                                                                            checked={
                                                                                item.default ===
                                                                                1
                                                                            }
                                                                            onChange={() =>
                                                                                handleCheck(
                                                                                    index,
                                                                                    "default"
                                                                                )
                                                                            }
                                                                            style={{
                                                                                width: "15px",
                                                                                height: "15px",
                                                                                transform:
                                                                                    "scale(1.5)",
                                                                                margin: "5px",
                                                                            }}
                                                                        />
                                                                    </td>
                                                                    <td>
                                                                        <input
                                                                            type="number"
                                                                            value={
                                                                                item.display_order
                                                                            }
                                                                            style={{
                                                                                width: "60px",
                                                                                height: "30px",
                                                                            }}
                                                                            onChange={(
                                                                                e
                                                                            ) =>
                                                                                handleOrderChange(
                                                                                    index,
                                                                                    e
                                                                                        .target
                                                                                        .value
                                                                                )
                                                                            }
                                                                        />
                                                                    </td>
                                                                </tr>
                                                            )
                                                        )}
                                                    </tbody>
                                                </table>
                                            ) : (
                                                <p
                                                    style={{
                                                        color: "orange",
                                                        fontWeight: "bold",
                                                    }}
                                                >
                                                    {translations.no_modifiers_available ||
                                                        "No modifiers available"}
                                                </p>
                                            )}
                                        </div>
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

export default ProductModiferDetail;

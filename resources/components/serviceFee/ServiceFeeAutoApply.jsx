import { useEffect, useState } from "react";
import { Calendar } from "primereact/calendar";
import Select from "react-select";
import makeAnimated from "react-select/animated";

const ServiceFeeAutoApply = ({
    translations,
    currentObject,
    serviceFeeCards,
    serviceFeediningTypes,
    onBasicChange,
    autoApplyTypes,
    diningTypes,
    creditCardTypes,
    paymentCards,
}) => {
    useEffect(() => {
        console.log(autoApplyTypes, diningTypes, creditCardTypes, paymentCards);
    }, []);

    const toDate = (dateTimeString, type) => {
        if (!!!dateTimeString) return null;
        if (type == "D") return new Date(dateTimeString);
        else return new Date(`01/01/2024 ${dateTimeString}`);
    };

    return (
        <section class="product spad">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="trending__product">
                            <div class="container">
                                <div class="row">
                                    <div class="col-6">
                                        <label
                                            for="name_ar"
                                            class="col-form-label"
                                        >
                                            {translations.auto_apply_type}
                                        </label>
                                        <select
                                            class="form-control form-control-solid selectpicker"
                                            value={
                                                currentObject.auto_apply_type
                                            }
                                            onChange={(e) =>
                                                onBasicChange(
                                                    "auto_apply_type",
                                                    e.target.value
                                                )
                                            }
                                        >
                                            <option
                                                value="-1"
                                                disabled
                                                selected={
                                                    !!!currentObject.auto_apply_type
                                                }
                                            ></option>
                                            {autoApplyTypes.map(
                                                (autoApplyType) => (
                                                    <option
                                                        key={
                                                            autoApplyType.value
                                                        }
                                                        value={
                                                            autoApplyType.value
                                                        }
                                                    >
                                                        {autoApplyType.name}
                                                    </option>
                                                )
                                            )}
                                        </select>
                                    </div>
                                </div>
                                <div class="row ">
                                    {currentObject.auto_apply_type == 0 ? (
                                        <div class="col-12">
                                            <label
                                                for="diningTypes"
                                                class="col-form-label"
                                            >
                                                {translations.diningOptions}
                                            </label>
                                            <Select
                                                id="diningTypes"
                                                isMulti={true}
                                                options={diningTypes}
                                                closeMenuOnSelect={false}
                                                defaultValue={
                                                    serviceFeediningTypes
                                                }
                                                onChange={(val) =>
                                                    onBasicChange(
                                                        "diningTypes",
                                                        val.map((x) => {
                                                            return {
                                                                dining_type_id:
                                                                    x.value,
                                                            };
                                                        })
                                                    )
                                                }
                                            />
                                        </div>
                                    ) : currentObject.auto_apply_type == 3 ? (
                                        <>
                                            <div class="row container pt-4">
                                                <div class="col-6">
                                                    <label
                                                        for="from_date"
                                                        class="col-form-label"
                                                    >
                                                        {translations.fromDate}
                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <label
                                                        for="to_date"
                                                        class="col-form-label"
                                                    >
                                                        {translations.toDate}
                                                    </label>
                                                </div>
                                                <div className="col-6">
                                                    <Calendar
                                                        showTime
                                                        maxDate={
                                                            currentObject.toDate
                                                        }
                                                        value={toDate(
                                                            currentObject.from_date,
                                                            "D"
                                                        )}
                                                        onChange={(e) =>
                                                            onBasicChange(
                                                                "from_date",
                                                                !!e.value
                                                                    ? e.value.toLocaleString(
                                                                          "sv-SE"
                                                                      )
                                                                    : null
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="col-6">
                                                    <Calendar
                                                        showTime
                                                        value={toDate(
                                                            currentObject.to_date,
                                                            "D"
                                                        )}
                                                        onChange={(e) =>
                                                            onBasicChange(
                                                                "to_date",
                                                                !!e.value
                                                                    ? e.value.toLocaleString(
                                                                          "sv-SE"
                                                                      )
                                                                    : null
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </>
                                    ) : currentObject.auto_apply_type == 2 ? (
                                        <div class="row">
                                            <div class="col-12">
                                                <label
                                                    for="credit_type"
                                                    class="col-form-label"
                                                >
                                                    {translations.creditType}
                                                </label>
                                                <select
                                                    class="form-control form-control-solid selectpicker"
                                                    value={
                                                        currentObject.credit_type
                                                    }
                                                    onChange={(e) =>
                                                        onBasicChange(
                                                            "credit_type",
                                                            e.target.value
                                                        )
                                                    }
                                                >
                                                    {creditCardTypes.map(
                                                        (creditCardType) => (
                                                            <option
                                                                key={
                                                                    creditCardType.value
                                                                }
                                                                value={
                                                                    creditCardType.value
                                                                }
                                                            >
                                                                {
                                                                    creditCardType.name
                                                                }
                                                            </option>
                                                        )
                                                    )}
                                                </select>
                                            </div>
                                        </div>
                                    ) : currentObject.auto_apply_type == 1 ? (
                                        <div class="col-6">
                                            <label
                                                for="guestCount"
                                                class="col-form-label"
                                            >
                                                {translations.guestCountValue}
                                            </label>
                                            <input
                                                type="number"
                                                class="form-control form-control-solid custom-height"
                                                id="guestCount"
                                                value={currentObject.guestCount}
                                                onChange={(e) =>
                                                    onBasicChange(
                                                        "guestCount",
                                                        e.target.value
                                                    )
                                                }
                                                required
                                            ></input>
                                        </div>
                                    ) : (
                                        <></>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};
export default ServiceFeeAutoApply;

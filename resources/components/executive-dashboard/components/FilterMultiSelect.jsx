import React from 'react';
import Select from 'react-select';
import { useAppTheme } from '../hooks/useAppTheme';

function selectStyles(theme) {
    const dark = theme === 'dark';
    const ink = dark ? '#e8eef8' : '#1e2129';
    const muted = dark ? '#9aa4b8' : '#7e8299';
    const line = dark ? '#2a3447' : '#eceff5';
    const input = dark ? '#111622' : '#f8f9fc';
    const card = dark ? '#182030' : '#ffffff';
    const brand = '#e9b71f';
    const brandSoft = dark ? 'rgba(233, 183, 31, 0.18)' : '#fdf6e3';

    return {
        control: (base, state) => ({
            ...base,
            minHeight: 38,
            borderRadius: 10,
            borderColor: state.isFocused ? brand : line,
            backgroundColor: input,
            boxShadow: 'none',
            ':hover': { borderColor: brand },
        }),
        valueContainer: (base) => ({
            ...base,
            padding: '2px 8px',
            gap: 4,
        }),
        placeholder: (base) => ({ ...base, color: muted, fontSize: 13 }),
        input: (base) => ({ ...base, color: ink, margin: 0 }),
        singleValue: (base) => ({ ...base, color: ink }),
        multiValue: (base) => ({
            ...base,
            backgroundColor: brandSoft,
            borderRadius: 999,
            margin: 0,
        }),
        multiValueLabel: (base) => ({
            ...base,
            color: ink,
            fontSize: 12,
            fontWeight: 700,
            paddingInline: 8,
        }),
        multiValueRemove: (base) => ({
            ...base,
            color: muted,
            borderRadius: 999,
            ':hover': { backgroundColor: brand, color: '#1e2129' },
        }),
        indicatorsContainer: (base) => ({ ...base, paddingInlineEnd: 4 }),
        dropdownIndicator: (base) => ({ ...base, color: muted, padding: 6 }),
        clearIndicator: (base) => ({ ...base, color: muted, padding: 4 }),
        indicatorSeparator: () => ({ display: 'none' }),
        menu: (base) => ({
            ...base,
            backgroundColor: card,
            border: `1px solid ${line}`,
            borderRadius: 12,
            overflow: 'hidden',
            boxShadow: '0 12px 32px rgba(16, 18, 24, 0.16)',
            zIndex: 40,
        }),
        menuPortal: (base) => ({ ...base, zIndex: 1080 }),
        menuList: (base) => ({ ...base, padding: 6 }),
        option: (base, state) => ({
            ...base,
            borderRadius: 8,
            fontSize: 13,
            fontWeight: state.isSelected ? 700 : 500,
            color: ink,
            backgroundColor: state.isSelected
                ? brandSoft
                : state.isFocused
                    ? (dark ? '#111622' : '#f8f9fc')
                    : 'transparent',
            ':active': { backgroundColor: brandSoft },
        }),
        noOptionsMessage: (base) => ({ ...base, color: muted, fontSize: 13 }),
    };
}

export default function FilterMultiSelect({
    inputId,
    options,
    value,
    onChange,
    placeholder,
    isRtl,
    noOptions,
}) {
    const theme = useAppTheme();
    const selected = options.filter((opt) => value.includes(String(opt.value)));

    return (
        <Select
            inputId={inputId}
            classNamePrefix="ed-rs"
            isMulti
            isClearable
            isSearchable
            closeMenuOnSelect={false}
            hideSelectedOptions={false}
            isRtl={isRtl}
            options={options}
            value={selected}
            onChange={(next) => onChange((next || []).map((opt) => String(opt.value)))}
            placeholder={placeholder}
            noOptionsMessage={() => noOptions}
            styles={selectStyles(theme)}
            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
            menuPosition="fixed"
        />
    );
}

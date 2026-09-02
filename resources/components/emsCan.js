/**
 * EMS flags from Blade `ems-can`. Missing attribute stays permissive
 * so inventory/reservation trees that share these components keep working.
 *
 * Supports flat `{create,update,delete,print}` and nested
 * `{product:{create,...}, category:{...}}`.
 */
export function emsCan(action, entity = null) {
    const root = document.getElementById("root");
    if (!root || !root.hasAttribute("ems-can")) {
        return true;
    }

    let flags;
    try {
        const raw = root.getAttribute("ems-can");
        flags = raw ? JSON.parse(raw) : {};
    } catch (e) {
        return false;
    }

    if (!flags || typeof flags !== "object") {
        return false;
    }

    if (entity && flags[entity] && typeof flags[entity] === "object") {
        if (Object.prototype.hasOwnProperty.call(flags[entity], action)) {
            return !!flags[entity][action];
        }
    }

    if (
        Object.prototype.hasOwnProperty.call(flags, action) &&
        typeof flags[action] !== "object"
    ) {
        return !!flags[action];
    }

    return false;
}

export function emsCanEditOrSave(isEditingThisRow, entity = null) {
    if (isEditingThisRow) {
        return true;
    }

    return emsCan("update", entity);
}

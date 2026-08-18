/**
 * Rebuild province/state options for a native select element.
 *
 * @param {string|HTMLSelectElement|null} selectId
 * @param {Record<string, string>|null|undefined} options
 * @param {string|number|null|undefined} selectedId
 * @returns {string}
 */
export function rebuildProvinceSelectOptions(selectId, options, selectedId) {
    const select = typeof selectId === 'string' ? document.getElementById(selectId) : selectId;

    if (! select) {
        return String(selectedId ?? '');
    }

    const selected = String(selectedId ?? '');
    const normalized = options ?? {};

    while (select.options.length > 1) {
        select.remove(1);
    }

    Object.entries(normalized).forEach(([id, name]) => {
        const option = document.createElement('option');
        option.value = String(id);
        option.textContent = name;
        select.appendChild(option);
    });

    const next = Object.prototype.hasOwnProperty.call(normalized, selected) ? selected : '';
    select.value = next;

    return next;
}

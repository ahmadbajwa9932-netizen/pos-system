function toggleValue(icon) {
    // Try to find `.secure-value` in metric card (top cards)
    let valueEl = icon.closest('.metric-card')?.querySelector('.secure-value');

    // If not found, we are in comparison details table → get sibling span
    if (!valueEl) {
        valueEl = icon.parentElement.querySelector('.secure-value');
    }

    if (!valueEl) return;

    const isHidden = valueEl.textContent === '****';
    valueEl.textContent = isHidden ? valueEl.dataset.value : '****';

    icon.classList.toggle('visibility-slash-active', isHidden);
}

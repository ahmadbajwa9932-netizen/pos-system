function toggleCardVisibility(card) {
    const icon = card.querySelector('.toggle-visibility');
    const values = card.querySelectorAll('.secure-value');

    const isHidden = values[0].textContent === "****";

    values.forEach(v => {
        v.textContent = isHidden ? v.dataset.value : "****";
    });

    icon.classList.toggle("hidden");
}
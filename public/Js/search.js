document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("singleInputSearch");
    const table = document.getElementById("singleSearchTable");
    const rows = table.getElementsByTagName("tr");

    // 🔹 Fuzzy match helper (optional but keeps flexible search)
    function fuzzyMatch(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) {
                tIndex++;
            }
        }
        return tIndex === token.length;
    }

    // 🔸 Run search when user types
    searchInput.addEventListener("keyup", function () {
        const filter = this.value.toLowerCase().trim();
        const tokens = filter.split(/\s+/);

        for (let i = 1; i < rows.length; i++) { // skip header
            const cells = rows[i].getElementsByTagName("td");
            if (!cells.length) continue;

            // Column 1 → Category Name (as per your Blade table)
            const rowName = cells[1].innerText.toLowerCase();

            // ✅ fuzzy or direct match
            const match = tokens.every(token =>
                rowName.includes(token) || fuzzyMatch(rowName, token)
            );

            rows[i].style.display = match ? "" : "none";
        }
    });
});
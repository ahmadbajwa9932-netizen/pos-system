// PDF Popup open/close logic
document.addEventListener("DOMContentLoaded", function () {
    const pdfPopup = document.getElementById("pdfPopup");
    const openPdfBtn = document.getElementById("openPdfPopup");
    const closePdfBtn = document.getElementById("closePdfPopup");

    openPdfBtn.addEventListener("click", function () {
        pdfPopup.style.display = "flex";
    });

    closePdfBtn.addEventListener("click", function () {
        pdfPopup.style.display = "none";
    });

    // Close popup when clicking outside
    window.addEventListener("click", function (e) {
        if (e.target === pdfPopup) pdfPopup.style.display = "none";
    });
});
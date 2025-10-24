// Print Popup open/close logic
document.addEventListener("DOMContentLoaded", function () {
    const printPopup = document.getElementById("printPopup");
    const openPrintBtn = document.getElementById("openPrintPopup");
    const closePrintBtn = document.getElementById("closePrintPopup");

    openPrintBtn.addEventListener("click", function () {
        printPopup.style.display = "flex";
    });

    closePrintBtn.addEventListener("click", function () {
        printPopup.style.display = "none";
    });

    // Close print popup when clicking outside
    window.addEventListener("click", function (e) {
        if (e.target === printPopup) printPopup.style.display = "none";
    });
});
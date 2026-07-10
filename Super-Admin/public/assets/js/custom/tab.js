function showTab(tabId) {
    // Activate selected tab
    document
        .querySelectorAll(".tab-item")
        .forEach((tab) => tab.classList.remove("active"));
    document
        .querySelectorAll(".tab-content")
        .forEach((content) => content.classList.remove("active"));

    document.getElementById(tabId).classList.add("active");
    document
        .querySelector(`[onclick="showTab('${tabId}')"]`)
        .classList.add("active");

    // Get the base URL from the hidden input fields
    const csvBaseUrl = document.getElementById("csvBaseUrl").value;
    const excelBaseUrl = document.getElementById("excelBaseUrl").value;
    const pdfBaseUrl = document.getElementById("pdfBaseUrl").value;

    // Set correct export type
    let type = tabId == "sales" ? "sales" : "purchases";

    // Update export URLs dynamically
    const csv = document.getElementById("csvExportLink");
    const excel = document.getElementById("excelExportLink");
    const pdf = document.getElementById("pdfExportLink");

    if (csv) {
        csv.href = `${csvBaseUrl}?type=${type}`;
    }
    if (excel) {
        excel.href = `${excelBaseUrl}?type=${type}`;
    }
    if (pdf) {
        pdf.href = `${pdfBaseUrl}?type=${type}`;
    }
}

$(document).ready(function () {
    showTab("sales");
});

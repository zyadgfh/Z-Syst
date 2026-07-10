document.addEventListener("DOMContentLoaded", () => {
    const alertBox = document.getElementById("alertBox");
    const closeBtn = document.querySelector(".alert-close-btn");

    closeBtn.addEventListener("click", () => {
        alertBox.style.display = "none";
    });
});

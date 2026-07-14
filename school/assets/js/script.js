// ======================================================
// UniAdmin Professional Dashboard JavaScript
// ======================================================

document.addEventListener("DOMContentLoaded", function () {

    // ============================================
    // Sidebar Toggle
    // ============================================

    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const overlay = document.getElementById("sidebarOverlay");

    if (sidebarToggle) {

        sidebarToggle.addEventListener("click", function () {

            sidebar.classList.toggle("show");

            if (overlay) {
                overlay.classList.toggle("show");
            }

        });

    }

    if (overlay) {

        overlay.addEventListener("click", function () {

            sidebar.classList.remove("show");
            overlay.classList.remove("show");

        });

    }

    // ============================================
    // User Dropdown
    // ============================================

    const userBtn = document.getElementById("topbarUserBtn");
    const dropdown = document.getElementById("topbarUserDropdown");

    if (userBtn && dropdown) {

        userBtn.addEventListener("click", function (e) {

            e.stopPropagation();

            dropdown.classList.toggle("show");

        });

        document.addEventListener("click", function () {

            dropdown.classList.remove("show");

        });

    }

    // ============================================
    // Confirm Delete / Logout
    // ============================================

    document.querySelectorAll("[data-confirm]").forEach(function (button) {

        button.addEventListener("click", function (e) {

            const message = button.getAttribute("data-confirm");

            if (!confirm(message)) {

                e.preventDefault();

            }

        });

    });

    // ============================================
    // Animated Dashboard Counters
    // ============================================

    document.querySelectorAll("[data-counter]").forEach(function (counter) {

        let target = parseInt(counter.dataset.counter);

        let current = 0;

        let increment = Math.ceil(target / 70);

        let timer = setInterval(function () {

            current += increment;

            if (current >= target) {

                counter.innerText = target;

                clearInterval(timer);

            } else {

                counter.innerText = current;

            }

        }, 20);

    });

    // ============================================
    // Auto-hide Alerts
    // ============================================

    document.querySelectorAll(".alert").forEach(function (alert) {

        setTimeout(function () {

            alert.style.opacity = "0";

            setTimeout(function () {

                alert.remove();

            }, 500);

        }, 4000);

    });

    // ============================================
    // Ripple Effect
    // ============================================

    document.querySelectorAll(".btn").forEach(function(btn){

        btn.addEventListener("click",function(e){

            let circle=document.createElement("span");

            circle.className="ripple";

            let rect=btn.getBoundingClientRect();

            circle.style.left=e.clientX-rect.left+"px";

            circle.style.top=e.clientY-rect.top+"px";

            btn.appendChild(circle);

            setTimeout(()=>{

                circle.remove();

            },600);

        });

    });

});
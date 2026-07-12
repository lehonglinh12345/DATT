// =============================
// NAV DROPDOWN MOBILE TOGGLE
// =============================

document.addEventListener("DOMContentLoaded", function () {

    const dropdownItems = document.querySelectorAll(".nav-dropdown > a");

    dropdownItems.forEach(link => {
        link.addEventListener("click", function (e) {

            // chỉ chạy trên mobile
            if (window.innerWidth <= 768) {
                e.preventDefault();

                const parent = this.parentElement;

                // toggle dropdown
                parent.classList.toggle("active");

                // đóng các dropdown khác (optional đẹp hơn)
                document.querySelectorAll(".nav-dropdown").forEach(item => {
                    if (item !== parent) {
                        item.classList.remove("active");
                    }
                });
            }

        });
    });

});
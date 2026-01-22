var backToTopButton = document.getElementById("backToTop");

window.addEventListener('scroll', function () {
    if (backToTopButton) {
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            backToTopButton.style.display = "block";
        } else {
            backToTopButton.style.display = "none";
        }
    }
});

if (backToTopButton) {
    backToTopButton.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

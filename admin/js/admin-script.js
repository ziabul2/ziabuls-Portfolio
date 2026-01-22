// Back to Top Button Functionality
document.addEventListener('DOMContentLoaded', function() {
    var backToTopButton = document.getElementById("backToTop");
    
    if (backToTopButton) {
        // Show/hide button on scroll
        window.addEventListener('scroll', function () {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopButton.style.display = "inline-flex";
            } else {
                backToTopButton.style.display = "none";
            }
        });
        
        // Scroll to top on click
        backToTopButton.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Hover effect
        backToTopButton.addEventListener('mouseover', function() {
            this.style.filter = 'brightness(1.1)';
            this.style.transform = 'translateY(-2px)';
        });
        
        backToTopButton.addEventListener('mouseout', function() {
            this.style.filter = 'brightness(1)';
            this.style.transform = 'translateY(0)';
        });
    }
});


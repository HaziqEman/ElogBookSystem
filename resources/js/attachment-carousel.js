document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-carousel-step]') : null;
    if (!button) return;

    var carousel = button.closest('[data-attachment-carousel]');
    if (!carousel) return;

    var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-slide]'));
    if (slides.length < 2) return;

    var current = slides.findIndex(function (slide) { return !slide.classList.contains('hidden'); });
    if (current < 0) current = 0;

    var step = parseInt(button.dataset.carouselStep, 10) || 1;
    var next = (current + step + slides.length) % slides.length;
    slides.forEach(function (slide, index) {
        slide.classList.toggle('hidden', index !== next);
    });

    var counter = carousel.querySelector('[data-carousel-count]');
    if (counter) counter.textContent = (next + 1) + ' / ' + slides.length;
});

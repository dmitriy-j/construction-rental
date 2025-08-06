// Ripple effect implementation
export function initRipple() {
    document.addEventListener('click', function(e) {
        const rippleElements = document.querySelectorAll('.ripple');

        rippleElements.forEach(element => {
            if (element.contains(e.target)) {
                createRipple(e, element);
            }
        });
    });

   function initRipple() {
    document.addEventListener('click', (e) => {
        const rippleBtn = e.target.closest('.ripple');
        if (!rippleBtn) return;

        // Только добавление класса, анимация в CSS
        rippleBtn.classList.add('ripple-active');
        setTimeout(() => rippleBtn.classList.remove('ripple-active'), 600);
    });
    }
}

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

    function createRipple(event, element) {
        // Remove existing ripples
        const existingRipples = element.querySelectorAll('.ripple-animation');
        existingRipples.forEach(ripple => ripple.remove());

        // Create new ripple
        const ripple = document.createElement('span');
        ripple.classList.add('ripple-animation');

        // Position ripple
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        element.appendChild(ripple);

        // Remove ripple after animation
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
}

export function initRipple() {
  document.addEventListener('click', function(e) {
    const rippleBtn = e.target.closest('.ripple');
    if (!rippleBtn) return;

    const rect = rippleBtn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    const ripple = document.createElement('span');
    ripple.className = 'ripple-effect';
    ripple.style.width = `${size}px`;
    ripple.style.height = `${size}px`;
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;

    rippleBtn.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
}

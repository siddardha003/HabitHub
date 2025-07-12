// Simple animations
document.addEventListener('DOMContentLoaded', function() {
    // Floating logo animation
    const logoIcon = document.querySelector('.logo i');
    if (logoIcon && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        logoIcon.style.animation = 'float 3s ease-in-out infinite';
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
                100% { transform: translateY(0px); }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Button hover effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            btn.style.transform = 'translateY(-2px)';
            btn.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
            btn.style.boxShadow = '';
        });
    });
});
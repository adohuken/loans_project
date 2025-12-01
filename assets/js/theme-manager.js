// Theme Manager - Dark Mode & Color Themes
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.currentColor = localStorage.getItem('colorTheme') || 'blue';
        this.init();
    }

    init() {
        this.applyTheme();
        this.applyColorTheme();
        this.createThemeToggle();
        this.createColorPicker();
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        this.updateThemeIcon();
    }

    applyColorTheme() {
        document.documentElement.setAttribute('data-color', this.currentColor);
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.currentTheme);
        this.applyTheme();
        
        // Smooth transition
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    setColorTheme(color) {
        this.currentColor = color;
        localStorage.setItem('colorTheme', color);
        this.applyColorTheme();
    }

    updateThemeIcon() {
        const icon = document.getElementById('theme-toggle-icon');
        if (icon) {
            icon.className = this.currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    createThemeToggle() {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    createColorPicker() {
        const picker = document.getElementById('color-picker');
        if (picker) {
            picker.addEventListener('change', (e) => this.setColorTheme(e.target.value));
            picker.value = this.currentColor;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

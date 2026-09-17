import { useEffect, useState } from 'react';

export function readAppTheme() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
}

export function useAppTheme() {
    const [theme, setTheme] = useState(readAppTheme);

    useEffect(() => {
        const root = document.documentElement;
        const sync = () => setTheme(readAppTheme());
        const observer = new MutationObserver(sync);
        observer.observe(root, { attributes: true, attributeFilter: ['data-bs-theme'] });
        window.addEventListener('storage', sync);
        return () => {
            observer.disconnect();
            window.removeEventListener('storage', sync);
        };
    }, []);

    return theme;
}

export function chartPalette(theme) {
    const dark = theme === 'dark';
    return {
        mode: dark ? 'dark' : 'light',
        fore: dark ? '#9AA4B8' : '#7e8299',
        ink: dark ? '#E8EEF8' : '#1e2129',
        grid: dark ? '#2A3447' : '#eceff5',
        surface: dark ? '#182030' : '#ffffff',
    };
}

const STORAGE_KEY = 'career-next-theme';
const VALID_PREFERENCES = ['light', 'dark', 'system'];
const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

function readPreference() {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        return VALID_PREFERENCES.includes(stored) ? stored : 'system';
    } catch (error) {
        return 'system';
    }
}

let preference = readPreference();

function applyPreference(nextPreference, persist = true) {
    preference = VALID_PREFERENCES.includes(nextPreference) ? nextPreference : 'system';
    const theme = preference === 'system'
        ? (colorSchemeQuery.matches ? 'dark' : 'light')
        : preference;

    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.themePreference = preference;
    document.documentElement.style.colorScheme = theme;

    if (persist) {
        try {
            window.localStorage.setItem(STORAGE_KEY, preference);
        } catch (error) {
            // The current selection still works when browser storage is unavailable.
        }
    }

    window.dispatchEvent(new CustomEvent('career-next-theme-change', {
        detail: { preference, theme },
    }));
}

export function getThemePreference() {
    return preference;
}

export function setThemePreference(nextPreference) {
    applyPreference(nextPreference);
}

colorSchemeQuery.addEventListener('change', () => {
    if (preference === 'system') {
        applyPreference('system', false);
    }
});

applyPreference(preference, false);

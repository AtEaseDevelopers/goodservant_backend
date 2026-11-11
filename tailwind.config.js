/** @type {import('tailwindcss').Config} */
module.exports = {
    // Scoped to the customer portal. The admin panel uses Bootstrap/CoreUI and
    // is not processed by Tailwind.
    content: [
        './resources/views/portal/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    // Classes toggled only from JavaScript (not statically present as full
    // class attributes) so the scanner would otherwise drop them.
    safelist: [
        'hidden',
        'flex',
        'opacity-0',
        'translate-y-2',
    ],
    plugins: [],
}

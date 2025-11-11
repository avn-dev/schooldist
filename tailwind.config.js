/** @type {import('tailwindcss').Config} */
/** https://uicolors.app/create */

const colors = require('tailwindcss/colors');
const defaultTheme = require('tailwindcss/defaultTheme')
const { generateTailwindConfigFiles } = require('./system/bundles/Core/Resources/js/webpack/util')

module.exports = {
    darkMode: ['class', '[data-mode="dark"]'],
    content: [
        "./system/bundles/*/Resources/**/*.{vue,ts,blade.php}",
        "./system/bundles/Admin/Resources/views/*.tpl",
        ...generateTailwindConfigFiles()
    ],
    safelist: [
        /** Verhindern, dass primary-Klassen entfernt werden, wenn diese dynamisch eingebaut werden (generatePrimaryColorClass) */
        {
            pattern: /^(bg|text|border|outline)-primary-[0-9]+(\/[0-9]+)?$/,
            variants: ["hover", "focus"],
        },
    ],
    theme: {
        fontFamily: {
            //body: ['Helvetica', ...defaultTheme.fontFamily.sans],
            //heading: ['Helvetica', ...defaultTheme.fontFamily.sans],
            heading: [["Inter", ...defaultTheme.fontFamily.sans], { fontFeatureSettings: '"tnum"' }],
            body: [["Inter", ...defaultTheme.fontFamily.sans], { fontFeatureSettings: '"tnum"' }],
        },
        colors: {
            transparent: colors.transparent,
            white: colors.white,
            black: colors.black,
            red: colors.red,
            yellow: colors.yellow,
            green: colors.green,
            lime: colors.lime,
            blue: colors.blue,
            sky: colors.sky,
            primary: {
                50: 'rgb(var(--primary-color-50, 242 245 252) / <alpha-value>)',
                100: 'rgb(var(--primary-color-100, 226 233 247) / <alpha-value>)',
                200: 'rgb(var(--primary-color-200, 203 217 242) / <alpha-value>)',
                300: 'rgb(var(--primary-color-300, 167 193 233) / <alpha-value>)',
                400: 'rgb(var(--primary-color-400, 126 160 220) / <alpha-value>)',
                500: 'rgb(var(--primary-color-500, 95 130 210) / <alpha-value>)',
                600: 'rgb(var(--primary-color-600, 78 106 198) / <alpha-value>)',
                700: 'rgb(var(--primary-color-700, 65 86 180) / <alpha-value>)',
                800: 'rgb(var(--primary-color-800, 58 72 147) / <alpha-value>)',
                900: 'rgb(var(--primary-color-900, 51 63 117) / <alpha-value>)',
                950: 'rgb(var(--primary-color-950, 35 40 72) / <alpha-value>)',
            },
            gray: {
                50: 'rgb(243 244 245 / <alpha-value>)',
                100: 'rgb(197 208 213 / <alpha-value>)',
                200: 'rgb(169 185 192 / <alpha-value>)',
                300: 'rgb(141 162 172 / <alpha-value>)',
                400: 'rgb(113 139 152 / <alpha-value>)',
                500: 'rgb(91 115 125 / <alpha-value>)',
                600: 'rgb(71 89 97 / <alpha-value>)',
                700: 'rgb(51 64 70 / <alpha-value>)',
                800: 'rgb(41 51 56 / <alpha-value>)',
                900: 'rgb(33 41 46 / <alpha-value>)',
                950: 'rgb(21 26 29 / <alpha-value>)',
            }
        }
    },
    plugins: [],
}


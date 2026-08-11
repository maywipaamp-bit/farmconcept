import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/*
 | ไม่ใช้ Tailwind และไม่ใช้ฟอนต์จาก plugin
 | โปรเจกต์นี้มีระบบ design token ของตัวเองอยู่แล้วที่ assets/css/standard/tokens.css
 | ถ้าเปิด Tailwind ไว้ ตัวแปร --font-sans ของ Tailwind จะชนกับของระบบโดยตรง
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: ['resources/views/**'],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

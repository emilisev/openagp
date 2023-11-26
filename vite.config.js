import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import * as path from "path";

export default defineConfig({
    mode : 'development',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            moment: path.resolve(__dirname, 'node_modules/moment/moment.js'),
        }
    }
});

import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const viteHost = env.VITE_DEV_SERVER_HOST ?? 'localhost';
    const vitePort = Number(env.VITE_DEV_SERVER_PORT ?? 5174);
    const viteOrigin =
        env.VITE_DEV_SERVER_URL ?? `http://${viteHost}:${vitePort}`;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: vitePort,
            cors: true,
            origin: viteOrigin,
            hmr: {
                host: viteHost,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});

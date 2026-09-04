import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'
import { networkInterfaces } from 'node:os';

function localNetworkHost() {
    for (const addresses of Object.values(networkInterfaces())) {
        const address = addresses?.find(entry => entry.family === 'IPv4' && !entry.internal);
        if (address) return address.address;
    }

    return '127.0.0.1';
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devHost = env.VITE_HMR_HOST || localNetworkHost();

    return {
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        origin: `http://${devHost}:5173`,
        hmr: {
            host: devHost,
        },
        warmup: {
            clientFiles: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
        },
    },
    };
});

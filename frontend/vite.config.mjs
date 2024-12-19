import vue from '@vitejs/plugin-vue'
import fs from 'fs-extra'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [
        vue(),
        {
            name: 'copy-to-public',
            closeBundle: async () => {
                await fs.emptyDir('../server/public')
                await fs.copy('dist', '../server/public', { overwrite: true })
                console.log('Files copied to server/public successfully!')
            }
        }
    ],
    preview: {
        host: 'localhost',
        port: 5174,
        strictPort: true
    },
    server: {
        proxy: {
            '^(/.*)?/api': {
                target: 'http://localhost:35456',
                changeOrigin: true,
                rewrite: (path) => path.replace(/^.*\/api/, '/api')
            }
        }
    },
    build: {
        assetsDir: 'assets',
        emptyOutDir: true,
        outDir: 'dist'
    },
    base: './'
}) 
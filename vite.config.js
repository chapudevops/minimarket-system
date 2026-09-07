import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy'

// Las vistas cargan los assets con asset('build/...'), no con @vite(), asi que
// aca no se empaqueta nada: solo se copian los directorios de resources/ a
// public/build/. main.js es la entrada minima que rollup necesita para correr.
export default defineConfig({
    // outDir vive dentro de public/, asi que hay que desactivar publicDir o Vite
    // copiaria public/ (index.php, favicon.ico, robots.txt) dentro de public/build/.
    publicDir: false,
    build: {
        manifest: true,
        outDir: 'public/build/',
        cssCodeSplit: true,
        rollupOptions: {
            input: 'resources/js/main.js',
        },
    },
    plugins: [
        viteStaticCopy({
            targets: [
                { src: 'resources/css', dest: '' },
                { src: 'resources/fonts', dest: '' },
                { src: 'resources/images', dest: '' },
                { src: 'resources/js', dest: '' },
                { src: 'resources/plugins', dest: '' },
                { src: 'resources/sass', dest: '' },
            ],
        })
    ],
});

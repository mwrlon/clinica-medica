import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync } from 'fs';
import { join, relative } from 'path';

// Lista recursivamente todos os arquivos com as extensões informadas
function listarArquivos(dir, extensoes) {
    const arquivos = [];
    for (const item of readdirSync(dir, { withFileTypes: true })) {
        const caminho = join(dir, item.name);
        if (item.isDirectory()) {
            arquivos.push(...listarArquivos(caminho, extensoes));
        } else if (extensoes.some(ext => item.name.endsWith(ext))) {
            arquivos.push(relative(process.cwd(), caminho).replace(/\\/g, '/'));
        }
    }
    return arquivos;
}

const entradas = [
    ...listarArquivos('resources/css', ['.css']),
    ...listarArquivos('resources/js', ['.js']),
];

export default defineConfig({
    plugins: [
        laravel({
            input: entradas,
            refresh: true,
        }),
    ],
});
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const currentDir = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    root: resolve(currentDir),
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: "resources/dist",
        emptyOutDir: true,
        manifest: false,
        rollupOptions: {
            input: resolve(currentDir, "resources/assets/docs.css"),
            output: {
                assetFileNames: "docs.css",
            },
        },
    },
});

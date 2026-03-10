import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import { resolve } from "node:path";

export default defineConfig({
    root: resolve(import.meta.dirname),
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: "resources/dist",
        emptyOutDir: true,
        manifest: false,
        rollupOptions: {
            input: resolve(import.meta.dirname, "resources/assets/docs.css"),
            output: {
                assetFileNames: "docs.css",
            },
        },
    },
});

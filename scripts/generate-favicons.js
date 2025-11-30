import sharp from 'sharp';
import { readFileSync, writeFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const rootDir = join(__dirname, '..');

const logoPath = join(rootDir, 'resources/images/brand/DefinitiveBrand.png');
const publicDir = join(rootDir, 'public');

async function generateFavicons() {
    try {
        console.log('🔄 Convertendo logo para favicons...');

        // Ler a imagem original
        const imageBuffer = readFileSync(logoPath);
        const metadata = await sharp(imageBuffer).metadata();

        // Converter PNG para base64 para usar no SVG
        const pngBuffer = await sharp(imageBuffer)
            .resize(512, 512, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
            .png()
            .toBuffer();

        const base64Image = pngBuffer.toString('base64');

        // Gerar favicon.ico (32x32) - múltiplos tamanhos para melhor compatibilidade
        const icoSizes = [16, 32, 48];
        const icoImages = await Promise.all(
            icoSizes.map(size =>
                sharp(imageBuffer)
                    .resize(size, size, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
                    .png()
                    .toBuffer()
            )
        );

        // Para ICO, vamos usar o tamanho 32x32 (formato mais comum)
        await sharp(imageBuffer)
            .resize(32, 32, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
            .png()
            .toFile(join(publicDir, 'favicon.ico'));

        console.log('✅ favicon.ico criado (32x32)');

        // Gerar favicon.svg com imagem inline (base64)
        const svgContent = `<?xml version="1.0" encoding="UTF-8"?>
<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <image width="512" height="512" preserveAspectRatio="xMidYMid meet" xlink:href="data:image/png;base64,${base64Image}"/>
</svg>`;

        writeFileSync(join(publicDir, 'favicon.svg'), svgContent);
        console.log('✅ favicon.svg criado (512x512)');

        // Gerar apple-touch-icon.png (180x180)
        await sharp(imageBuffer)
            .resize(180, 180, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 1 } })
            .png()
            .toFile(join(publicDir, 'apple-touch-icon.png'));

        console.log('✅ apple-touch-icon.png criado (180x180)');

        console.log('🎉 Todos os favicons foram gerados com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao gerar favicons:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
}

generateFavicons();


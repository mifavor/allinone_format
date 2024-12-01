import fs from 'fs';
import { dirname, join } from 'path';
import sharp from 'sharp';
import { fileURLToPath } from 'url';

// 获取 __dirname 的 ES modules 替代方案
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

async function generateIcons() {
    const svgBuffer = fs.readFileSync(join(__dirname, '../public/apple-touch-icon.svg'));

    // 生成 apple-touch-icon.png (180x180)
    await sharp(svgBuffer)
        .resize(180, 180)
        .png()
        .toFile(join(__dirname, '../public/apple-touch-icon.png'));

    // 生成 favicon.ico (32x32)
    await sharp(join(__dirname, '../public/favicon.svg'))
        .resize(32, 32)
        .toFile(join(__dirname, '../public/favicon.ico'));
}

generateIcons().catch(console.error); 
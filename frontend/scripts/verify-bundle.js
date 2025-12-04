import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const assetsDir = path.resolve(__dirname, '../../public_html/assets');

function assertFileExists(fileName) {
  const filePath = path.join(assetsDir, fileName);
  if (!fs.existsSync(filePath)) {
    throw new Error(`Fichier manquant : ${filePath}. Lancez \"npm run build\" pour générer le bundle.`);
  }
  const stats = fs.statSync(filePath);
  if (!stats.isFile()) {
    throw new Error(`Chemin inattendu (pas un fichier) : ${filePath}`);
  }
  return { filePath, size: stats.size };
}

function assertMinSize(check, minBytes) {
  if (check.size < minBytes) {
    throw new Error(`Le fichier ${check.filePath} semble trop petit (${check.size} octets). Vérifiez que le build s'est bien déroulé.`);
  }
}

function assertTailwindCompiled(cssPath) {
  const content = fs.readFileSync(cssPath, 'utf8');
  if (content.includes('@tailwind base') || content.includes('@tailwind components')) {
    throw new Error(`Le CSS compilé contient encore les directives @tailwind. Re-lancez \"npm run build\" pour produire le CSS final.`);
  }
  if (!content.includes('--tw-')) {
    throw new Error(`Aucune variable Tailwind détectée dans ${cssPath}. Assurez-vous que les plugins sont bien chargés lors du build.`);
  }
}

try {
  const cssCheck = assertFileExists('main.css');
  assertMinSize(cssCheck, 2000);
  assertTailwindCompiled(cssCheck.filePath);

  const jsCheck = assertFileExists('main.js');
  assertMinSize(jsCheck, 500);

  console.log('✔ Vérification réussie : les assets compilés sont présents et semblent corrects.');
  process.exit(0);
} catch (error) {
  console.error(`✖ Vérification échouée : ${error.message}`);
  process.exit(1);
}

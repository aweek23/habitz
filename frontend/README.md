# Habitz frontend

Pile Tailwind CSS avec daisyUI, un plugin HyperUI maison, Preline et Flowbite. Le frontend vit dans `frontend/` tandis que le code PHP est isolé dans `backend/` et l'entrée publique dans `public_html/`.

## Structure
- `frontend/` : sources Vite/Tailwind (`src/`, `components/`, `pages/`, `styles/`).
- `backend/` : logique PHP et configuration sensible (non exposée depuis `public_html/`).
- `public_html/` : point d'entrée PHP (`index.php`) et assets compilés (`assets/`, `css/`).

## Scripts
Depuis le dossier `frontend/` :
- `npm run dev` : lance Vite sur `http://localhost:5173` avec rechargement à chaud.
- `npm run build` : génère les assets de production dans `public_html/assets` sans supprimer les fichiers PHP.
- `npm run preview` : sert le build généré.

## Installation
1. Installer Node.js 18+.
2. Exécuter `npm install` dans `frontend/` pour récupérer les dépendances (tailwindcss, daisyUI, Preline, Flowbite, Vite, etc.).
3. Lancer le projet avec `npm run dev`.

Le build produit des fichiers dans `public_html/assets/` ignorés par Git (sauf fichiers de structure `.gitkeep`). Inclure le manifest généré si vous servez les assets via PHP.

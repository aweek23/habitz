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
- Le `postinstall` déclenche `npm run build` automatiquement pour éviter d'oublier de régénérer le CSS/JS après installation des dépendances.

## Installation
1. Installer Node.js 18+.
2. Exécuter `npm install` dans `frontend/` pour récupérer les dépendances (tailwindcss, daisyUI, Preline, Flowbite, Vite, etc.). Le `postinstall` lancera automatiquement `npm run build` et placera `main.css`/`main.js` dans `public_html/assets/`.
3. Lancer le projet avec `npm run dev`.

Les fichiers générés dans `public_html/assets/` sont maintenant versionnés : assurez-vous de committer le CSS/JS produits ou de les régénérer en CI pour que le PHP serve toujours les derniers styles.

## Problème traité : styles manquants sur les nouveaux composants
- Symptôme : lorsqu'un composant est ajouté (HTML visible dans la page), les classes Tailwind associées ne sont pas présentes dans le bundle CSS servi depuis `public_html/assets/main.css`.
- Diagnostic initial : Tailwind génère les utilitaires uniquement au moment du build Vite. Sans exécution de `npm run dev`/`npm run build` après l'ajout du composant, les nouvelles classes ne sont jamais injectées dans le CSS compilé qui reste inchangé dans `public_html/assets/`.
- Correctif appliqué :
  - `npm install` déclenche désormais automatiquement `npm run build` via le script `postinstall` pour régénérer le CSS/JS.
  - Les assets générés ne sont plus ignorés par Git, évitant de livrer une branche sans le CSS compilé.
  - Recommandé en complément : garder une étape CI `npm ci && npm run build` avant déploiement pour garantir que `public_html/assets/main.css` inclut les nouveaux composants.

import './styles/index.css';
import 'flowbite';
import 'preline';

if (window.HSStaticMethods) {
  window.HSStaticMethods.autoInit();
}

const root = document.getElementById('app');

root.innerHTML = `
  <main class="min-h-screen bg-base-200">
    <header class="bg-base-100 shadow-sm">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
          <span class="ui-badge">Habitz</span>
          <h1 class="text-xl font-semibold text-base-content">Onboarding frontend</h1>
        </div>
        <button class="ui-btn-primary">Lancer l'interface</button>
      </div>
    </header>
    <section class="mx-auto grid max-w-6xl gap-6 px-6 py-10 lg:grid-cols-2">
      <article class="ui-card">
        <div class="flex items-center gap-3">
          <span class="badge badge-primary">daisyUI</span>
          <span class="badge badge-ghost">HyperUI</span>
        </div>
        <h2 class="text-lg font-semibold text-base-content">Pile Tailwind prête</h2>
        <p class="text-sm text-base-content/80">Tailwind CSS est configuré avec les plugins daisyUI, un plugin maison inspiré d'HyperUI, Preline et Flowbite.</p>
        <button class="btn btn-outline">Voir les composants</button>
      </article>
      <article class="ui-card">
        <div class="flex items-center gap-2">
          <span class="badge badge-secondary">Préline</span>
          <span class="badge badge-accent">Flowbite</span>
        </div>
        <h2 class="text-lg font-semibold text-base-content">Scripts inclus</h2>
        <p class="text-sm text-base-content/80">Les bibliothèques JavaScript Preline et Flowbite sont prêtes à être importées via Vite pour leurs composants interactifs.</p>
        <div class="flex flex-wrap gap-3">
          <button class="btn btn-primary">Démarrer le Dev Server</button>
          <button class="btn">Compiler le build</button>
        </div>
      </article>
    </section>
  </main>
`;

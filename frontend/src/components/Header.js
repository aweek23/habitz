export function Header() {
  return `
    <header class="bg-base-100 shadow-sm">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
          <span class="ui-badge">Habitz</span>
          <h1 class="text-xl font-semibold text-base-content">Onboarding frontend</h1>
        </div>
        <div class="flex items-center gap-3">
          <span class="badge badge-outline">Vite</span>
          <span class="badge badge-outline">Tailwind CSS</span>
          <button class="ui-btn-primary">Lancer l'interface</button>
        </div>
      </div>
    </header>
  `;
}

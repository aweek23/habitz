export function FeatureCard({ badges = [], title, description, actions = [] }) {
  const badgeHtml = badges
    .map((badge) => `<span class="badge ${badge.variant ?? 'badge-ghost'}">${badge.label}</span>`)
    .join('');

  const actionsHtml = actions
    .map(
      (action) => `
        <button class="${action.variant ?? 'btn'}">${action.label}</button>
      `
    )
    .join('');

  return `
    <article class="ui-card">
      <div class="flex flex-wrap items-center gap-2">${badgeHtml}</div>
      <h2 class="text-lg font-semibold text-base-content">${title}</h2>
      <p class="text-sm text-base-content/80">${description}</p>
      <div class="flex flex-wrap gap-3">${actionsHtml}</div>
    </article>
  `;
}

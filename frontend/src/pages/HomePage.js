import { Header } from '../components/Header';
import { FeatureCard } from '../components/FeatureCard';

export function HomePage() {
  const cards = [
    {
      badges: [
        { label: 'daisyUI', variant: 'badge-primary' },
        { label: 'HyperUI', variant: 'badge-ghost' }
      ],
      title: 'Pile Tailwind prête',
      description:
        "Tailwind CSS est configuré avec les plugins daisyUI, un plugin maison inspiré d'HyperUI, Preline et Flowbite.",
      actions: [
        { label: 'Voir les composants', variant: 'btn btn-outline' }
      ]
    },
    {
      badges: [
        { label: 'Préline', variant: 'badge-secondary' },
        { label: 'Flowbite', variant: 'badge-accent' }
      ],
      title: 'Scripts inclus',
      description:
        'Les bibliothèques JavaScript Preline et Flowbite sont prêtes à être importées via Vite pour leurs composants interactifs.',
      actions: [
        { label: 'Démarrer le Dev Server', variant: 'btn btn-primary' },
        { label: 'Compiler le build', variant: 'btn' }
      ]
    }
  ];

  const features = cards.map((card) => FeatureCard(card)).join('');

  return `
    <main class="min-h-screen bg-base-200">
      ${Header()}
      <section class="mx-auto grid max-w-6xl gap-6 px-6 py-10 lg:grid-cols-2">
        ${features}
      </section>
    </main>
  `;
}

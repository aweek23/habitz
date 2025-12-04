<?php
require_once __DIR__ . '/../backend/config.php';
?>
<!doctype html>
<html lang="fr" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Habitz | Interface</title>
    <link rel="stylesheet" href="/assets/main.css" />
  </head>
  <body class="bg-base-200 text-base-content">
    <nav class="bg-neutral-primary fixed w-full z-20 top-0 start-0 border-b border-default">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
  <a href="https://flowbite.com/" class="flex items-center space-x-3 rtl:space-x-reverse">
      <img src="https://flowbite.com/docs/images/logo.svg" class="h-7" alt="Flowbite Logo" />
      <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">Flowbite</span>
  </a>
  <div class="flex items-center md:order-2">
    <button type="button" data-collapse-toggle="navbar-search" aria-controls="navbar-search" aria-expanded="false" class="flex items-center justify-center md:hidden text-body hover:text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-2 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm w-10 h-10 focus:outline-none">
      <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
      <span class="sr-only">Search</span>
    </button>
    <div id="app">Chargement de l'interface Habitz...</div>
    <script type="module" src="/assets/main.js"></script>
    <noscript>Activez JavaScript pour charger l'interface.</noscript>
  </body>
</html>

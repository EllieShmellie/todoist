export default defineNuxtConfig({
  compatibilityDate: '2026-07-10',
  ssr: false,
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['@fontsource-variable/inter/index.css', '~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api',
    },
  },
  app: {
    head: {
      htmlAttrs: { lang: 'ru' },
      titleTemplate: '%s — Фокус',
      meta: [
        { name: 'description', content: 'Спокойное управление задачами и дедлайнами.' },
        { name: 'theme-color', content: '#ffffff' },
      ],
    },
  },
  typescript: {
    typeCheck: true,
    strict: true,
  },
})

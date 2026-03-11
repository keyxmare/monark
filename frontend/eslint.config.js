import boundaries from 'eslint-plugin-boundaries'
import perfectionist from 'eslint-plugin-perfectionist'
import pluginVue from 'eslint-plugin-vue'
import vuejsAccessibility from 'eslint-plugin-vuejs-accessibility'

const BOUNDED_CONTEXTS = ['shared', 'identity', 'catalog', 'dependency', 'assessment', 'activity']

export default [
  ...pluginVue.configs['flat/recommended'],
  ...vuejsAccessibility.configs['flat/recommended'],
  perfectionist.configs['recommended-natural'],
  {
    plugins: {
      boundaries,
    },
    settings: {
      'boundaries/elements': BOUNDED_CONTEXTS.map((context) => ({
        mode: 'folder',
        pattern: `src/${context}`,
        type: context,
      })),
    },
    rules: {
      'boundaries/element-types': [
        'error',
        {
          default: 'disallow',
          rules: BOUNDED_CONTEXTS.map((context) => ({
            allow: context === 'shared' ? ['shared'] : [context, 'shared'],
            from: [context],
          })),
        },
      ],
      'boundaries/no-private': 'error',
      'vue/multi-word-component-names': 'off',
    },
  },
  {
    ignores: ['dist/**', 'node_modules/**', 'coverage/**'],
  },
]

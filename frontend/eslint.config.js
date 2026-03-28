import boundaries from 'eslint-plugin-boundaries';
import perfectionist from 'eslint-plugin-perfectionist';
import pluginVue from 'eslint-plugin-vue';
import vuejsAccessibility from 'eslint-plugin-vuejs-accessibility';
import tseslint from 'typescript-eslint';

const BOUNDED_CONTEXTS = ['shared', 'identity', 'catalog', 'dependency', 'assessment', 'activity'];

export default [
  ...pluginVue.configs['flat/recommended'],
  ...vuejsAccessibility.configs['flat/recommended'],
  perfectionist.configs['recommended-natural'],
  {
    files: ['**/*.vue'],
    languageOptions: {
      parserOptions: {
        parser: tseslint.parser,
      },
    },
  },
  {
    plugins: {
      boundaries,
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
      'vuejs-accessibility/label-has-for': [
        'error',
        {
          required: { some: ['nesting', 'id'] },
        },
      ],
    },
    settings: {
      'boundaries/elements': BOUNDED_CONTEXTS.map((context) => ({
        mode: 'folder',
        pattern: `src/${context}`,
        type: context,
      })),
    },
  },
  {
    ignores: ['dist/**', 'node_modules/**', 'coverage/**'],
  },
];

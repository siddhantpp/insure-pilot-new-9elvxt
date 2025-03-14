module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: {
          browsers: ['>0.2%', 'not dead', 'not op_mini all']
        },
        useBuiltIns: 'usage',
        corejs: 3,
        modules: false
      }
    ],
    ['@babel/preset-react', { runtime: 'automatic' }],
    ['@babel/preset-typescript', { isTSX: true, allExtensions: true }]
  ],
  plugins: [
    ['@babel/plugin-transform-runtime', { regenerator: true, corejs: 3 }],
    '@babel/plugin-proposal-class-properties',
    '@babel/plugin-proposal-object-rest-spread',
    'babel-plugin-transform-import-meta',
    [
      'babel-plugin-transform-imports',
      {
        lodash: {
          transform: 'lodash/${member}',
          preventFullImport: true
        }
      }
    ]
  ],
  env: {
    test: {
      presets: [
        ['@babel/preset-env', { targets: { node: 'current' } }],
        '@babel/preset-react',
        '@babel/preset-typescript'
      ],
      plugins: ['@babel/plugin-transform-modules-commonjs']
    },
    production: {
      plugins: [
        ['babel-plugin-transform-react-remove-prop-types', { removeImport: true }],
        ['babel-plugin-styled-components', { displayName: false, pure: true }]
      ]
    },
    development: {
      plugins: [
        ['babel-plugin-styled-components', { displayName: true, pure: false }]
      ]
    }
  }
};
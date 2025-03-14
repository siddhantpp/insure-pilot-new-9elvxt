import path from 'path';
import { CracoConfig } from '@craco/craco';
import { BundleAnalyzerPlugin } from 'webpack-bundle-analyzer'; // v4.8.0
import CompressionPlugin from 'compression-webpack-plugin'; // v10.0.0

const cracoConfig: CracoConfig = {
  webpack: {
    configure: (webpackConfig, { env, paths }) => {
      const isProduction = env === 'production';
      
      // Add path aliases to match tsconfig.json paths
      if (webpackConfig.resolve) {
        webpackConfig.resolve.alias = {
          ...webpackConfig.resolve.alias,
          '@components': path.resolve(__dirname, 'src/components'),
          '@services': path.resolve(__dirname, 'src/services'),
          '@hooks': path.resolve(__dirname, 'src/hooks'),
          '@utils': path.resolve(__dirname, 'src/utils'),
          '@contexts': path.resolve(__dirname, 'src/contexts'),
          '@types': path.resolve(__dirname, 'src/types'),
          '@assets': path.resolve(__dirname, 'src/assets'),
          '@views': path.resolve(__dirname, 'src/views'),
        };
      }
      
      // Configure optimization settings for production builds
      if (isProduction && webpackConfig.optimization) {
        webpackConfig.optimization.runtimeChunk = 'single';
        webpackConfig.optimization.splitChunks = {
          chunks: 'all',
          maxInitialRequests: 20,
          maxAsyncRequests: 20,
          minSize: 15000,
          maxSize: 250000,
          cacheGroups: {
            vendors: {
              test: /[\\/]node_modules[\\/]/,
              name(module, chunks, cacheGroupKey) {
                const packageName = module.context?.match(/[\\/]node_modules[\\/](.*?)([\\/]|$)/)?.[1] || '';
                return `npm.${packageName.replace('@', '')}`;
              },
              priority: -10,
            },
            common: {
              minChunks: 2,
              priority: -20,
              reuseExistingChunk: true,
            },
          },
        };
      }
      
      // Add bundle analyzer plugin when ANALYZE environment variable is true
      if (process.env.ANALYZE === 'true') {
        webpackConfig.plugins = webpackConfig.plugins || [];
        webpackConfig.plugins.push(
          new BundleAnalyzerPlugin({
            analyzerMode: 'server',
            analyzerPort: 8888,
            openAnalyzer: true,
          })
        );
      }
      
      // Add compression plugin for production builds
      if (isProduction) {
        webpackConfig.plugins = webpackConfig.plugins || [];
        webpackConfig.plugins.push(
          new CompressionPlugin({
            algorithm: 'gzip',
            test: /\.(js|css|html|svg)$/,
            threshold: 10240,
            minRatio: 0.8,
          })
        );
      }
      
      return webpackConfig;
    },
  },
  
  babel: {
    presets: [],
    plugins: [
      // Adds plugins for optimized builds
      [
        'babel-plugin-transform-imports',
        {
          '@material-ui/core': {
            transform: '@material-ui/core/esm/${member}',
            preventFullImport: true,
          },
          '@material-ui/icons': {
            transform: '@material-ui/icons/esm/${member}',
            preventFullImport: true,
          },
        },
      ],
      ['@babel/plugin-proposal-class-properties', { loose: true }],
      ['@babel/plugin-proposal-private-methods', { loose: true }],
      [
        'babel-plugin-styled-components',
        {
          displayName: process.env.NODE_ENV !== 'production',
          fileName: false,
        },
      ],
    ],
    loaderOptions: {
      ignore: ['./node_modules/mapbox-gl'],
    },
  },
  
  jest: {
    configure: (jestConfig) => {
      // Extend moduleNameMapper to support path aliases
      jestConfig.moduleNameMapper = {
        ...jestConfig.moduleNameMapper,
        '^@components/(.*)$': '<rootDir>/src/components/$1',
        '^@services/(.*)$': '<rootDir>/src/services/$1',
        '^@hooks/(.*)$': '<rootDir>/src/hooks/$1',
        '^@utils/(.*)$': '<rootDir>/src/utils/$1',
        '^@contexts/(.*)$': '<rootDir>/src/contexts/$1',
        '^@types/(.*)$': '<rootDir>/src/types/$1',
        '^@assets/(.*)$': '<rootDir>/src/assets/$1',
        '^@views/(.*)$': '<rootDir>/src/views/$1',
      };
      
      // Configure coverage thresholds
      jestConfig.coverageThreshold = {
        global: {
          branches: 80,
          functions: 80,
          lines: 85,
          statements: 85,
        },
        './src/components/': {
          branches: 80,
          functions: 85,
          lines: 90,
          statements: 90,
        },
        './src/services/': {
          branches: 75,
          functions: 80,
          lines: 85,
          statements: 85,
        },
      };
      
      // Set up test environment
      jestConfig.setupFilesAfterEnv = [
        ...(jestConfig.setupFilesAfterEnv || []),
        '<rootDir>/src/setupTests.ts',
      ];
      
      jestConfig.testEnvironment = 'jsdom';
      jestConfig.verbose = true;
      
      return jestConfig;
    },
  },
  
  eslint: {
    configure: (eslintConfig) => {
      // Extend ESLint rules
      eslintConfig.extends = [
        ...(eslintConfig.extends || []),
        'eslint:recommended',
        'plugin:react/recommended',
        'plugin:react-hooks/recommended',
        'plugin:jsx-a11y/recommended',
        'plugin:@typescript-eslint/recommended',
        'prettier',
      ];
      
      // Configure TypeScript parser options
      eslintConfig.parser = '@typescript-eslint/parser';
      eslintConfig.parserOptions = {
        ...eslintConfig.parserOptions,
        ecmaVersion: 2020,
        sourceType: 'module',
        ecmaFeatures: {
          jsx: true,
        },
      };
      
      // Add plugins for React, accessibility, and best practices
      eslintConfig.plugins = [
        ...(eslintConfig.plugins || []),
        'react',
        'react-hooks',
        'jsx-a11y',
        '@typescript-eslint',
      ];
      
      // Add specific rules
      eslintConfig.rules = {
        ...eslintConfig.rules,
        'react/prop-types': 'off', // We're using TypeScript
        'react/react-in-jsx-scope': 'off', // Not needed in React 17+
        'jsx-a11y/anchor-is-valid': ['error', { components: ['Link'], specialLink: ['to'] }],
        '@typescript-eslint/explicit-function-return-type': 'off',
        '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
      };
      
      return eslintConfig;
    },
  },
  
  plugins: [
    {
      plugin: require('craco-plugin-env'),
      options: {
        variables: {
          REACT_APP_API_URL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
          REACT_APP_VERSION: process.env.npm_package_version || '0.0.0',
          REACT_APP_BUILD_TIME: new Date().toISOString(),
          REACT_APP_ADOBE_PDF_SDK_URL: process.env.REACT_APP_ADOBE_PDF_SDK_URL || 'https://documentcloud.adobe.com/view-sdk/main.js',
        },
      },
    },
  ],
};

export default cracoConfig;
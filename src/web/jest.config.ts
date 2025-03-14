import type { Config } from '@jest/types';
import path from 'path';

const config: Config.InitialOptions = {
  preset: 'ts-jest',
  testEnvironment: 'jest-environment-jsdom',
  setupFilesAfterEnv: ['<rootDir>/src/setupTests.ts'],
  transform: {
    '^.+\\.(ts|tsx)$': 'ts-jest',
    '^.+\\.(js|jsx)$': 'babel-jest',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
  },
  moduleNameMapper: {
    '^src/(.*)$': '<rootDir>/src/$1',
    '^components/(.*)$': '<rootDir>/src/components/$1',
    '^containers/(.*)$': '<rootDir>/src/containers/$1',
    '^hooks/(.*)$': '<rootDir>/src/hooks/$1',
    '^models/(.*)$': '<rootDir>/src/models/$1',
    '^services/(.*)$': '<rootDir>/src/services/$1',
    '^utils/(.*)$': '<rootDir>/src/utils/$1',
    '^styles/(.*)$': '<rootDir>/src/styles/$1',
    '^assets/(.*)$': '<rootDir>/src/assets/$1',
    '^lib/(.*)$': '<rootDir>/src/lib/$1',
    '^context/(.*)$': '<rootDir>/src/context/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    '\\.(jpg|jpeg|png|gif|svg)$': '<rootDir>/src/__mocks__/fileMock.js',
  },
  moduleFileExtensions: ['ts', 'tsx', 'js', 'jsx', 'json', 'node'],
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 85,
      statements: 85,
    },
    'src/components/': {
      branches: 90,
      functions: 90,
      lines: 90,
      statements: 90,
    },
    'src/containers/': {
      branches: 85,
      functions: 85,
      lines: 85,
      statements: 85,
    },
  },
  collectCoverageFrom: [
    'src/**/*.{ts,tsx}',
    '!src/**/*.d.ts',
    '!src/**/*.stories.{ts,tsx}',
    '!src/**/*.test.{ts,tsx}',
    '!src/**/__tests__/**',
    '!src/**/__mocks__/**',
    '!src/index.tsx',
    '!src/reportWebVitals.ts',
    '!src/setupTests.ts',
  ],
  watchPlugins: [
    'jest-watch-typeahead/filename',
    'jest-watch-typeahead/testname',
  ],
  testPathIgnorePatterns: ['/node_modules/', '/build/', '/dist/', '/coverage/'],
  transformIgnorePatterns: ['/node_modules/(?!(@adobe|axios|lodash-es)/)'],
};

export default config;
const mix = require('laravel-mix'); // laravel-mix v6.0
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Laravel Mix Configuration for Documents View Feature
 |--------------------------------------------------------------------------
 |
 | This file configures Laravel Mix (webpack wrapper) to compile and bundle
 | frontend assets for the Documents View feature. It handles JavaScript 
 | compilation with React support, SASS processing, and various optimizations
 | for both development and production environments.
 |
 */

// Base configuration for all environments
mix.js('resources/js/app.js', 'public/js')
    .react()
    .sass('resources/sass/app.scss', 'public/css')
    .options({
        processCssUrls: true,
        autoprefixer: {
            options: {
                browsers: ['> 1%', 'last 2 versions']
            }
        }
    });

// Environment-specific configurations
if (process.env.NODE_ENV === 'production') {
    // Production optimizations
    mix.options({
        terser: {
            extractComments: false,
            terserOptions: {
                compress: {
                    drop_console: true
                }
            }
        }
    })
    .version() // Add content hash for cache busting
    .extract(); // Extract vendor libraries
} else {
    // Development tools
    mix.sourceMaps() // Enable source maps for debugging
        .browserSync({
            proxy: 'localhost:8000',
            open: false,
            notify: false
        });

    // Enable hot module replacement for React components
    if (mix.inProduction() === false) {
        mix.webpackConfig({
            devServer: {
                hot: true
            }
        });
    }
}

// Common webpack configuration for all environments
mix.webpackConfig({
    resolve: {
        alias: {
            '@': path.resolve('resources/js')
        },
        extensions: ['.js', '.jsx', '.vue', '.ts', '.tsx']
    },
    output: {
        chunkFilename: 'js/[name].js'
    },
    optimization: {
        splitChunks: {
            cacheGroups: {
                vendor: {
                    test: /node_modules/,
                    chunks: 'all',
                    name: 'vendor'
                }
            }
        }
    }
});
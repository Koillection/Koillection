var Encore = require('@symfony/webpack-encore');
var CopyWebpackPlugin = require('copy-webpack-plugin');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('../public/build/')
    .setPublicPath('/build')

    .addEntry('js/app', './js/app.js')
    .addEntry('js/statistics', './js/statistics/statistics.js')

    .addStyleEntry('css/app', './css/app.css')
    .addStyleEntry('css/themes/aubergine', './css/themes/aubergine.css')
    .addStyleEntry('css/themes/sunset', './css/themes/sunset.css')
    .addStyleEntry('css/themes/teal', './css/themes/teal.css')
    .addStyleEntry('css/themes/dark_mode', './css/themes/dark_mode.css')
    .addStyleEntry('css/export', './css/export.css')
    .addStyleEntry('css/translation', './css/translation.css')

    .addPlugin(new CopyWebpackPlugin([
        { from: './img', to: 'images' }
    ]))

    .configureUrlLoader({
        images: { limit: 4096 },
        fonts: { limit: 10240 }
    })

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .autoProvidejQuery()
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();


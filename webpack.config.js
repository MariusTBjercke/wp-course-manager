const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

// Determine whether we are targeting development or production environment.
// npm run build => production target
// npm run watch => development target
let developmentEnv = true;
if (process.env.npm_lifecycle_event === "build") {
  developmentEnv = false;
}

console.log('Running webpack in ' + (developmentEnv ? 'development' : 'production') + ' mode');

module.exports = [
  // JS config
  {
    mode: developmentEnv ? 'development' : 'production',
    entry: {
      script: './src/js/index.ts',
    },
    output: {
      filename: '[name].js',
      path: path.resolve(__dirname, 'dist'),
    },
    resolve: {
      extensions: ['.ts', '.js'],
    },
    module: {
      rules: [
        {
          test: /\.ts$/,
          use: 'ts-loader',
          exclude: /node_modules/,
        },
      ],
    },
    devtool: 'source-map',
  },

  // SCSS config
  {
    mode: developmentEnv ? 'development' : 'production',
    entry: {
      style: './src/scss/style.scss',
      admin: './src/scss/admin.scss',
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
    },
    module: {
      rules: [
        {
          test: /\.s?css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader'
          ],
        },
      ],
    },
    plugins: [
      new RemoveEmptyScriptsPlugin(),
      new MiniCssExtractPlugin({
        filename: '[name].css',
      }),
    ],
    devtool: 'source-map',
  }
];

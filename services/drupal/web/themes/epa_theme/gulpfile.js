'use strict';

const { dest, lastRun, parallel, series, src, watch, task } = require('gulp');
const patternLabConfig = require('./pattern-lab-config.json');
const patternLab = require('@pattern-lab/core')(patternLabConfig);
const postcss = require('gulp-postcss');
const sass = require('gulp-sass');
const sassGlobImporter = require('node-sass-glob-importer');
const sourcemaps = require('gulp-sourcemaps');
const stylelint = require('gulp-stylelint');
const svgSprite = require('gulp-svg-sprite');
const yaml = require('yaml');
const rename = require('gulp-rename');

const fs = require('fs');
const path = require('path');
const util = require('util');

const webpack = require('webpack');
const asyncWebpack = util.promisify(webpack);

const readSource = require('./lib/readSource');
const transform = require('./lib/transform');
const renderSass = require('./lib/renderSass');
const renderUswdsConfig = require('./lib/renderUswdsConfig');
const lintPatternLab = require('./lib/lintPatternLab');

const writeFile = util.promisify(fs.writeFile);
const os = require('os');

const buildConfig = async () => {
  const scssDir = path.join(__dirname, '/source/_patterns/00-config');
  const ymlDir = path.join(__dirname, './source/_data');
  const configDir = path.join(__dirname, '/source/_patterns');

  const parsed = await readSource(
    path.join(
      __dirname,
      './source/_patterns/00-config/config.design-tokens.yml'
    )
  );
  const dataComment =
    '# DO NOT EDIT THIS FILE.  This is a gitignored artifact created by Gulp.' +
    os.EOL +
    '# Design tokens should be edited in _patterns/00-config/config.design-tokens.yml';

  const transformed = transform(parsed);
  const sassComment =
    '// DO NOT EDIT THIS FILE.  This is a gitignored artifact created by Gulp.' +
    os.EOL +
    '// Design tokens should be edited in config.design-tokens.yml';

  await Promise.all([
    writeFile(
      path.join(ymlDir, 'design-tokens.artifact.yml'),
      dataComment + os.EOL + yaml.stringify(transformed.data)
    ),
    writeFile(
      path.join(configDir, '_uswds-theme-settings.artifact.scss'),
      sassComment + os.EOL + renderUswdsConfig(transformed.data)
    ),
    writeFile(
      path.join(scssDir, '_design-tokens.artifact.scss'),
      sassComment + os.EOL + renderSass(transformed.data)
    ),
  ]);
};
exports.buildConfig = buildConfig;

const lintStyles = () => {
  return src('**/!(*.artifact).scss', { cwd: './source', since: lastRun(lintStyles) }).pipe(
    stylelint({
      configFile: '.stylelintrc.yml',
      failAfterError: true,
      reporters: [{ formatter: 'string', console: true }],
    })
  );
};

const compileStyles = () => {
  return src('*.scss', { cwd: './source' })
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: [
          './node_modules/breakpoint-sass/stylesheets',
          './node_modules/tiny-slider/src',
          './node_modules/uswds/src/stylesheets',
        ],
        precision: 10,
        importer: sassGlobImporter(),
      })
    )
    .pipe(
      postcss([
        require('postcss-assets')(),
        require('autoprefixer')({
          remove: false,
        }),
      ])
    )
    .pipe(sourcemaps.write('.'))
    .pipe(dest('css'));
};

const createSprite = () => {
  return src('**/*.svg', { cwd: 'images/_sprite-source-files/' })
    .pipe(svgSprite({
      mode: {
        symbol: {
          dest: '',
          sprite: 'sprite.artifact.svg'
        }
      }
    }))
    .pipe(dest('images'));
};

async function lintPatterns() {
  const errors = await lintPatternLab();
  if (Array.isArray(errors) && errors.length > 0) {
    throw new Error(errors.join('\n'));
  }
}

const buildPatternLab = () => {
  return patternLab.build({ cleanPublic: true, watch: false });
};

async function webpackBundleScripts(mode) {
  const webpackConfig = require('./webpack.config')(mode);
  const stats = await asyncWebpack(webpackConfig);
  if (stats.hasErrors()) {
    throw new Error(stats.compilation.errors.join('\n'));
  }
}

const bundleScripts = (exports.gessoBundleScripts = () =>
  webpackBundleScripts('production'));

const bundleScriptsDev = () => webpackBundleScripts('development');

const watchFiles = () => {
  watch(
    [
      'source/**/*.scss',
      '!source/_patterns/00-config/_config.artifact.design-tokens.scss',
    ],
    { usePolling: true, interval: 1500 },
    series(lintStyles, buildStyles)
  );
  watch(
    ['images/_sprite-source-files/*.svg'],
    { usePolling: true, interval: 1500 },
    buildImages
  );
  watch(
    ['source/_patterns/00-config/config.design-tokens.yml'],
    { usePolling: true, interval: 1500 },
    series(
      buildConfig,
      parallel(series(lintStyles, buildStyles), series(lintPatterns, buildPatternLab))
    )
  );
  watch(
    [
      'source/**/*.{twig,json,yaml,yml}',
      '!source/_patterns/00-config/config.design-tokens.yml',
    ],
    { usePolling: true, interval: 1500 },
    series(lintPatterns, buildPatternLab)
  );
  watch(
    ['js/src/**/*.es6.js'],
    { usePolling: true, interval: 1500 },
    bundleScriptsDev
  );
  watch(
    [
      'source/**/*.md',
    ],
    { usePolling: true, interval: 1500 },
    lintPatterns
  )
};

const buildStyles = (exports.buildStyles = series(lintStyles, compileStyles));
const buildPatterns = (exports.buildPatterns) = series(lintPatterns, buildPatternLab);
const buildImages = (exports.buildImages = createSprite);

const build = (isProduction = true ) =>  {
  const scriptTask = isProduction ? bundleScripts : bundleScriptsDev;
  task('bundleScripts', scriptTask);
  return series(
    buildConfig,
    parallel(task('bundleScripts'), buildImages, buildStyles, buildPatterns));
};

exports.build = build(true);

exports.default = series(build(false), watchFiles);

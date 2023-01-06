'use strict';

const { dest, lastRun, parallel, series, src, watch, task } = require('gulp');
const patternLabConfig = require('./pattern-lab-config.json');
const patternLab = require('@pattern-lab/core')(patternLabConfig);
const postcss = require('gulp-postcss');
const sass = require('gulp-sass')(require('sass'));
// const sassGlobImporter = require('node-sass-glob-importer');
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
const renderUswdsTheme = require('./lib/renderUswdsTheme');
const lintPatternLab = require('./lib/lintPatternLab');

const writeFile = util.promisify(fs.writeFile);
const os = require('os');

const plumber = require('gulp-plumber');
const notify = require('gulp-notify');
const inject = require('gulp-inject');

const log = require('fancy-log');

const plumberErrorHandler = {
  errorHandler: notify.onError({
    title: 'Gulp',
    message: 'Error: <%= error.message %>',
  }),
};

const buildConfig = async () => {
  const configDir = path.join(__dirname, '/source/_patterns/00-config');
  const ymlDir = path.join(__dirname, './source/_data');

  const parsed = await readSource(
    path.join(
      __dirname,
      './source/_patterns/00-config/config.design-tokens.yml'
    )
  );

  const transformed = transform(parsed);

  const yamlComment =
    '# DO NOT EDIT THIS FILE.  This is a gitignored artifact created by Gulp.' +
    os.EOL +
    '# Design tokens should be edited in _patterns/00-config/config.design-tokens.yml';

  const sassComment =
    '// DO NOT EDIT THIS FILE.  This is a gitignored artifact created by Gulp.' +
    os.EOL +
    '// Design tokens should be edited in config.design-tokens.yml';

  await Promise.all([
    writeFile(
      path.join(ymlDir, 'design-tokens.artifact.yml'),
      yamlComment + os.EOL + yaml.stringify(transformed.data)
    ),
    writeFile(
      path.join(configDir, '_uswds-theme.artifact.scss'),
      sassComment + os.EOL + renderUswdsTheme(transformed.data)
    ),
    writeFile(
      path.join(configDir, '_design-tokens.artifact.scss'),
      sassComment + os.EOL + "@use '_uswds-theme.artifact.scss' as *;" + os.EOL + renderSass(transformed.data)
    ),
  ]);
};
exports.buildConfig = buildConfig;

const lintStyles = () => {
  return src('**/!(*.artifact).scss', {
    cwd: './source',
    since: lastRun(lintStyles),
  }).pipe(
    stylelint({
      configFile: '.stylelintrc.yml',
      failAfterError: true,
      reporters: [{ formatter: 'string', console: true }],
    })
  );
};

const buildSass = mode => {
  return src('*.scss', { cwd: './source' })
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: [
          './node_modules/tiny-slider/src',
          './node_modules/@uswds/uswds/packages',
        ],
        precision: 10,
        // importer: sassGlobImporter(),
        outputStyle: mode === 'production' ? 'compressed' : 'expanded',
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
    .pipe(
      svgSprite({
        mode: {
          symbol: {
            dest: '',
            sprite: 'sprite.artifact.svg',
          },
        },
      })
    )
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

const compileStyles = () => buildSass('production');
exports.buildStyles = series(lintStyles, compileStyles);

const compileStylesDev = () => buildSass('development');

const watchFiles = () => {
  watch(
    [
      'source/**/*.scss',
      '!source/_patterns/00-config/_config.artifact.design-tokens.scss',
    ],
    { usePolling: true, interval: 1500 },
    series(lintStyles, compileStylesDev)
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
      parallel(
        series(lintStyles, compileStylesDev),
        series(lintPatterns, buildPatternLab)
      )
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
  watch(['source/**/*.md'], { usePolling: true, interval: 1500 }, lintPatterns);
};

const buildPatterns = (exports.buildPatterns = series(
  lintPatterns,
  buildPatternLab
));
const buildImages = (exports.buildImages = createSprite);

const generateTestUrls = () => {
  return src('vrt_urls-base.txt')
    .pipe(plumber(plumberErrorHandler))
    .pipe(
      inject(src('./source/_patterns/**/*.twig', { read: false }), {
        starttag: '<!-- startinject -->',
        endtag: '<!-- endinject -->',
        removeTags: true,
        transform: (filepath, file, i, length) => {
          const filepathClean = filepath.substr(9);
          const parts = filepathClean
            .replace(/[0-9]{1,2}-(?=[^\/]+\/)/gi, '')
            .replace(/\.twig/gi, '')
            .split('/');
          let path = '';
          let patternParts = '';
          // Ignore any components in files or directories starting with underscore
          for (let i=1; i < parts.length; i++) {
            if (parts[i].startsWith('_')) {
              return;
            }
          }
          if (parts[3] === undefined) {
            patternParts = `${parts[1]}-${parts[2]}`;
          } else {
            patternParts = `${parts[1]}-${parts[2]}-${parts[3]}`;
          }
          path = `${parts[0]}/${patternParts}/${patternParts}.rendered.html`;
          const url = `https://www.epa.gov/themes/epa_theme/pattern-lab/${path}`;
          return url;
        },
      })
    )
    .pipe(rename('vrt_urls.txt'))
    .pipe(dest('.'));
};

const build = (isProduction = true) => {
  const scriptTask = isProduction ? bundleScripts : bundleScriptsDev;
  const stylesTask = isProduction ? compileStyles : compileStylesDev;
  task('bundleScripts', scriptTask);
  task('compileStyles', stylesTask);
  return series(
    buildConfig,
    parallel(
      task('bundleScripts'),
      buildImages,
      task('compileStyles'),
      buildPatterns
    ),
    generateTestUrls
  );
};

exports.build = build(true);

exports.default = series(build(false), watchFiles);

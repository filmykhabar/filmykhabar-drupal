'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var minify = require('gulp-minify');
var imagemin = require('gulp-imagemin');
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var del = require('del');

const paths = {
  styles: {
    src: './assets/scss/**/*.scss',
    dest: './css'
  },
  scripts: {
    src: './assets/js/**/*.js',
    dest: './js'
  },
  images: {
    src: './assets/images/*',
    dest: './images'
  },
  fonts: {
    src: './assets/fonts/**/*.{eot,woff,woff2,ttf,svg}',
    dest: './fonts'
  }
}

// CSS
gulp.task('sass', function(done) {
  gulp.src(paths.styles.src, { base: './assets/scss' })
    // .pipe(sourcemaps.init())
      .pipe(sass({
        includePaths: ['node_modules/susy/sass'],
        outputStyle: 'compressed'
      }).on('error', sass.logError))
      .pipe(autoprefixer({
        browsers: ['last 2 versions']
      }))
    // .pipe(sourcemaps.write('./maps'))
    .pipe(gulp.dest(paths.styles.dest));
  done();
});
gulp.task('sass').description = "Compiles sass files to css files.";

// JavaScript
gulp.task('js', function(done) {
  gulp.src(paths.scripts.src, { base: './assets/js' })
    .pipe(minify())
    .pipe(gulp.dest(paths.scripts.dest));
  done();
});
gulp.task('js').description = "Compiles javascript files.";

// Images
gulp.task('image', function(done) {
  gulp.src(paths.images.src)
    .pipe(imagemin({optimizationLevel: 5}))
    .pipe(gulp.dest(paths.images.dest))
  done();
});
gulp.task('image').description = 'Minifies images.';

// Fonts
gulp.task('fonts', function(done) {
  gulp.src(paths.fonts.src)
    .pipe(gulp.dest(paths.fonts.dest));
  done();
});

// Delete destination css, js and images folders.
gulp.task('clean', function(done) {
  del([paths.styles.dest, paths.scripts.dest, paths.images.dest, paths.fonts.dest]);
  done();
});
gulp.task('clean').description = "Deletes css and js folders.";

// Watch
gulp.task('watch', function(done) {
  var cssWatcher = gulp.watch(paths.styles.src, gulp.parallel('sass'));
  var jsWatcher = gulp.watch(paths.scripts.src, gulp.parallel('js'));
  var imagesWatcher = gulp.watch(paths.images.src, gulp.parallel('image'));
  var fontsWatcher = gulp.watch(paths.fonts.src, gulp.parallel('fonts'));

  cssWatcher.on('all', function(event, path, stats) {
  console.log('File ' + path + ', event=' + event + ', running tasks...');
  });

  jsWatcher.on('all', function(event, path, stats) {
  console.log('File ' + path + ', event=' + event + ', running tasks...');
  });

  imagesWatcher.on('all', function(event, path, stats) {
  console.log('File ' + path + ', event=' + event + ', running tasks...');
  });

  fontsWatcher.on('all', function(event, path, stats) {
  console.log('File ' + path + ', event=' + event + ', running tasks...');
  });

  done();
});
gulp.task('watch').description = "Watch files for changes.";

// Default task.
gulp.task('default', gulp.series('clean', gulp.parallel('sass', 'js', 'image', 'fonts')));
gulp.task('default').description = "Default gulp task for running all tasks.";

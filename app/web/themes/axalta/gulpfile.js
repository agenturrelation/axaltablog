const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');

gulp.task('scss', function () {
  var sassOptions = { outputStyle: 'compressed' };
  return gulp.src('scss/styles.scss')
    .pipe(sass(sassOptions).on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(gulp.dest('./css'));
});

gulp.task('watch', () => {
  gulp.watch('scss/**/*.scss', (done) => {
    gulp.series(['scss'])(done);
  });
});

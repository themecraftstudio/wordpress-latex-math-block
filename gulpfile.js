const path = require('path');
const fs = require('fs');
// const through = require('through2');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const slugify = require('slugify');
const FileSet = require('file-set');
const log = require('fancy-log');
const { series, parallel, src, dest, watch } = require('gulp');
// const replace = require('gulp-string-replace');
// const rename = require("gulp-rename");
const sass = require('gulp-sass');
const postcss = require('gulp-postcss');
const ts = require('gulp-typescript');

const SASS_IMPORTS = [
    // 'node_modules/foundation-sites/scss',
];
const PATHS = {
    assets: 'assets',
    styles: 'assets/styles',
    scripts: 'assets/scripts',
    // html: {
    //     root: '../html',
    //     assets: 'assets',
    //     styles: 'styles'
    // },
};

/**
 * Holds configuration settings.
 */
class Configuration {
    getPath(name, namespace) {
        let scopedPaths = PATHS;

        if (namespace)
            if (namespace in PATHS)
                scopedPaths = PATHS[namespace];
            else
                throw new Error('Namespace '+ namespace +' does not exist');

        if (!('root' in scopedPaths))
            scopedPaths.root = __dirname;

        if (!name)
            return path.resolve(scopedPaths.root);
        if (name in scopedPaths)
            return path.resolve(scopedPaths.root, scopedPaths[name]);

        throw new Error('Path with name '+ name +' does not exist');
    }

    getHtmlPath(name) {
        return this.getPath(name, 'html');
    }

    getSassImports() {
        return SASS_IMPORTS;
    }

    getTextDomain() {
        let header = fs.readFileSync(path.resolve(global.config.getPath(), 'plugin.php')).toString();
        let matches = header.match(/Text Domain: ([\w-]+)\s*$/m);

        if (!matches || matches.length !== 2)
            throw new Error('Unable to determine textdomain');

        return matches[1];
    };

    getPluginName(slug = false) {
        let header = fs.readFileSync(path.resolve(global.config.getPath(), 'style.css')).toString();
        let matches = header.match(/Plugin Name: (.+?)\s*$/m);

        if (!matches || matches.length !== 2)
            throw new Error('Unable to determine theme name');

        return slug ? slugify(matches[1], {lower: true}) : matches[1];
    }
}
global.config = new Configuration();

/**
 * Styles
 */
function styles() {
    return src(global.config.getPath('styles') +'/**/*.scss')
        .pipe(sass({
            includePaths: global.config.getSassImports()
        }).on('error', sass.logError))
        .pipe(postcss([
            autoprefixer(),
            cssnano({preset: 'default'})
        ]))
        .pipe(dest(global.config.getPath('styles')));
}
exports.styles = styles;

/**
 * Scripts
 */
// const tsProject = ts.createProject('tsconfig.json');
// function scripts(done) {
// 	const typescripts = new FileSet(path.resolve(global.config.getPath('scripts'), '**/*.ts'));
// 	if (!typescripts.files.length) {
// 		log.info('No TypeScript scripts to compile');
// 		return done();
// 	}

//     return tsProject.src()
//         //.pipe(sourcemaps.init())
//         .pipe(tsProject())
//         //.pipe(sourcemaps.write('.'))
//         .pipe(dest(global.config.getPath('scripts')));
// }
// exports.scripts = scripts;

const blocksProject = ts.createProject('blocks/tsconfig.json');
function blockScripts(done) {
    return blocksProject.src()
        .pipe(blocksProject()).js
        .pipe(dest('blocks/latex-math/'));
}
exports.blockScripts = blockScripts;



/**
 * Watch for scripts or style changes and compile them.
 */
exports.watch = () => {
    // watch(global.config.getPath('styles') +'/**/*.scss', {ignoreInitial: false}, styles);
    // watch(global.config.getPath('scripts') +'/**/*.ts', {ignoreInitial: false}, scripts);
    watch('blocks/**/*.ts*', {ignoreInitial: false}, blockScripts);
};

/**
 * Default is to compile both styles and scripts
 */
exports.default = parallel(blockScripts);

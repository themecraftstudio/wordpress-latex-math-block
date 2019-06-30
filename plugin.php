<?php if (!defined('WPINC')) exit;
/*
 * Plugin Name:  LaTeX Math Block
 * Description:  Gutenberg block to display LaTeX math expressions
 * Version:      0.0.1
 * Author:       Themecraft Studio
 * Author URI:   https://themecraft.studio
 * License:      Proprietary
 * Text Domain:  tc-latex-math-block
 * Domain Path:  /languages
 * Requires WP:  5.0.0
 * Requires PHP: 7.2
 * GitHub Plugin URI: themecraftstudio/wordpress-latex-math-block
 */
require_once __DIR__ .'/vendor/autoload.php';

$p = Themecraft\WordPress\LatexMathBlock\LatexMathBlockPlugin::getInstance();

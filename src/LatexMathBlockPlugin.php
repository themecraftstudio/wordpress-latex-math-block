<?php

namespace Themecraft\WordPress\LatexMathBlock;

class LatexMathBlockPlugin extends WordPressPlugin
{
    protected function configure(): void
    {
        parent::configure();

        $this->registerExternalScript('katex', 'https://cdn.jsdelivr.net/npm/katex@0.10.2/dist/katex.js');
        $this->registerScript('latex-math-editor', 'blocks/latex-math/editor.js', ['wp-blocks', 'wp-element', 'wp-components', 'katex']);
        
        $this->registerExternalStyle('katex', 'https://cdn.jsdelivr.net/npm/katex@0.10.2/dist/katex.css');
        $this->registerStyle('latex-math-editor', 'blocks/latex-math/editor.css');
    }

    protected function bootstrap(): void
    {
        // actions 'enqueue_block_editor_assets', 'enqueue_block_assets'
        // 'enqueue_block_assets' fires for both editor (back end) and front end.
        // In the function call you supply, simply use wp_enqueue_script and wp_enqueue_style to add your functionality to the Gutenberg editor.

        add_action('init', function () {
            register_block_type('themecraft/latex-math', [
                'title' => 'LaTeX Math',
                'category' => 'formatting',
                'editor_script' => 'latex-math-editor',
                'editor_style' => 'latex-math-editor',
                'style' => 'katex', // front only
                'script' => 'katex', // front only
            ]);
        });

        parent::bootstrap();
    }
}

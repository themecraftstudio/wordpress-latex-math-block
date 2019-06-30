// https://developer.wordpress.org/block-editor/tutorials/format-api/3-apply-format/
// https://github.com/WordPress/gutenberg/tree/39f568faf2e62c57736bb80d7088a996eb067944/packages/rich-text
// Link https://github.com/WordPress/gutenberg/tree/39f568faf2e62c57736bb80d7088a996eb067944/packages/format-library/src/link
const { TextareaControl, CheckboxControl } = wp.components;
const { RawHTML } = wp.element;
const { InspectorControls } = wp.editor;
wp.blocks.registerBlockType('themecraft/latex-math', {
    title: 'LaTeX Math',
    icon: 'universal-access-alt',
    category: 'formatting',
    attributes: {
        // Ideally, the attributes saved should be included in the markup.
        // However, there are times when this is not practical, so if no attribute source is specified
        // the attribute is serialized and saved to the block’s comment delimiter.
        expression: {
            type: 'string',
        }
    },
    edit: function (props) {
        console.debug('Rendering via edit()');
        let attributes = props.attributes;
        let setAttributes = props.setAttributes;
        if (!attributes.expression) {
            return [
                wp.element.createElement("div", null,
                    wp.element.createElement("p", null, "Type a latex math expression below"),
                    wp.element.createElement(TextareaControl, { value: "", onChange: (v) => { setAttributes({ expression: v }); } }))
            ];
        }
        console.debug('LaTeX expression is defined!');
        let html = '';
        html = katex.renderToString(attributes.expression, {
            displayMode: true,
        });
        return [
            wp.element.createElement("div", { id: "katex" },
                wp.element.createElement(RawHTML, { children: html })),
            wp.element.createElement(InspectorControls, null,
                wp.element.createElement(CheckboxControl, { heading: "Checkbox Field", label: "tick me", help: "stuff", onChange: console.debug }))
        ];
    },
    save: function ({ attributes }) {
        // The save function should be a pure function that depends only on the attributes used to invoke it.
        let html = '';
        if (attributes.expression) {
            html = katex.renderToString(attributes.expression, {
                throwOnError: false,
                displayMode: true,
            });
        }
        return wp.element.createElement(RawHTML, { children: html });
    },
});

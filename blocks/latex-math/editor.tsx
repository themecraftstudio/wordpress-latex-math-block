/**
 * TODOs
 *  - capture click on expression / when block is selected and focus the textarea
 * 
 * References
 *  [1] https://developer.wordpress.org/block-editor/tutorials/format-api/3-apply-format/
 */

const { TextareaControl, CheckboxControl, Placeholder } = wp.components;
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
	edit(props) {
        let { attributes: { expression }, setAttributes } = props;
        const textArea = [
            <TextareaControl value={expression} onChange={(v) => { setAttributes({expression: v}) }} />,
            <div style={{textAlign: 'right', fontSize: '0.7em'}}>
                <a target="_blank" href="https://katex.org/docs/supported.html#symbols-and-punctuation">Supported functions and symbols</a>
            </div>
        ];

        if (!expression)
            expression = '\\text{Enter \\LaTeX\\ math expression...}';

        let renderedExpression = katex.renderToString(expression, {
            throwOnError: false,
            displayMode: true,
        });

		return [
            <div className="latex-math">
                <p className="rendered">
                    <RawHTML children={renderedExpression}/>
                </p>
                { props.isSelected && textArea }
            </div>,
            <InspectorControls>
                <CheckboxControl 
                    heading="Checkbox Field" 
                    label="tick me" 
                    help="stuff"
                    onChange={console.debug}
                />
            </InspectorControls>
		];
	},
	save: function( { attributes: {expression} } ) {
		// The save function should be a pure function that depends only on the attributes used to invoke it.
		let renderedExpression = '';

		if (expression) {
			renderedExpression = katex.renderToString(expression, {
				throwOnError: false,
				displayMode: true,
			});
		}

		return <RawHTML children={renderedExpression}></RawHTML>;
	},
});

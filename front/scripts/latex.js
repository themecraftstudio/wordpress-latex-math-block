jQuery(() => {
	for (const expression of document.querySelectorAll('span.latex-math')) {
		console.debug(expression.textContent);

		katex.render(expression.textContent, expression, {
			throwOnError: false,
		})
	}
});

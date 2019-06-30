import * as WPElement from '@wordpress/element';
import * as WPComponents from '@wordpress/components';

declare global {
	namespace wp {
        const element: typeof WPElement;
        const components: typeof WPComponents;
        const blocks: any;
        const editor: any;
	}
}
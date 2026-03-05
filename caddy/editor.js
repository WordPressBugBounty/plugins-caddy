/**
 * Caddy Cart Block Editor
 *
 * Block editor interface for the Caddy cart block.
 *
 * @since 2.1.3
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('caddy/cart', {
	edit: function(props) {
		const { attributes, setAttributes } = props;
		const { cartText, showIcon, autoOpen } = attributes;

		return [
			// Inspector Controls (Block Settings Sidebar)
			wp.element.createElement(InspectorControls, { key: 'inspector' },
				wp.element.createElement(PanelBody, {
					title: __('Cart Settings', 'caddy'),
					initialOpen: true
				},
					wp.element.createElement(TextControl, {
						label: __('Cart Button Text', 'caddy'),
						value: cartText,
						onChange: (value) => setAttributes({ cartText: value }),
						help: __('Text displayed on the cart trigger button', 'caddy')
					}),
					wp.element.createElement(ToggleControl, {
						label: __('Show Cart Icon', 'caddy'),
						checked: showIcon,
						onChange: (value) => setAttributes({ showIcon: value }),
						help: __('Display cart icon next to the button text', 'caddy')
					}),
					wp.element.createElement(ToggleControl, {
						label: __('Auto-open Cart', 'caddy'),
						checked: autoOpen,
						onChange: (value) => setAttributes({ autoOpen: value }),
						help: __('Automatically open cart when items are added', 'caddy')
					})
				)
			),

			// Block Preview in Editor
			wp.element.createElement('div', {
				key: 'preview',
				className: 'caddy-cart-block-preview',
				style: {
					padding: '20px',
					border: '2px dashed #ccc',
					borderRadius: '4px',
					textAlign: 'center',
					backgroundColor: '#f9f9f9'
				}
			},
				wp.element.createElement('div', {
					style: {
						display: 'inline-flex',
						alignItems: 'center',
						gap: '8px',
						padding: '10px 16px',
						backgroundColor: '#0073aa',
						color: 'white',
						borderRadius: '4px',
						fontSize: '14px'
					}
				},
					showIcon && wp.element.createElement('span', {
						style: { fontSize: '16px' }
					}, '🛒'),
					cartText && wp.element.createElement('span', null, cartText),
					wp.element.createElement('span', {
						style: {
							backgroundColor: 'rgba(255,255,255,0.2)',
							padding: '2px 6px',
							borderRadius: '10px',
							fontSize: '12px',
							marginLeft: '4px'
						}
					}, '0')
				),
				wp.element.createElement('p', {
					style: {
						marginTop: '12px',
						fontSize: '12px',
						color: '#666'
					}
				}, __('Caddy Smart Side Cart - This preview shows how the cart trigger will appear to visitors.', 'caddy'))
			)
		];
	},

	save: function() {
		// Return null since this is a dynamic block (rendered via PHP)
		return null;
	}
});
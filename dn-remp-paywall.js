(function (wp) {
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var SelectControl = wp.components.SelectControl;

	registerBlockType('remp-paywall/lock', {
		title: 'REMP LOCK',
		icon: 'lock',
		category: 'common',
		attributes: {
			blockValue: {
				type: 'string',
				source: 'meta',
				meta: '_dn_remp_paywall_access'
			}
		},
		edit: function (props) {
			var className = props.className;
			var setAttributes = props.setAttributes;

			function updateBlockValue(blockValue) {
				setAttributes({ blockValue });
			}

			return el(
				'div',
				{ className: className },
				el(SelectControl, {
					label: 'LOCK',
					help: window.dn_remp_paywall_error,
					value: props.attributes.blockValue,
					options: window.dn_remp_paywall_access,
					onChange: updateBlockValue
				})
			);
		},
		save: function () {
			return el('div', { id: 'remp_lock_anchor' }, el(wp.element.RawHTML, null));
		}
	});
})(window.wp);

window.dataLayer = window.dataLayer || [];
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
	'event': 'purchase',
	'ecommerce': {
		'transaction_id': dl.transaction_id,
		'value': dl.value,
		'tax': dl.tax,
		'shipping': dl.shipping,
		'currency': dl.currency,
		'items': JSON.parse(dl.items),
	}
});

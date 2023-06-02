window.dataLayer = window.dataLayer || [];
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
	event: 'purchase',
	ecommerce: {
		transaction_id: String(dl.transaction_id),
		value: Number(dl.value),
		tax: Number(dl.tax),
		shipping: Number(dl.shipping),
		currency: String(dl.currency),
		items: JSON.parse(dl.items),
	}
});

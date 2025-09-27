/* global WOM_Driver, wp */

(function () {
	function el(tag, attrs, children) {
		var node = document.createElement(tag);
		if (attrs) {
			Object.keys(attrs).forEach(function (k) {
				if (k === 'className') node.className = attrs[k];
				else if (k === 'text') node.textContent = attrs[k];
				else node.setAttribute(k, attrs[k]);
			});
		}
		(children || []).forEach(function (c) { if (c) node.appendChild(c); });
		return node;
	}

	function fetchOrders() {
		var url = WOM_Driver.root + '/driver/orders';
		return window.fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': WOM_Driver.nonce
			}
		}).then(function (r) { return r.json(); });
	}

	function updateOrderStatus(orderId, status) {
		var url = WOM_Driver.root + '/driver/orders/' + orderId + '/status';
		return window.fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': WOM_Driver.nonce
			},
			body: JSON.stringify({ status: status })
		}).then(function (r) { return r.json(); });
	}

	function render(rootId) {
		var root = document.getElementById(rootId);
		if (!root) return;
		root.innerHTML = '';

		var header = el('div', { className: 'wom-header' }, [
			el('h2', { text: 'My Deliveries' })
		]);
		var list = el('div', { className: 'wom-orders' });
		root.appendChild(header);
		root.appendChild(list);

		fetchOrders().then(function (orders) {
			list.innerHTML = '';
			if (!orders || !orders.length) {
				list.appendChild(el('div', { className: 'wom-empty', text: 'No assigned orders.' }));
				return;
			}
			orders.forEach(function (o) {
				var actions = el('div', { className: 'wom-actions' }, [
					button('En route', function () { doUpdate(o.id, 'en_route'); }),
					button('Delivered', function () { doUpdate(o.id, 'delivered'); }),
					button('Failed', function () { doUpdate(o.id, 'failed'); })
				]);
				var card = el('div', { className: 'wom-card' }, [
					el('div', { className: 'wom-line', text: 'Order #' + o.number + ' — ' + (o.driverStatus || 'assigned') }),
					el('div', { className: 'wom-line', text: (o.customer && o.customer.name) ? o.customer.name : '' }),
					el('div', { className: 'wom-line', text: address(o) }),
					actions
				]);
				list.appendChild(card);
			});
		});

		function doUpdate(orderId, status) {
			updateOrderStatus(orderId, status).then(function () { render(rootId); });
		}

		function address(o) {
			var s = o.shipping || {};
			return [s.address1, s.address2, s.city, s.postcode, s.country].filter(Boolean).join(', ');
		}

		function button(label, onClick) {
			var b = el('button', { className: 'button button-secondary', type: 'button', text: label });
			b.addEventListener('click', onClick);
			return b;
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		render('wom-driver-dashboard-root');
	});
})();


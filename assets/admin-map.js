(function(){
	function init(){
		var el = document.getElementById('wom-admin-map');
		if(!el || typeof L === 'undefined') return;
		var map = L.map(el).setView([51.505, -0.09], 11);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; OpenStreetMap contributors'
		}).addTo(map);

		fetch(WOM_AdminMap.root + '/admin/orders-for-map', {
			headers: { 'X-WP-Nonce': WOM_AdminMap.nonce },
			credentials: 'same-origin'
		}).then(function(r){ return r.json(); }).then(function(points){
			var bounds = [];
			(points || []).forEach(function(p){
				var marker = L.marker([p.lat, p.lng]).addTo(map);
				marker.bindPopup('<strong>Order #' + p.number + '</strong><br>' + (p.address||'') + '<br>Status: ' + p.status);
				bounds.push([p.lat, p.lng]);
			});
			if(bounds.length) map.fitBounds(bounds, { padding: [20,20] });
		});
	}
	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();


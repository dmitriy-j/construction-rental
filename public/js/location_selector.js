class LocationSelector {
    constructor(mapElementId) {
        this.map = L.map(mapElementId).setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

        this.markers = {
            equipment: null,
            construction: null
        };
    }

    addMarker(type, lat, lng) {
        if (this.markers[type]) {
            this.map.removeLayer(this.markers[type]);
        }

        this.markers[type] = L.marker([lat, lng], {
            draggable: true
        }).addTo(this.map)
          .bindPopup(`${type === 'equipment' ? 'Техника' : 'Объект'} расположена здесь`);

        this.markers[type].on('dragend', (e) => {
            this.updateCoordinates(type, e.target.getLatLng());
        });
    }

    updateCoordinates(type, latlng) {
        document.getElementById(`${type}_latitude`).value = latlng.lat;
        document.getElementById(`${type}_longitude`).value = latlng.lng;
    }

    geocodeAddress(address, type) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    this.addMarker(type, lat, lon);
                    this.map.setView([lat, lon], 15);
                }
            });
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.locationSelector = new LocationSelector('map');

    document.getElementById('equipment_location').addEventListener('change', function() {
        window.locationSelector.geocodeAddress(this.value, 'equipment');
    });

    document.getElementById('construction_site').addEventListener('change', function() {
        window.locationSelector.geocodeAddress(this.value, 'construction');
    });
});

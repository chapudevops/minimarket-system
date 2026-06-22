<div class="row mt-3">
    <div class="col-12 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                    <div class="">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-geo-alt-fill text-danger"></i>
                            {{ $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Nuestra Ubicación' }}
                        </h5>
                        @if($empresa->link_ubicacion)
                            <small class="text-success d-block mt-1">
                                <i class="bi bi-check-circle"></i> Ubicación configurada en Google Maps
                            </small>
                        @else
                            <small class="text-warning d-block mt-1">
                                <i class="bi bi-exclamation-triangle"></i> No hay ubicación configurada
                            </small>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="openGoogleMaps()">
                            <i class="bi bi-map"></i> Ver en Google Maps
                        </button>
                    </div>
                </div>
                <div id="storeMap" style="height: 400px; width: 100%; border-radius: 10px;"></div>
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> La ubicación se carga desde el link configurado en la empresa
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let map, marker, storeLink = null;
    let storeName = '{{ addslashes($empresa->nombre_comercial ?? $empresa->razon_social ?? "Mi Minimarket") }}';
    let storeLat = -12.046374, storeLng = -77.042793;

    window.openGoogleMaps = function() {
        if (storeLink) { window.open(storeLink, '_blank'); }
        else { alert('No hay un link de ubicación configurado. Ve a Configuración de Empresa para agregarlo.'); }
    };

    async function loadStoreLocation() {
        try {
            var r = await fetch('{{ route("store.location") }}');
            var d = await r.json();
            if (d && !d.error && d.link_ubicacion) {
                storeLink = d.link_ubicacion;
                if (d.lat && d.lng) { storeLat = parseFloat(d.lat); storeLng = parseFloat(d.lng); }
                if (map && marker) { map.setCenter({ lat: storeLat, lng: storeLng }); marker.setPosition({ lat: storeLat, lng: storeLng }); }
            } else {
                document.getElementById('storeMap').innerHTML = '<div class="alert alert-warning text-center p-5">⚠️ No hay ubicación configurada.</div>';
            }
        } catch (e) { console.error('Error:', e); }
    }

    function initMap() {
        var el = document.getElementById('storeMap');
        if (!el) return;
        map = new google.maps.Map(el, {
            center: { lat: storeLat, lng: storeLng },
            zoom: 17,
            zoomControl: true,
            streetViewControl: true,
            fullscreenControl: true,
            mapTypeControl: true
        });
        marker = new google.maps.Marker({
            position: { lat: storeLat, lng: storeLng },
            map: map,
            title: storeName,
            icon: { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png', scaledSize: new google.maps.Size(50, 50) }
        });
        var info = new google.maps.InfoWindow({
            content: '<div style="padding:12px;"><h6 style="margin:0;font-weight:bold;">' + storeName + '</h6><hr><button onclick="openGoogleMaps()" style="width:100%;padding:5px;background:#0d6efd;color:#fff;border:none;border-radius:5px;">📍 Ver en Google Maps</button></div>'
        });
        marker.addListener('click', function() { info.open(map, marker); });
    }

    function loadGoogleMapsScript() {
        var existing = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
        if (existing) existing.remove();
        var s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key={{ env("GOOGLE_MAPS_API_KEY") }}';
        s.async = true; s.defer = true;
        s.onload = function() {
            loadStoreLocation().then(initMap).catch(initMap);
        };
        s.onerror = function() {
            document.getElementById('storeMap').innerHTML = '<div class="alert alert-danger text-center p-5">Error al cargar el mapa.</div>';
        };
        document.head.appendChild(s);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleMapsScript);
    } else {
        loadGoogleMapsScript();
    }
</script>


'use strict';


class Map {

    defaults() {
        return {
            apiKey:  'pass key using constructor options',
            htmlIdentifier: 'map',
            maxZoom: 18,
            minZoom: 1,
            scrollWheelZoom: true,
            popups: true
        };
    }

    constructor(options) {
        this.apiKey = this.htmlIdentifier = this.maxZoom = null;
        this.minZoom = this.app = this.scrollWheelZoom = null;
        this.popups = true;

        if(typeof options == 'object')
            options = Object.assign(this.defaults(), options);
        else options = this.defaults();

        for(var key in options) {
            this[key] = options[key];
        }

        let corner1 = L.latLng(90, 360),
            corner2 = L.latLng(-90, -360),
            maxBounds = L.latLngBounds(corner1, corner2);

        this.map = L.map(this.htmlIdentifier, {
            worldCopyJump: true,
            maxZoom: this.maxZoom,
            minZoom: this.minZoom,
            scrollWheelZoom: this.scrollWheelZoom,
            maxBounds: maxBounds,
            maxBoundsViscosity: 1
        });

        L.tileLayer('https://api.mapbox.com/styles/v1/'
            + 'hashmich/ciqhed3uq001ae6niop4onov3/tiles/256/{z}/{x}/{y}?access_token='
            + this.apiKey).addTo(this.map);

        window.addEventListener('resize', function () {
            this.map.invalidateSize();
            if(this.app.action == 'index')
                this.fitBounds();
        }.bind(this));

        // markers is set as a lookup table to get a single course record by ID
        this.markers = {};
        this.id = false;
        if(this.popups) this.addHandlers();
    }

    addHandlers() {
        // map popup handlers
        $(document).on('click', '#map .show_view', function(e) {
            e.preventDefault();
            let id = $(e.target).attr('data-id');
            this.app.setCourse(id);
        }.bind(this));
        $(document).on('click', '#map .show_table', function(e) {
            this.app.slider.setPosition('table');
        }.bind(this));
    }

    setMarkers(courses) {
        this.markers = {};
        this.cluster = new L.MarkerClusterGroup({
            spiderfyOnMaxZoom: true,
            disableClusteringAtZoom: this.maxZoom,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50,
            iconCreateFunction: function(cluster) {
                let childCount = cluster.getChildCount();
                let c = ' marker-cluster-';
                let size = 40;
                if (childCount < 10) {
                    c += 'small';
                    size = 40;
                } else if (childCount < 100) {
                    c += 'medium';
                    size = 50;
                } else {
                    c += 'large';
                    size = 60
                }
                return new L.DivIcon({
                    html: '<div><span>' + childCount + '</span></div>',
                    className: 'marker-cluster' + c,
                    iconSize: new L.Point(size, size)
                });
            }
        });
        let helper = new ViewHelper();
        for(let k in courses) {
            let course = courses[k];
            let icon = L.icon({
                iconUrl: BASE_URL + 'leaflet/images/dhcr-marker-icon.png',
                iconRetinaUrl: BASE_URL + 'leaflet/images/dhcr-marker-icon-2x.png',
                iconSize:     [25, 37], // size of the icon
                iconAnchor:   [12, 37], // point of the icon which will correspond to marker's location
                popupAnchor:  [0, -40] // point from which the popup should open relative to the iconAnchor
            });
            let marker = L.marker([course.lat, course.lon], {
                title: course.name,
                icon: icon,
                // autoPanPadding doesn't seem to have any effect
                autoPanPaddingTopLeft: [20,20],
                autoPanPaddingBottomRight: [20,100],
                closeButton: false
            });

            if(this.popups) {
                // prepare html content
                marker.bindPopup(helper.createPopup(course));
                marker.on('click', function(e) {
                    this.app.hash.push(course.id);
                    if(this.app.view.openRow(course.id))
                        this.app.view.scrollToRow(course.id);
                }.bind(this));
            }

            this.cluster.addLayer(marker);
            this.markers[course.id] = marker;
        }

        this.map.addLayer(this.cluster);
        this.fitBounds();
        if(Object.entries(this.app.filter).length > 0 && !this.app.filter.isLocated() && this.popups) {
            let zoom = this.map.getZoom();
            // locate to user location
            this.map.locate({setView: true, maxZoom: zoom});
            this.map.on('locationfound', function() {
                this.map.stopLocate();
            }.bind(this));
        }
    }

    openMarker(id) {
        this.cluster.zoomToShowLayer(this.markers[id], function() {
            this.markers[id].openPopup();
            this.id = id;
        }.bind(this));
    }

    closeMarker() {
        if(!this.id) return;
        this.markers[this.id].closePopup();
        let zoom = this.map.getZoom();
        let delta = 0;
        if(zoom >= 5) delta = 3;
        if(zoom >= 10) delta = 5;
        if(zoom >= 15) delta = 10
        this.map.zoomOut(delta);
        this.id = false;
    }

    fitBounds() {
        this.map.options.maxZoom = 12;
        this.map.options.minZoom = 2;
        this.map.fitBounds(this.cluster.getBounds(), {padding: [10, 10]});
        this.map.options.minZoom = this.minZoom;
        this.map.options.maxZoom = this.maxZoom;
    }

}

/**
 * MRDS Map - Carte MapLibre GL
 * Style bleu foncé #141B42 - Exactement comme la maquette
 */

(function () {
  "use strict";

  const CONFIG = window.MRDS_Map_Config || {
    ajax_url: "/wp-admin/admin-ajax.php",
    nonce: "",
    theme_url: "",
    default_center: [2.3522, 48.8566],
    default_zoom: 14,
    colors: {
      primary: "#141B42",
      accent: "#DA9D42",
      light: "#FFFFFF",
    },
  };

  const CUSTOM_STYLE = {
    version: 8,
    name: "MRDS Dark Blue",
    sprite:
      "https://api.maptiler.com/maps/basic-v2/sprite?key=TAYaAg3duAcdXiTBzNnG",
    sources: {
      osm: {
        type: "vector",
        url: "https://api.maptiler.com/tiles/v3/tiles.json?key=TAYaAg3duAcdXiTBzNnG",
      },
    },
    glyphs:
      "https://api.maptiler.com/fonts/{fontstack}/{range}.pbf?key=TAYaAg3duAcdXiTBzNnG",
    layers: [
      {
        id: "background",
        type: "background",
        paint: { "background-color": "#1E2A5E" },
      },
      {
        id: "landuse-park",
        type: "fill",
        source: "osm",
        "source-layer": "landuse",
        filter: ["in", "class", "park", "grass", "cemetery"],
        paint: { "fill-color": "#243366", "fill-opacity": 0.7 },
      },
      {
        id: "water",
        type: "fill",
        source: "osm",
        "source-layer": "water",
        paint: { "fill-color": "#3D5A9E", "fill-opacity": 1 },
      },
      {
        id: "waterway",
        type: "line",
        source: "osm",
        "source-layer": "waterway",
        paint: { "line-color": "#3D5A9E", "line-width": 2.5 },
      },
      {
        id: "building",
        type: "fill",
        source: "osm",
        "source-layer": "building",
        paint: { "fill-color": "#232F5C", "fill-opacity": 0.6 },
      },
      {
        id: "road-tertiary",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["in", "class", "tertiary", "residential", "service", "minor"],
        layout: { "line-cap": "round", "line-join": "round" },
        paint: {
          "line-color": "rgba(255, 255, 255, 0.25)",
          "line-width": [
            "interpolate",
            ["linear"],
            ["zoom"],
            10,
            0.5,
            14,
            2,
            18,
            6,
          ],
        },
      },
      {
        id: "road-secondary",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["==", "class", "secondary"],
        layout: { "line-cap": "round", "line-join": "round" },
        paint: {
          "line-color": "rgba(255, 255, 255, 0.4)",
          "line-width": [
            "interpolate",
            ["linear"],
            ["zoom"],
            10,
            1,
            14,
            3,
            18,
            10,
          ],
        },
      },
      {
        id: "road-primary",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["==", "class", "primary"],
        layout: { "line-cap": "round", "line-join": "round" },
        paint: {
          "line-color": "rgba(255, 255, 255, 0.55)",
          "line-width": [
            "interpolate",
            ["linear"],
            ["zoom"],
            10,
            1.5,
            14,
            4,
            18,
            14,
          ],
        },
      },
      {
        id: "road-motorway",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["in", "class", "motorway", "trunk"],
        layout: { "line-cap": "round", "line-join": "round" },
        paint: {
          "line-color": "rgba(255, 255, 255, 0.7)",
          "line-width": [
            "interpolate",
            ["linear"],
            ["zoom"],
            8,
            1,
            12,
            3,
            16,
            8,
            20,
            16,
          ],
        },
      },
      {
        id: "railway",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["==", "class", "rail"],
        paint: {
          "line-color": "rgba(255, 255, 255, 0.18)",
          "line-width": 1.5,
          "line-dasharray": [3, 3],
        },
      },
      {
        id: "bridge",
        type: "line",
        source: "osm",
        "source-layer": "transportation",
        filter: ["==", "brunnel", "bridge"],
        layout: { "line-cap": "round", "line-join": "round" },
        paint: {
          "line-color": "rgba(255, 255, 255, 0.6)",
          "line-width": ["interpolate", ["linear"], ["zoom"], 12, 2, 16, 6],
        },
      },
      {
        id: "road-labels",
        type: "symbol",
        source: "osm",
        "source-layer": "transportation_name",
        layout: {
          "text-field": ["get", "name"],
          "text-font": ["Open Sans Regular"],
          "text-size": ["interpolate", ["linear"], ["zoom"], 12, 14, 16, 16],
          "text-max-angle": 30,
          "symbol-placement": "line",
          "text-padding": 5,
        },
        paint: {
          "text-color": "rgba(255, 255, 255, 0.7)",
          "text-halo-color": "#141B42",
          "text-halo-width": 1.5,
        },
      },
      // {
      //   id: "poi-labels",
      //   type: "symbol",
      //   source: "osm",
      //   "source-layer": "poi",
      //   minzoom: 14,
      //   layout: {
      //     "icon-image": ["get", "class"],
      //     "icon-size": 0.8,
      //     "icon-allow-overlap": false,
      //     "text-field": ["get", "name"],
      //     "text-font": ["Open Sans Regular"],
      //     "text-size": 11,
      //     "text-anchor": "top",
      //     "text-offset": [0, 1.2],
      //   },
      //   paint: {
      //     "text-color": "rgba(255, 255, 255, 0.8)",
      //     "text-halo-color": "#141B42",
      //     "text-halo-width": 1.5,
      //   },
      // },
      {
        id: "place-labels",
        type: "symbol",
        source: "osm",
        "source-layer": "place",
        layout: {
          "text-field": ["get", "name"],
          "text-font": ["Open Sans Bold"],
          "text-size": ["interpolate", ["linear"], ["zoom"], 10, 11, 14, 14],
          "text-anchor": "center",
        },
        paint: {
          "text-color": "rgba(255, 255, 255, 0.9)",
          "text-halo-color": "#141B42",
          "text-halo-width": 2,
        },
      },
    ],
  };

  class MRDSMap {
    constructor(container) {
      this.container = container;
      this.mapId = container.id;
      this.map = null;
      this.markers = [];
      this.popups = [];

      this.options = {
        zoom: parseInt(container.dataset.zoom) || CONFIG.default_zoom,
        centerLat: parseFloat(container.dataset.centerLat) || 48.8566,
        centerLng: parseFloat(container.dataset.centerLng) || 2.3522,
        clickable: container.dataset.clickable !== "false",
        restaurants: [],
      };

      try {
        this.options.restaurants = JSON.parse(
          container.dataset.restaurants || "[]",
        );
      } catch (e) {
        console.warn("MRDS Map: Erreur parsing restaurants", e);
      }

      this.init();
    }

    init() {
      this.map = new maplibregl.Map({
        container: this.mapId,
        style: CUSTOM_STYLE,
        center: [this.options.centerLng, this.options.centerLat],
        zoom: this.options.zoom,
        attributionControl: true,
      });

      this.map.addControl(
        new maplibregl.NavigationControl({ showCompass: false }),
        "bottom-right",
      );

      this.map.on("load", () => {
        this.addMarkers(this.options.restaurants);
      });

      this.map.on("resize", () => {
        this.map.resize();
      });
    }

    createMarkerElement(restaurant) {
      const el = document.createElement("div");
      el.className = "mrds-marker";
      el.dataset.id = restaurant.id;
      el.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="39" height="58" viewBox="0 0 39.034 57.783">
          <path d="M19.517,0A19.545,19.545,0,0,1,39.034,19.517c0,9.528-4.918,14.753-10.143,23.205l-9.374,15.06-9.374-15.06C4.918,34.27,0,29.045,0,19.517A19.545,19.545,0,0,1,19.517,0m0,7.991A10.757,10.757,0,1,1,8.76,18.749,10.789,10.789,0,0,1,19.517,7.991" fill="#DA9D42" fill-rule="evenodd"/>
        </svg>`;
      return el;
    }

    addMarkers(restaurants) {
      this.markers.forEach((m) => m.remove());
      this.markers = [];
      this.popups.forEach((p) => p.remove());
      this.popups = [];

      restaurants.forEach((restaurant) => {
        const el = this.createMarkerElement(restaurant);

        const marker = new maplibregl.Marker({ element: el, anchor: "bottom" })
          .setLngLat([restaurant.lng, restaurant.lat])
          .addTo(this.map);

        const popup = new maplibregl.Popup({
          offset: [0, -58],
          className: "mrds-popup",
          closeButton: false,
          closeOnClick: false,
          maxWidth: "280px",
        }).setHTML(this.createPopupContent(restaurant));

        this.popups.push(popup);

        let hoverTimer = null;

        el.addEventListener("mouseenter", () => {
          if (hoverTimer) clearTimeout(hoverTimer);
          this.popups.forEach((p) => p.remove());
          popup.setLngLat([restaurant.lng, restaurant.lat]).addTo(this.map);
          const popupEl = popup.getElement();
          if (popupEl) {
            popupEl.addEventListener("mouseenter", () => {
              if (hoverTimer) clearTimeout(hoverTimer);
            });
            popupEl.addEventListener("mouseleave", () => {
              hoverTimer = setTimeout(() => popup.remove(), 150);
            });
          }
        });

        el.addEventListener("mouseleave", () => {
          hoverTimer = setTimeout(() => popup.remove(), 150);
        });

        if (this.options.clickable) {
          el.style.cursor = "pointer";
          el.addEventListener("click", (e) => {
            e.stopPropagation();
            window.location.href = restaurant.link;
          });
        }

        this.markers.push(marker);
        marker._restaurantData = restaurant;
      });
    }

    createPopupContent(restaurant) {
      const tagsHtml = (restaurant.tags || [])
        .map((t) => `<span class="tag">${t}</span>`)
        .join("");

      const cuisinesHtml = (restaurant.cuisines || [])
        .map((c) => `<span class="tag tag-type-cuisine">${c}</span>`)
        .join("");

      const allTags = tagsHtml + cuisinesHtml;

      const rawAddress = restaurant.address || restaurant.location || "";
      const address = rawAddress.replace(/,?\s*France$/i, "").trim();

      return `
    <div class="mrds-popup-content">
      <h3 class="popup-title">${restaurant.title}</h3>
      ${address ? `<p class="popup-address">${address}</p>` : ""}
      ${allTags ? `<div class="popup-tags">${allTags}</div>` : ""}
      <a href="${restaurant.link}" class="popup-link">Voir le restaurant →</a>
    </div>`;
    }
    updateMarkers(restaurants) {
      this.options.restaurants = restaurants;
      this.addMarkers(restaurants);
      if (restaurants.length > 0) {
        this.fitToMarkers();
      }
    }
    centerOnMarkers(restaurants) {
      const bounds = new maplibregl.LngLatBounds();
      restaurants.forEach((r) => bounds.extend([r.lng, r.lat]));
      this.map.fitBounds(bounds, {
        padding: 80,
      });
    }

    fitToMarkers() {
      if (this.markers.length === 0) return;
      if (this.markers.length === 1) {
        const data = this.markers[0]._restaurantData;
        this.map.flyTo({ center: [data.lng, data.lat], zoom: 14 });
      } else {
        const bounds = new maplibregl.LngLatBounds();
        this.markers.forEach((marker) => {
          const data = marker._restaurantData;
          bounds.extend([data.lng, data.lat]);
        });
        this.map.fitBounds(bounds, { padding: 80, maxZoom: 12 });
      }
    }

    focusRestaurant(restaurantId) {
      const marker = this.markers.find(
        (m) => m._restaurantData.id === restaurantId,
      );
      if (marker) {
        const data = marker._restaurantData;
        this.map.flyTo({ center: [data.lng, data.lat], zoom: 14 });
        marker.togglePopup();
      }
    }

    async loadRestaurants(filters = {}) {
      try {
        const formData = new FormData();
        formData.append("action", "mrds_get_restaurants");
        formData.append("nonce", CONFIG.nonce);
        Object.keys(filters).forEach((key) => {
          formData.append(key, filters[key]);
        });

        const response = await fetch(CONFIG.ajax_url, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          this.updateMarkers(result.data);
          return result.data;
        }
        return [];
      } catch (error) {
        console.error("MRDS Map: Erreur AJAX", error);
        return [];
      }
    }

    getMap() {
      return this.map;
    }

    destroy() {
      if (this.map) {
        this.map.remove();
        this.map = null;
      }
    }
  }

  const MapManager = {
    instances: new Map(),

    initAll() {
      const maps = document.querySelectorAll(".mrds-map");
      console.log("MRDS Map: Trouvé", maps.length, "carte(s)");
      maps.forEach((container) => {
        if (!this.instances.has(container.id)) {
          this.instances.set(container.id, new MRDSMap(container));
        }
      });
    },

    get(mapId) {
      return this.instances.get(mapId);
    },

    create(containerId, options = {}) {
      const container = document.getElementById(containerId);
      if (!container) {
        console.error(`MRDS Map: Container #${containerId} non trouvé`);
        return null;
      }
      Object.keys(options).forEach((key) => {
        const dataKey = key.replace(/([A-Z])/g, "-$1").toLowerCase();
        container.dataset[dataKey] =
          typeof options[key] === "object"
            ? JSON.stringify(options[key])
            : options[key];
      });
      const instance = new MRDSMap(container);
      this.instances.set(containerId, instance);
      return instance;
    },

    destroyAll() {
      this.instances.forEach((instance) => instance.destroy());
      this.instances.clear();
    },
  };

  if (typeof maplibregl !== "undefined") {
    document.addEventListener("DOMContentLoaded", () => {
      MapManager.initAll();
    });
  } else {
    console.warn("MRDS Map: MapLibre GL non chargé");
  }

  window.MRDS_Map = MapManager;
})();

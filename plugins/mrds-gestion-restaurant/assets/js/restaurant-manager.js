document.addEventListener("DOMContentLoaded", function () {
  if (!window.MRDSRestaurantConfig) return;

  const apiUrl = MRDSRestaurantConfig.restUrl;
  const nonce = MRDSRestaurantConfig.nonce;

  // Elements
  const rowsTbody = document.getElementById("mrds-restaurants-rows");
  const form = document.getElementById("mrds-restaurant-form");
  const formTitle = document.getElementById("mrds-restaurant-form-title");
  const msgBox = document.getElementById("mrds-restaurant-message");

  const btnNew = document.getElementById("mrds-restaurant-new-btn");
  const btnReset = document.getElementById("mrds-restaurant-reset-btn");
  const btnAddTarif = document.getElementById("mrds-add-tarif-btn");
  const tarifsContainer = document.getElementById("mrds-tarifs-container");
  const horairesContainer = document.getElementById("mrds-horaires-container");

  // Champs
  const fieldId = document.getElementById("mrds-restaurant-id");
  const fieldTitle = document.getElementById("mrds-restaurant-title");

  const fieldOwner = document.getElementById("mrds-restaurant-owner");
  const fieldRestaurateurs = document.getElementById(
    "mrds-restaurant-restaurateurs",
  );

  const fieldAdrRue = document.getElementById("mrds-adresse-rue");
  const fieldAdrComp = document.getElementById("mrds-adresse-complement");
  const fieldCP = document.getElementById("mrds-code-postal");
  const fieldVille = document.getElementById("mrds-ville");
  const fieldArr = document.getElementById("mrds-arrondissement");

  const fieldTel = document.getElementById("mrds-telephone");
  const fieldSiteWeb = document.getElementById("mrds-site-web");

  const fieldTypeCuisine = document.getElementById("mrds-type-cuisine");
  const fieldTagsRest = document.getElementById("mrds-tags-restaurant");

  const fieldDescMenu = document.getElementById("mrds-description-menu");
  const fieldExemplePlats = document.getElementById("mrds-exemple-plats");
  const fieldCitationDesc = document.getElementById(
    "mrds-citation-description",
  );
  const fieldCitationAut = document.getElementById("mrds-citation-auteur");

  // Champs image principale
  const fieldImage = document.getElementById("mrds-restaurant-image");
  const imagePreview = document.getElementById("mrds-image-preview");
  const imagePreviewImg = imagePreview
    ? imagePreview.querySelector("img")
    : null;
  const btnImageRemove = document.getElementById("mrds-image-remove");
  const imageUploadStatus = document.getElementById("mrds-image-upload-status");

  // Champs galerie
  const galleryInput = document.getElementById("mrds-gallery-input");
  const galleryPreview = document.getElementById("mrds-gallery-preview");
  const galleryUploadStatus = document.getElementById(
    "mrds-gallery-upload-status",
  );

  let currentThumbnailId = null;
  let currentGallery = []; // [{id, url}, ...]

  if (!rowsTbody || !form) return;

  // =====================
  // HELPERS
  // =====================
  function showMessage(text, type) {
    if (!type) type = "success";
    msgBox.textContent = text;
    msgBox.className = "";
    msgBox.classList.add(type === "error" ? "text-danger" : "text-success");
    if (text) {
      setTimeout(function () {
        msgBox.textContent = "";
      }, 4000);
    }
  }

  // =====================
  // IMAGE PRINCIPALE
  // =====================
  function showImagePreview(url) {
    if (url && imagePreview && imagePreviewImg) {
      imagePreviewImg.src = url;
      imagePreview.style.display = "inline-block";
    }
  }

  function hideImagePreview() {
    if (imagePreview) {
      imagePreview.style.display = "none";
      if (imagePreviewImg) imagePreviewImg.src = "";
    }
    currentThumbnailId = null;
    if (imageUploadStatus) imageUploadStatus.textContent = "";
  }

  function uploadImage(restaurantId, file) {
    return new Promise(function (resolve, reject) {
      var formData = new FormData();
      formData.append("image", file);

      if (imageUploadStatus) {
        imageUploadStatus.textContent = "Upload en cours...";
        imageUploadStatus.className = "small mt-1 text-info";
      }

      fetch(apiUrl + "/" + restaurantId + "/image", {
        method: "POST",
        headers: {
          "X-WP-Nonce": nonce,
        },
        body: formData,
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            if (imageUploadStatus) {
              imageUploadStatus.textContent = "Image uploadee !";
              imageUploadStatus.className = "small mt-1 text-success";
            }
            currentThumbnailId = data.attachment_id;
            showImagePreview(data.url);
            resolve(data);
          } else {
            throw new Error(data.message || "Erreur upload");
          }
        })
        .catch(function (err) {
          if (imageUploadStatus) {
            imageUploadStatus.textContent = "Erreur: " + err.message;
            imageUploadStatus.className = "small mt-1 text-danger";
          }
          reject(err);
        });
    });
  }

  // =====================
  // GALERIE
  // =====================
  function renderGalleryPreview() {
    galleryPreview.innerHTML = "";

    currentGallery.forEach(function (img) {
      var item = document.createElement("div");
      item.className = "mrds-gallery-item";

      var imgEl = document.createElement("img");
      imgEl.src = img.url;
      imgEl.alt = "Gallery image";

      var btnRemove = document.createElement("button");
      btnRemove.type = "button";
      btnRemove.className = "mrds-gallery-remove-btn";
      btnRemove.innerHTML = '<i class="fa-solid fa-times"></i>';
      btnRemove.addEventListener("click", function () {
        deleteGalleryImage(img.id);
      });

      item.appendChild(imgEl);
      item.appendChild(btnRemove);
      galleryPreview.appendChild(item);
    });

    // Desactiver input si max atteint
    if (galleryInput) {
      galleryInput.disabled = currentGallery.length >= 4;
    }
  }

  function uploadGalleryImage(restaurantId, file) {
    return new Promise(function (resolve, reject) {
      if (currentGallery.length >= 4) {
        reject(new Error("Maximum 4 images"));
        return;
      }

      var formData = new FormData();
      formData.append("image", file);

      if (galleryUploadStatus) {
        galleryUploadStatus.textContent = "Upload en cours...";
        galleryUploadStatus.className = "small mt-1 text-info";
      }

      fetch(apiUrl + "/" + restaurantId + "/gallery", {
        method: "POST",
        headers: {
          "X-WP-Nonce": nonce,
        },
        body: formData,
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            if (galleryUploadStatus) {
              galleryUploadStatus.textContent = "Image ajoutee !";
              galleryUploadStatus.className = "small mt-1 text-success";
            }
            currentGallery.push({
              id: data.attachment_id,
              url: data.url,
            });
            renderGalleryPreview();
            resolve(data);
          } else {
            throw new Error(data.message || "Erreur upload galerie");
          }
        })
        .catch(function (err) {
          if (galleryUploadStatus) {
            galleryUploadStatus.textContent = "Erreur: " + err.message;
            galleryUploadStatus.className = "small mt-1 text-danger";
          }
          reject(err);
        });
    });
  }

  function deleteGalleryImage(imageId) {
    var restaurantId = fieldId.value;
    if (!restaurantId) {
      // Juste retirer du tableau local si pas encore sauvegarde
      currentGallery = currentGallery.filter(function (img) {
        return img.id !== imageId;
      });
      renderGalleryPreview();
      return;
    }

    fetch(apiUrl + "/" + restaurantId + "/gallery/" + imageId, {
      method: "DELETE",
      headers: {
        "X-WP-Nonce": nonce,
        "Content-Type": "application/json",
      },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          currentGallery = currentGallery.filter(function (img) {
            return img.id !== imageId;
          });
          renderGalleryPreview();
          showMessage("Image supprimee de la galerie");
        }
      })
      .catch(function (err) {
        console.error(err);
        showMessage("Erreur lors de la suppression", "error");
      });
  }

  // =====================
  // FORM
  // =====================
  function resetForm() {
    fieldId.value = "";
    fieldTitle.value = "";

    if (fieldOwner) fieldOwner.value = "";

    if (fieldRestaurateurs) {
      Array.from(fieldRestaurateurs.options).forEach(function (o) {
        o.selected = false;
      });
    }

    fieldAdrRue.value = "";
    fieldAdrComp.value = "";
    fieldCP.value = "";
    fieldVille.value = "";
    fieldArr.value = "";

    fieldTel.value = "";
    fieldSiteWeb.value = "";

    if (fieldTypeCuisine) {
      Array.from(fieldTypeCuisine.options).forEach(function (o) {
        o.selected = false;
      });
    }
    if (fieldTagsRest) {
      Array.from(fieldTagsRest.options).forEach(function (o) {
        o.selected = false;
      });
    }

    fieldDescMenu.value = "";
    fieldExemplePlats.value = "";
    if (fieldCitationDesc) fieldCitationDesc.innerHTML = "";
    fieldCitationAut.value = "";

    tarifsContainer.innerHTML = "";
    horairesContainer.innerHTML = "";

    // Reset image principale
    if (fieldImage) fieldImage.value = "";
    hideImagePreview();

    // Reset galerie
    if (galleryInput) galleryInput.value = "";
    currentGallery = [];
    renderGalleryPreview();
    if (galleryUploadStatus) galleryUploadStatus.textContent = "";

    // Cacher le formulaire si non-admin
    var formCard = document.getElementById("mrds-restaurant-form-card");
    if (formCard && !MRDSRestaurantConfig.isAdmin) {
      formCard.style.display = "none";
    }

    formTitle.textContent = "Ajouter un restaurant";
  }

  function addTarifRow(nom, prix) {
    if (!nom) nom = "";
    if (!prix) prix = "";

    var row = document.createElement("div");
    row.className = "mrds-tarif-row";

    var inputNom = document.createElement("input");
    inputNom.type = "text";
inputNom.placeholder = "Libellé (ex: Entrées, Menu midi…)";
    inputNom.className = "form-control mrds-tarif-nom";
    inputNom.value = nom;

var inputPrix = document.createElement("input");
inputPrix.type = "text";
inputPrix.placeholder = "Ex: 25€ ou de 20 à 30€";
inputPrix.className = "form-control mrds-tarif-prix";
inputPrix.value = prix;

    var btnRemove = document.createElement("button");
    btnRemove.type = "button";
    btnRemove.className = "btn btn-sm btn-outline-danger";
    btnRemove.textContent = "X";
    btnRemove.addEventListener("click", function () {
      tarifsContainer.removeChild(row);
    });

    row.appendChild(inputNom);
    row.appendChild(inputPrix);
    row.appendChild(btnRemove);

    tarifsContainer.appendChild(row);
  }

  function addHoraireRow(periode, jours) {
    if (!periode) periode = "";
    if (!jours) jours = [];

    var row = document.createElement("div");
    row.className = "mrds-horaire-row";

    var selectPeriode = document.createElement("select");
    selectPeriode.className = "form-select mrds-horaire-periode";
    ["", "Matin", "Midi", "Soir"].forEach(function (val) {
      var opt = document.createElement("option");
      opt.value = val;
      opt.textContent = val === "" ? "Periode" : val;
      selectPeriode.appendChild(opt);
    });
    if (periode) selectPeriode.value = periode;

    var selectJours = document.createElement("select");
    selectJours.className = "form-select mrds-horaire-jours";
    selectJours.multiple = true;
    var joursChoices = {
      L: "Lundi",
      Mar: "Mardi",
      Mer: "Mercredi",
      J: "Jeudi",
      V: "Vendredi",
      S: "Samedi",
      D: "Dimanche",
    };
    for (var code in joursChoices) {
      var opt = document.createElement("option");
      opt.value = code;
      opt.textContent = joursChoices[code];
      if (jours && jours.indexOf(code) !== -1) {
        opt.selected = true;
      }
      selectJours.appendChild(opt);
    }

    var btnRemove = document.createElement("button");
    btnRemove.type = "button";
    btnRemove.className = "btn btn-sm btn-outline-danger";
    btnRemove.textContent = "X";
    btnRemove.addEventListener("click", function () {
      horairesContainer.removeChild(row);
    });

    row.appendChild(selectPeriode);
    row.appendChild(selectJours);
    row.appendChild(btnRemove);

    horairesContainer.appendChild(row);
  }

  // =====================
  // REST
  // =====================
  function fetchRestaurants() {
    fetch(apiUrl, {
      method: "GET",
      headers: {
        "X-WP-Nonce": nonce,
        "Content-Type": "application/json",
      },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        renderRestaurants(data);
      })
      .catch(function (err) {
        console.error(err);
        showMessage("Erreur lors du chargement des restaurants", "error");
      });
  }

  function renderRestaurants(restaurants) {
    rowsTbody.innerHTML = "";

    if (!restaurants || restaurants.length === 0) {
      rowsTbody.innerHTML =
        '<tr><td colspan="6" class="text-center py-3">Aucun restaurant pour le moment.</td></tr>';
      return;
    }

    restaurants.forEach(function (r) {
      var tr = document.createElement("tr");

      var tdNom = document.createElement("td");
      tdNom.innerHTML =
        '<a href="' + r.lien_restaurant + '">' + (r.title || "") + "</a>";

      var tdAdr = document.createElement("td");
      var adrParts = [];
      if (r.adresse_rue) adrParts.push(r.adresse_rue);
      if (r.code_postal || r.ville)
        adrParts.push((r.code_postal || "") + " " + (r.ville || ""));
      //if (r.arrondissement) adrParts.push("Arr. " + r.arrondissement);
      tdAdr.textContent = adrParts.join(", ");

      var tdTypeCuisine = document.createElement("td");
      if (
        Array.isArray(r.type_cuisine_labels) &&
        r.type_cuisine_labels.length
      ) {
        r.type_cuisine_labels.forEach(function (label) {
          var span = document.createElement("span");
          span.className = "mrds-badge";
          span.textContent = label;
          tdTypeCuisine.appendChild(span);
        });
      } else {
        tdTypeCuisine.textContent = "—";
      }

      var tdTags = document.createElement("td");
      if (Array.isArray(r.tags_labels) && r.tags_labels.length) {
        r.tags_labels.forEach(function (label) {
          var span = document.createElement("span");
          span.className = "mrds-badge";
          span.textContent = label;
          tdTags.appendChild(span);
        });
      } else {
        tdTags.textContent = "—";
      }

      var tdOwner = document.createElement("td");
      if (r.owner && r.owner.display_name) {
        tdOwner.textContent = r.owner.display_name;
      } else {
        tdOwner.textContent = "—";
      }

      var tdActions = document.createElement("td");
      tdActions.className = "text-end";

      var btnEdit = document.createElement("button");
      btnEdit.type = "button";
      btnEdit.className = "btn btn-sm btn-outline-primary";
      btnEdit.innerHTML = '<i class="fa-solid fa-pen"></i>';
      btnEdit.setAttribute("data-bs-toggle", "tooltip");
      btnEdit.setAttribute("data-bs-placement", "top");
      btnEdit.setAttribute("data-bs-title", "Modifier le restaurant");
      btnEdit.addEventListener("click", function () {
        fillForm(r);
      });

      var btnremise = document.createElement("button");
      btnremise.type = "button";
      btnremise.className = "btn btn-sm btn-outline-primary";
      btnremise.innerHTML = '<i class="fa-solid fa-tags"></i>';
      btnremise.setAttribute("data-bs-toggle", "tooltip");
      btnremise.setAttribute("data-bs-placement", "top");
      btnremise.setAttribute("data-bs-title", "Gestion des remises");
      btnremise.addEventListener("click", function () {
        document.location.href = r.lien_remises;
      });

      var btnDelete = document.createElement("button");
      btnDelete.type = "button";
      btnDelete.className = "btn btn-sm btn-outline-danger";
      btnDelete.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
      btnDelete.setAttribute("data-bs-toggle", "tooltip");
      btnDelete.setAttribute("data-bs-placement", "top");
      btnDelete.setAttribute("data-bs-title", "Supprimer le restaurant");
      btnDelete.addEventListener("click", function () {
        if (confirm("Supprimer ce restaurant ?")) {
          deleteRestaurant(r.id);
        }
      });

      var divactions = document.createElement("div");
      divactions.className = "d-flex gap-1 justify-content-center";

      divactions.appendChild(btnremise);
      divactions.appendChild(btnEdit);
      if (MRDSRestaurantConfig.isAdmin) {
        divactions.appendChild(btnDelete);
      }
      tdActions.appendChild(divactions);
      tr.appendChild(tdNom);
      tr.appendChild(tdAdr);
      tr.appendChild(tdTypeCuisine);
      tr.appendChild(tdTags);
      tr.appendChild(tdOwner);
      tr.appendChild(tdActions);

      rowsTbody.appendChild(tr);

      if (window.bootstrap && bootstrap.Tooltip) {
        document
          .querySelectorAll('#mrds-restaurants-app [data-bs-toggle="tooltip"]')
          .forEach(function (el) {
            var inst = bootstrap.Tooltip.getInstance(el);
            if (inst) inst.dispose();
            new bootstrap.Tooltip(el);
          });
      }
    });
  }

  function fillForm(r) {
    resetForm();

    // Afficher le formulaire même pour les non-admins
    var formCard = document.getElementById("mrds-restaurant-form-card");
    if (formCard) formCard.style.display = "block";

    fieldId.value = r.id;
    fieldTitle.value = r.title || "";

    if (fieldOwner && r.owner && r.owner.id) {
      fieldOwner.value = String(r.owner.id);
    }

    if (fieldRestaurateurs && Array.isArray(r.restaurateurs)) {
      var ids = r.restaurateurs.map(function (u) {
        return String(u.id);
      });
      Array.from(fieldRestaurateurs.options).forEach(function (o) {
        o.selected = ids.indexOf(o.value) !== -1;
      });
    }

    fieldAdrRue.value = r.adresse_rue || "";
    fieldCP.value = r.code_postal || "";
    fieldVille.value = r.ville || "";
    fieldArr.value = r.arrondissement || "";

    fieldTel.value = r.telephone || "";
    fieldSiteWeb.value = r.site_web || "";

    if (fieldTypeCuisine && r.type_cuisine) {
      var tcIds = Array.isArray(r.type_cuisine)
        ? r.type_cuisine.map(String)
        : [String(r.type_cuisine)];
      Array.from(fieldTypeCuisine.options).forEach(function (o) {
        o.selected = tcIds.indexOf(o.value) !== -1;
      });
    }

    if (fieldTagsRest && r.tags_restaurant) {
      var tagIds = Array.isArray(r.tags_restaurant)
        ? r.tags_restaurant.map(String)
        : [String(r.tags_restaurant)];
      Array.from(fieldTagsRest.options).forEach(function (o) {
        o.selected = tagIds.indexOf(o.value) !== -1;
      });
    }

    fieldDescMenu.value = r.description_menu || "";
    fieldExemplePlats.value = r.exemple_de_plats || "";

    if (r.citation) {
      fieldCitationDesc.innerHTML = r.citation.description || "";
      fieldCitationAut.value = r.citation.auteur || "";
    }

    tarifsContainer.innerHTML = "";
    if (Array.isArray(r.tarifs)) {
      r.tarifs.forEach(function (t) {
        addTarifRow(t.nom_de_menu || "", t.prix || "");
      });
    }

    horairesContainer.innerHTML = "";
    if (Array.isArray(r.horaires)) {
      r.horaires.forEach(function (h) {
        addHoraireRow(h.periode || "", Array.isArray(h.jours) ? h.jours : []);
      });
    }

    // Image principale
    if (fieldImage) fieldImage.value = "";
    if (r.thumbnail_url) {
      currentThumbnailId = r.thumbnail_id;
      showImagePreview(r.thumbnail_url);
    } else {
      hideImagePreview();
    }

    // Galerie
    if (galleryInput) galleryInput.value = "";
    currentGallery = Array.isArray(r.gallery) ? r.gallery : [];
    renderGalleryPreview();

    formTitle.textContent = "Modifier le restaurant #" + r.id;

    if (formCard) {
    var offset = formCard.getBoundingClientRect().top + window.scrollY - 110;
    window.scrollTo({ top: offset, behavior: "smooth" });
}
    
  }

  function getSelectedValues(select) {
    return Array.from(select.options)
      .filter(function (o) {
        return o.selected && o.value !== "";
      })
      .map(function (o) {
        return o.value;
      });
  }

  function saveRestaurant(e) {
    e.preventDefault();

    var id = fieldId.value.trim();
    var imageFile = fieldImage && fieldImage.files[0];
    var galleryFile = galleryInput && galleryInput.files[0];

    var payload = {
      title: fieldTitle.value,

      restaurant_owner:
        fieldOwner && fieldOwner.value ? parseInt(fieldOwner.value, 10) : null,

      adresse_rue: fieldAdrRue.value,
      adresse_complement: fieldAdrComp.value,
      code_postal: fieldCP.value,
      ville: fieldVille.value,
      arrondissement: fieldArr.value,

      telephone: fieldTel.value,
      site_web: fieldSiteWeb.value,

      type_de_cuisine: fieldTypeCuisine
        ? getSelectedValues(fieldTypeCuisine).map(function (v) {
            return parseInt(v, 10);
          })
        : [],
      tags_restaurant: fieldTagsRest
        ? getSelectedValues(fieldTagsRest).map(function (v) {
            return parseInt(v, 10);
          })
        : [],

      description_menu: fieldDescMenu.value,
      exemple_de_plats: fieldExemplePlats.value,
      citation_description: fieldCitationDesc
        ? fieldCitationDesc.innerHTML
        : "",
      citation_auteur: fieldCitationAut.value,

      tarifs: [],
      horaires: [],
    };

    // ✅ IMPORTANT : n'envoyer restaurant_restaurateurs QUE si le champ existe (admin)
    if (fieldRestaurateurs) {
      payload.restaurant_restaurateurs = getSelectedValues(
        fieldRestaurateurs,
      ).map(function (v) {
        return parseInt(v, 10);
      });
    }

    // (optionnel) si le champ owner n'existe pas dans ton template, ne pas l'envoyer
    if (!fieldOwner) {
      delete payload.restaurant_owner;
    }

    // Tarifs
    tarifsContainer.querySelectorAll(".mrds-tarif-row").forEach(function (row) {
      var nom = row.querySelector(".mrds-tarif-nom").value;
      var prix = row.querySelector(".mrds-tarif-prix").value;
      if (nom || prix) {
payload.tarifs.push({
    nom_de_menu: nom,
    prix: prix ? prix : '',
});
      }
    });

    // Horaires
    horairesContainer
      .querySelectorAll(".mrds-horaire-row")
      .forEach(function (row) {
        var periodeSel = row.querySelector(".mrds-horaire-periode");
        var joursSel = row.querySelector(".mrds-horaire-jours");
        var periode = periodeSel ? periodeSel.value : "";
        var jours = joursSel ? getSelectedValues(joursSel) : [];
        if (periode || jours.length) {
          payload.horaires.push({
            periode: periode,
            jours: jours,
          });
        }
      });

    var method = id ? "PUT" : "POST";
    var url = id ? apiUrl + "/" + id : apiUrl;

    fetch(url, {
      method: method,
      headers: {
        "X-WP-Nonce": nonce,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data && data.code) {
          console.error(data);
          showMessage("Erreur lors de l enregistrement", "error");
          return;
        }

        var restaurantId = data.id || id;
        var uploads = [];

        // Upload image principale si selectionnee
        if (imageFile && restaurantId) {
          uploads.push(uploadImage(restaurantId, imageFile));
        }

        // Upload galerie si selectionnee
        if (galleryFile && restaurantId) {
          uploads.push(uploadGalleryImage(restaurantId, galleryFile));
        }

        if (uploads.length > 0) {
          Promise.all(uploads)
            .then(function () {
              showMessage("Restaurant et images enregistres");
              resetForm();
              fetchRestaurants();
            })
            .catch(function () {
              showMessage(
                "Restaurant enregistre, erreur sur certaines images",
                "error",
              );
              resetForm();
              fetchRestaurants();
            });
        } else {
          showMessage("Restaurant enregistre");
          resetForm();
          fetchRestaurants();
        }
      })
      .catch(function (err) {
        console.error(err);
        showMessage("Erreur reseau", "error");
      });
  }

  function deleteRestaurant(id) {
    fetch(apiUrl + "/" + id, {
      method: "DELETE",
      headers: {
        "X-WP-Nonce": nonce,
        "Content-Type": "application/json",
      },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        showMessage("Restaurant supprime");
        fetchRestaurants();
      })
      .catch(function (err) {
        console.error(err);
        showMessage("Erreur lors de la suppression", "error");
      });
  }

  // =====================
  // EVENTS
  // =====================
  form.addEventListener("submit", saveRestaurant);
  if (btnReset) btnReset.addEventListener("click", resetForm);
  if (btnNew) btnNew.addEventListener("click", resetForm);
  if (btnAddTarif)
    btnAddTarif.addEventListener("click", function () {
      addTarifRow();
    });

  var btnAddHoraire = document.getElementById("mrds-add-horaire-btn");
  if (btnAddHoraire)
    btnAddHoraire.addEventListener("click", function () {
      addHoraireRow();
    });

  if (btnImageRemove)
    btnImageRemove.addEventListener("click", hideImagePreview);

  // Upload galerie en direct quand on selectionne un fichier (si restaurant existe)
  if (galleryInput) {
    galleryInput.addEventListener("change", function () {
      var file = galleryInput.files[0];
      var restaurantId = fieldId.value;

      if (file && restaurantId) {
        uploadGalleryImage(restaurantId, file)
          .then(function () {
            galleryInput.value = "";
          })
          .catch(function () {
            galleryInput.value = "";
          });
      }
    });
  }

  // =====================
  // INIT
  // =====================
  resetForm();
  fetchRestaurants();
});

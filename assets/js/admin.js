/* =====================================================================
   Gedung Inn — JavaScript area admin
   Modal hapus gedung, baris layanan dinamis, dan area unggah CSV.
   ===================================================================== */
(function () {
  "use strict";

  /* -------------------------------------------------------------------
     Toast auto-hide
     ------------------------------------------------------------------- */
  document.querySelectorAll(".toast[data-autohide]").forEach((el) => {
    setTimeout(() => el.remove(), 3000);
  });

  /* -------------------------------------------------------------------
     Modal konfirmasi hapus gedung
     ------------------------------------------------------------------- */
  const deleteModal = document.getElementById("delete-modal");

  function closeModal() {
    if (!deleteModal) return;
    deleteModal.hidden = true;
    document.body.style.overflow = "";
  }

  if (deleteModal) {
    const idInput = deleteModal.querySelector("[data-delete-id]");
    const nameOut = deleteModal.querySelector("[data-delete-name]");

    document.querySelectorAll("[data-delete-venue]").forEach((btn) => {
      btn.addEventListener("click", () => {
        if (idInput) idInput.value = btn.dataset.deleteVenue;
        if (nameOut) nameOut.textContent = btn.dataset.deleteNama || "gedung ini";
        deleteModal.hidden = false;
        document.body.style.overflow = "hidden";
        const confirmBtn = deleteModal.querySelector(".btn.red");
        if (confirmBtn) confirmBtn.focus();
      });
    });

    deleteModal.querySelectorAll("[data-close-modal]").forEach((btn) => {
      btn.addEventListener("click", closeModal);
    });
    deleteModal.addEventListener("mousedown", (event) => {
      if (event.target === deleteModal) closeModal();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !deleteModal.hidden) closeModal();
    });
  }

  /* -------------------------------------------------------------------
     Baris layanan tambahan (tambah / hapus)
     ------------------------------------------------------------------- */
  const serviceRows = document.getElementById("service-rows");
  const addService = document.getElementById("add-service");
  const template = document.getElementById("service-row-template");

  if (addService && serviceRows && template) {
    addService.addEventListener("click", () => {
      const clone = template.content.cloneNode(true);
      serviceRows.appendChild(clone);
      const rows = serviceRows.querySelectorAll(".service-form-row");
      const last = rows[rows.length - 1];
      const input = last ? last.querySelector('input[name="layanan_nama[]"]') : null;
      if (input) input.focus();
    });
  }

  if (serviceRows) {
    serviceRows.addEventListener("click", (event) => {
      const remove = event.target.closest("[data-remove-service]");
      if (!remove) return;
      const row = remove.closest(".service-form-row");
      if (row) row.remove();
    });
  }

  /* -------------------------------------------------------------------
     Area unggah CSV
     ------------------------------------------------------------------- */
  const dropZone = document.getElementById("upload-zone");
  const fileInput = document.getElementById("csv-file");
  const uploadTitle = document.getElementById("upload-title");

  function showFileName(file) {
    if (!file || !uploadTitle) return;
    if (!/\.csv$/i.test(file.name)) {
      uploadTitle.textContent = "Pilih file dengan format CSV.";
      return;
    }
    uploadTitle.textContent = file.name;
  }

  if (fileInput) {
    fileInput.addEventListener("change", () => {
      if (fileInput.files && fileInput.files[0]) showFileName(fileInput.files[0]);
    });
  }

  if (dropZone && fileInput) {
    ["dragenter", "dragover"].forEach((type) => {
      dropZone.addEventListener(type, (event) => {
        event.preventDefault();
        dropZone.classList.add("dragging");
      });
    });
    ["dragleave", "drop"].forEach((type) => {
      dropZone.addEventListener(type, (event) => {
        event.preventDefault();
        dropZone.classList.remove("dragging");
      });
    });
    dropZone.addEventListener("drop", (event) => {
      const files = event.dataTransfer ? event.dataTransfer.files : null;
      if (files && files.length) {
        fileInput.files = files;
        showFileName(files[0]);
      }
    });
  }
})();

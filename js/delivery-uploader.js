document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-delivery-uploader]').forEach(function (uploader) {
    var form = uploader.closest('[data-delivery-form]');
    var fileInput = uploader.querySelector('[data-delivery-file-input]');
    var fileList = uploader.querySelector('[data-delivery-file-list]');
    var comment = form ? form.querySelector('[data-delivery-comment]') : null;
    var fileStore = null;

    if (typeof window.DataTransfer === 'function') {
      try {
        fileStore = new window.DataTransfer();
      } catch (error) {
        fileStore = null;
      }
    }

    function formatFileSize(bytes) {
      if (!bytes) {
        return 'Archivo';
      }
      if (bytes < 1024 * 1024) {
        return Math.ceil(bytes / 1024) + ' KB';
      }
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function selectedFiles() {
      return fileStore ? Array.from(fileStore.files) : Array.from(fileInput.files || []);
    }

    function syncFiles() {
      if (fileStore && fileInput) {
        fileInput.files = fileStore.files;
      }
    }

    function clearValidation() {
      if (comment) {
        comment.setCustomValidity('');
      }
    }

    function renderFiles() {
      if (!fileList || !fileInput) {
        return;
      }

      var files = selectedFiles();
      fileList.innerHTML = '';
      fileList.classList.toggle('d-none', files.length === 0);

      files.forEach(function (file, index) {
        var item = document.createElement('div');
        item.className = 'resource-attachment-item';
        item.innerHTML =
          '<span class="resource-attachment-icon"><i class="fas fa-file-alt"></i></span>' +
          '<span class="resource-attachment-body"><strong></strong><small></small></span>' +
          '<button type="button" class="resource-remove-button" aria-label="Quitar archivo"><i class="fas fa-times"></i></button>';

        item.querySelector('strong').textContent = file.name;
        item.querySelector('small').textContent = formatFileSize(file.size);
        item.querySelector('button').addEventListener('click', function () {
          if (fileStore) {
            var nextStore = new window.DataTransfer();
            Array.from(fileStore.files).forEach(function (currentFile, currentIndex) {
              if (currentIndex !== index) {
                nextStore.items.add(currentFile);
              }
            });
            fileStore = nextStore;
            syncFiles();
          } else {
            fileInput.value = '';
          }
          clearValidation();
          renderFiles();
        });

        fileList.appendChild(item);
      });
    }

    if (fileInput) {
      fileInput.addEventListener('change', function () {
        if (fileStore) {
          try {
            Array.from(fileInput.files || []).forEach(function (file) {
              fileStore.items.add(file);
            });
            syncFiles();
          } catch (error) {
            fileStore = null;
          }
        }
        clearValidation();
        renderFiles();
      });
    }

    if (comment) {
      comment.addEventListener('input', clearValidation);
    }

    if (form) {
      form.addEventListener('submit', function (event) {
        var hasExistingFiles = form.getAttribute('data-has-existing-files') === '1';
        var hasSelectedFiles = selectedFiles().length > 0;
        var hasComment = comment && comment.value.trim() !== '';

        if (!hasExistingFiles && !hasSelectedFiles && !hasComment) {
          event.preventDefault();
          if (comment) {
            comment.setCustomValidity('Adjuntá al menos un archivo o escribí un comentario con tu entrega.');
            comment.reportValidity();
          }
        }
      });
    }
  });
});

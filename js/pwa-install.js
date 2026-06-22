(function () {
  'use strict';

  var deferredPrompt = null;
  var botones = [];

  function esStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function esIOS() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
  }

  function actualizarBotones(visibles) {
    botones.forEach(function (boton) {
      var contenedor = boton.closest('[data-pwa-install-container]');
      if (contenedor) {
        contenedor.classList.toggle('d-none', !visibles);
      } else {
        boton.classList.toggle('d-none', !visibles);
      }
    });
  }

  function mostrarAyuda() {
    var modal = document.getElementById('pwaInstallHelpModal');
    var textoIOS = document.getElementById('pwaInstallIOS');
    var textoGeneral = document.getElementById('pwaInstallGeneral');

    if (textoIOS) {
      textoIOS.classList.toggle('d-none', !esIOS());
    }
    if (textoGeneral) {
      textoGeneral.classList.toggle('d-none', esIOS());
    }

    if (modal && window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(modal).modal('show');
    }
  }

  function instalar() {
    if (!deferredPrompt) {
      mostrarAyuda();
      return;
    }

    deferredPrompt.prompt();
    deferredPrompt.userChoice.finally(function () {
      deferredPrompt = null;
      actualizarBotones(false);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    botones = Array.prototype.slice.call(document.querySelectorAll('button[data-pwa-install]'));
    botones.forEach(function (boton) {
      boton.addEventListener('click', instalar);
    });

    if (!esStandalone()) {
      actualizarBotones(true);
    }
  });

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredPrompt = event;
    actualizarBotones(!esStandalone());
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    actualizarBotones(false);
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('./service-worker.js').catch(function () {
        // La aplicacion sigue funcionando normalmente si el navegador no admite el registro.
      });
    });
  }
})();

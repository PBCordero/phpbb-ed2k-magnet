/* FILE: styles/all/theme/ed2k_modal.js */

// Asegurarse de que jQuery y el DOM están listos
jQuery(function($) {

    // Cachear los elementos del DOM que no cambian
    const $triggerBtn = $('#ed2k-modal-trigger-btn');
    const $modalOverlay = $('#ed2k-modal-overlay');
    // const $modalContent = $('#ed2k-modal-content'); // No es estrictamente necesario cachearlo
    const $modalClose = $('#ed2k-modal-close');
    const $linksList = $('#ed2k-links-list');
    const $copyBtn = $('#ed2k-copy-btn');
    const $copyFeedback = $('#ed2k-copy-feedback');
	const $countSpan = $('#ed2k-count');

    // Función para mostrar/ocultar el botón flotante
    function updateButtonState() {
		 // Buscar los checkbox activos *en este momento* (importante para AJAX/previews)
        const checkedCount = $('.ed2k-magnet-checkbox:checked').length;
		$countSpan.text(checkedCount); // Actualizar contador
        if (checkedCount > 0) {
            $triggerBtn.fadeIn(200);
        } else {
            $triggerBtn.fadeOut(200);
        }
    }

     // Función para cerrar el modal
     function closeModal() {
         $modalOverlay.fadeOut(300);
     }

     // Función para mostrar feedback de copiado
      function showFeedback(){
         $copyFeedback.fadeIn(200).delay(1500).fadeOut(400); // Muestra, espera 1.5s, oculta
      }


    // === EVENT LISTENERS ===

    // 1. Detectar cambios en CUALQUIER checkbox (usa delegación de eventos)
    // Es más robusto si phpBB carga contenido via AJAX (ej. vista previa, respuesta rápida)
    $(document).on('change', '.ed2k-magnet-checkbox', updateButtonState);

    // 2. Clic en el botón flotante para abrir el modal
    $triggerBtn.on('click', function() {
        let links = [];
        // Recolectar data-raw de los checkbox chequeados *en este momento*
        $('.ed2k-magnet-checkbox:checked').each(function() {
            const rawLink = $(this).data('raw');
             if(rawLink){
                 links.push(rawLink);
             }
        });
        
        // Poner los enlaces en el textarea, uno por línea
        $linksList.val(links.join('\n')); 
        
        $copyFeedback.hide(); // Ocultar feedback antiguo
        $modalOverlay.css('display', 'flex').hide().fadeIn(300); // Asegura 'flex' para centrar y luego fadeIn
        $linksList.focus(); // Poner el foco en el textarea
    });

    // 3. Clic en el botón de cerrar (X)
    $modalClose.on('click', closeModal);

     // 4. Clic FUERA del contenido del modal (en el overlay oscuro)
     $modalOverlay.on('click', function(event) {
        // Comprueba si el clic fue directamente en el overlay y no en el contenido interno
        if (event.target === this) { 
            closeModal();
        }
     });

     // 5. Pulsar tecla ESCAPE
      $(document).on('keydown', function(event) {
       // Si se pulsa ESC y el modal es visible
       if (event.key === "Escape" && $modalOverlay.is(':visible')) {
            closeModal();
         }
     });

    // 6. Clic en el botón de Copiar
    $copyBtn.on('click', function() {
       const textToCopy = $linksList.val();
        if (!textToCopy) {
             return; // No hay nada que copiar
        }

       // Intenta usar la API moderna Clipboard
        if (navigator.clipboard && window.isSecureContext) {
             navigator.clipboard.writeText(textToCopy).then(function() {
                // Éxito
                 showFeedback();
             }, function(err) {
                // Error
                 console.error('ED2K Modal: Fallo al copiar con Clipboard API: ', err);
                  alert('No se pudo copiar automáticamente. Por favor, copia manualmente.');
             });
        } else {
           // Fallback para navegadores antiguos o contextos no seguros (http)
            $linksList.select(); // Selecciona el texto
            try {
               const successful = document.execCommand('copy');
                if(successful) {
                   showFeedback();
                } else {
                    alert('No se pudo copiar automáticamente. Por favor, copia manualmente.');
                }
            } catch (err) {
                console.error('ED2K Modal: Fallo al copiar con execCommand: ', err);
                 alert('No se pudo copiar automáticamente. Por favor, copia manualmente.');
            }
        }
    });

    // === EJECUCIÓN INICIAL ===
     // Comprobar estado al cargar la página por si acaso
    updateButtonState(); 

}); // Fin jQuery ready
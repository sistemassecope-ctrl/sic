/**
 * Sistema de Firma Digital - Componente de Estampado
 * Este componente permite solicitar la firma del usuario en cualquier documento
 * 
 * USO:
 * 1. Incluir este script en la página
 * 2. Llamar a FirmaDigital.solicitarFirma() cuando se necesite firmar
 * 3. La función retorna una promesa con la imagen de la firma si es exitosa
 * 
 * EJEMPLO:
 * const result = await FirmaDigital.solicitarFirma('Documento XYZ-123');
 * if (result.success) {
 *     // result.firma_imagen contiene la imagen en base64
 *     // Insertar en el documento o enviar al servidor
 * }
 */

const FirmaDigital = (function() {
    let modal = null;
    let resolvePromise = null;
    let rejectPromise = null;
    
    const apiUrl = '/pao/modulos/recursos-humanos/api/verificar-pin.php';
    
    // Crear el modal de solicitud de PIN
    function createModal() {
        if (modal) return modal;
        
        const modalHTML = `
        <div id="firmaDigitalModal" class="firma-modal-overlay" style="display: none;">
            <div class="firma-modal-container">
                <div class="firma-modal-header">
                    <div class="firma-modal-icon">
                        <i class="fas fa-signature"></i>
                    </div>
                    <h3>Firma Digital</h3>
                    <p id="firmaDocReferencia">Confirme su identidad para firmar</p>
                </div>
                
                <div class="firma-modal-body">
                    <label>Ingrese su PIN de 4 dígitos</label>
                    <div class="firma-pin-inputs">
                        <input type="password" class="firma-pin-digit" maxlength="1" inputmode="numeric" data-index="0" autocomplete="off">
                        <input type="password" class="firma-pin-digit" maxlength="1" inputmode="numeric" data-index="1" autocomplete="off">
                        <input type="password" class="firma-pin-digit" maxlength="1" inputmode="numeric" data-index="2" autocomplete="off">
                        <input type="password" class="firma-pin-digit" maxlength="1" inputmode="numeric" data-index="3" autocomplete="off">
                    </div>
                    <div id="firmaPinError" class="firma-error-message"></div>
                </div>
                
                <div class="firma-modal-footer">
                    <button type="button" class="firma-btn firma-btn-cancel" onclick="FirmaDigital.cancelar()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="firma-btn firma-btn-confirm" id="btnConfirmarFirma" onclick="FirmaDigital.confirmar()">
                        <i class="fas fa-check"></i> Firmar
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .firma-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .firma-modal-overlay.visible {
            opacity: 1;
        }
        
        .firma-modal-container {
            background: linear-gradient(135deg, #1a1f25 0%, #0d1117 100%);
            border: 1px solid rgba(88, 166, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            max-width: 380px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        0 0 40px rgba(88, 166, 255, 0.1);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .firma-modal-overlay.visible .firma-modal-container {
            transform: scale(1);
        }
        
        .firma-modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .firma-modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(88, 166, 255, 0.2), rgba(110, 66, 202, 0.2));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #58a6ff;
        }
        
        .firma-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #ffffff;
            font-weight: 600;
        }
        
        .firma-modal-header p {
            margin: 0.5rem 0 0;
            color: #8b949e;
            font-size: 0.9rem;
        }
        
        .firma-modal-body {
            margin-bottom: 1.5rem;
        }
        
        .firma-modal-body label {
            display: block;
            text-align: center;
            color: #c9d1d9;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .firma-pin-inputs {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }
        
        .firma-pin-digit {
            width: 55px;
            height: 65px;
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            border: 2px solid #30363d;
            border-radius: 12px;
            background: #0d1117;
            color: #ffffff;
            transition: all 0.2s ease;
        }
        
        .firma-pin-digit:focus {
            outline: none;
            border-color: #58a6ff;
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.2);
        }
        
        .firma-pin-digit.filled {
            border-color: #2ea043;
            background: rgba(46, 160, 67, 0.1);
        }
        
        .firma-pin-digit.error {
            border-color: #f85149;
            animation: shake 0.4s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .firma-error-message {
            text-align: center;
            color: #f85149;
            font-size: 0.85rem;
            margin-top: 0.75rem;
            min-height: 1.25rem;
        }
        
        .firma-modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .firma-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .firma-btn-cancel {
            background: #21262d;
            color: #8b949e;
            border: 1px solid #30363d;
        }
        
        .firma-btn-cancel:hover {
            background: #30363d;
            color: #c9d1d9;
        }
        
        .firma-btn-confirm {
            background: linear-gradient(135deg, #58a6ff, #6e42ca);
            color: white;
        }
        
        .firma-btn-confirm:hover {
            box-shadow: 0 5px 20px rgba(88, 166, 255, 0.3);
            transform: translateY(-1px);
        }
        
        .firma-btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        </style>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        modal = document.getElementById('firmaDigitalModal');
        
        // Configurar eventos de los inputs de PIN
        const pinInputs = modal.querySelectorAll('.firma-pin-digit');
        pinInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 1) {
                    this.classList.add('filled');
                    this.classList.remove('error');
                    if (index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }
                
                // Limpiar mensaje de error
                document.getElementById('firmaPinError').textContent = '';
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    pinInputs[index - 1].focus();
                }
                
                if (e.key === 'Enter') {
                    FirmaDigital.confirmar();
                }
            });
        });
        
        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
                FirmaDigital.cancelar();
            }
        });
        
        return modal;
    }
    
    function getPin() {
        const inputs = modal.querySelectorAll('.firma-pin-digit');
        let pin = '';
        inputs.forEach(input => pin += input.value);
        return pin;
    }
    
    function clearInputs() {
        const inputs = modal.querySelectorAll('.firma-pin-digit');
        inputs.forEach(input => {
            input.value = '';
            input.classList.remove('filled', 'error');
        });
        document.getElementById('firmaPinError').textContent = '';
    }
    
    function showError(message) {
        document.getElementById('firmaPinError').textContent = message;
        const inputs = modal.querySelectorAll('.firma-pin-digit');
        inputs.forEach(input => input.classList.add('error'));
    }
    
    return {
        /**
         * Solicitar firma del usuario
         * @param {string} documentoReferencia - Referencia del documento a firmar
         * @returns {Promise} - Resuelve con {success: true, firma_imagen: string} o rechaza con error
         */
        solicitarFirma: function(documentoReferencia = '') {
            return new Promise((resolve, reject) => {
                resolvePromise = resolve;
                rejectPromise = reject;
                
                createModal();
                clearInputs();
                
                // Establecer referencia del documento
                const refEl = document.getElementById('firmaDocReferencia');
                if (documentoReferencia) {
                    refEl.textContent = `Firmando: ${documentoReferencia}`;
                } else {
                    refEl.textContent = 'Confirme su identidad para firmar';
                }
                
                // Guardar referencia para la API
                modal.dataset.documentoReferencia = documentoReferencia;
                
                // Mostrar modal
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('visible');
                    modal.querySelector('.firma-pin-digit').focus();
                }, 10);
            });
        },
        
        confirmar: async function() {
            const pin = getPin();
            
            if (pin.length !== 4) {
                showError('Ingrese los 4 dígitos de su PIN');
                return;
            }
            
            const btn = document.getElementById('btnConfirmarFirma');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        pin: pin,
                        documento_referencia: modal.dataset.documentoReferencia || null
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Cerrar modal y resolver
                    modal.classList.remove('visible');
                    setTimeout(() => {
                        modal.style.display = 'none';
                        if (resolvePromise) {
                            resolvePromise({
                                success: true,
                                firma_imagen: result.firma_imagen
                            });
                        }
                    }, 300);
                } else {
                    showError(result.message || 'PIN incorrecto');
                    clearInputs();
                    modal.querySelector('.firma-pin-digit').focus();
                }
            } catch (error) {
                console.error('Error al verificar PIN:', error);
                showError('Error de conexión. Intente nuevamente.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Firmar';
            }
        },
        
        cancelar: function() {
            modal.classList.remove('visible');
            setTimeout(() => {
                modal.style.display = 'none';
                if (rejectPromise) {
                    rejectPromise({ cancelled: true, message: 'Firma cancelada por el usuario' });
                }
            }, 300);
        },
        
        /**
         * Verificar si el usuario actual tiene firma registrada
         * @returns {Promise} - Resuelve con {tiene_firma: boolean, datos: object}
         */
        verificarEstado: async function() {
            try {
                const response = await fetch('/pao/modulos/recursos-humanos/api/estado-firma.php');
                return await response.json();
            } catch (error) {
                console.error('Error al verificar estado de firma:', error);
                return { success: false, tiene_firma: false };
            }
        }
    };
})();

// Exportar para uso global
window.FirmaDigital = FirmaDigital;

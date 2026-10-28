# Changelog

All Notable changes to `Ceca` will be documented in this file

## 1.0.0 (2021-08-24)

### Added
- First version, support sha256.

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing

## Version 2.0.0

### Changed

Laravel 9/10 Support

## Version 2.1.0 (2026-10-28)

### Added
- Soporte de firma **HMAC-SHA256** (nuevo sistema obligatorio de Cecabank, límite 31/12/2026), con la misma derivación de clave 3DES que los plugins oficiales de Cecabank. Se activa con `CECA_CIFRADO="HMAC"` o `Ceca::setCifrado('HMAC')`.
- Nuevo método `setCifrado()` y clave de configuración `cifrado` (`CECA_CIFRADO`, por defecto `SHA2`).
- Nuevo método `checkNotificationSignature()` para validar la notificación de pago (timing-safe, compatible SHA2/HMAC).
- Las URLs de pasarela por defecto se leen de la configuración (`url_pasarela_produccion` / `url_pasarela_desarrollo`) con las nuevas URLs TPV Web de Cecabank; ya no es necesario sobrescribirlas manualmente desde el cliente.
- Soporte de Laravel 11, 12 y 13 (constraint ampliado; una única base de código).
- Suite de tests PHPUnit (orchestra/testbench) y workflow CI con matriz PHP × Laravel (9 a 13).

### Changed
- En anulaciones ya no se fuerza `SHA1`: se respeta el cifrado configurado (SHA1/SHA2/HMAC).
- El formulario TPV escapa los valores en atributos HTML.

### Fixed
- `setImporte()` ya no pierde céntimos por redondeo de punto flotante (p. ej. `10,67` antes podía quedar en 1066 en lugar de 1067).
- `priceToSQL()` interpreta correctamente importes con un solo decimal (p. ej. `10.6`).
- Propiedad del botón submit inicializada correctamente (`_submit` en lugar de `_setSubmit`).

### Security
- Firma HMAC-SHA256 según el Boletín de Seguridad de Cecabank (clave ampliada a 32 caracteres y nuevo sistema de firma).

### Deprecated
- Firma SHA1/SHA2: Cecabank exigirá HMAC a partir del 31/12/2026.

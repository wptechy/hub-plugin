# WPT Hub Plugin

Plugin HUB pentru ecosistemul WPT OpticaPRO - folosit pe OpticaMedicala.ro pentru gestionarea tenants și distribuție module.

## Descriere

Acest plugin este destinat exclusiv pentru site-ul HUB central (OpticaMedicala.ro) și oferă:

- **Tenant Management**: Gestionare clienți (optical shops) cu plans și module
- **API Server**: REST API pentru sincronizare date cu site-urile tenant
- **Module Manager**: Catalog module disponibile și gestionare activări
- **Release Manager**: Distribuție actualizări pentru themes și plugins
- **Provisioning**: Creare automată tenants și configurare inițială
- **Analytics Dashboard**: Monitorizare tenants și utilizare resurse

## Componente

### Core Classes (`inc/core/`)

- `class-wpt-database.php` - Creare tabele database (HUB tables)
- `class-wpt-custom-post-types.php` - Înregistrare CPT-uri
- `class-wpt-taxonomies.php` - Înregistrare taxonomii
- `class-wpt-acf.php` - Integrare ACF JSON
- `class-wpt-helpers.php` - Funcții helper
- `class-wpt-roles.php` - Roluri și capabilities (HUB roles)
- `class-wpt-default-data.php` - Date default (plans, modules, categories)

### Hub Components (`inc/hub/`)

#### API Server (`inc/hub/api/`)
- `class-wpt-api-server.php` - REST API pentru tenants

#### Admin Interface (`inc/hub/admin/`)
- `class-wpt-release-manager.php` - Gestionare releases
- `class-wpt-sync-config-admin.php` - Configurare sincronizare

#### Admin Views (`inc/hub/admin/views/`)
- `dashboard.php` - Dashboard principal
- `tenants.php` - Lista tenants
- `modules.php` - Catalog module
- `releases.php` - Versiuni disponibile
- `analytics.php` - Statistici și rapoarte
- `settings.php` - Setări HUB
- `sync-config.php` - Configurare sync
- `sync-config-v2.php` - Configurare sync v2
- `tenant-sync-config-section.php` - Secțiune config per tenant

#### Core HUB Classes
- `class-wpt-admin-menus.php` - Meniuri admin
- `class-wpt-module-manager.php` - Manager module
- `class-wpt-provisioning.php` - Creare automată tenants
- `class-wpt-tenant-manager.php` - Gestionare tenants

## Instalare

1. Încarcă plugin-ul în `/wp-content/plugins/wpt-hub-plugin/`
2. Activează plugin-ul din WordPress admin
3. La activare se vor crea automat:
   - Tabelele database necesare
   - Rolurile custom (optica, medic, furnizor, candidat)
   - Plans default (Starter, Business, Enterprise)
   - Module categories și module disponibile

## Cerințe

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ sau MariaDB 10.3+
- Advanced Custom Fields PRO

## Constante

Plugin-ul definește următoarele constante:

```php
WPT_IS_HUB = true          // Întotdeauna TRUE pe HUB
WPT_HUB_VERSION            // Versiunea plugin-ului
WPT_HUB_DIR                // Path către director plugin
WPT_HUB_URL                // URL către plugin
```

## Database Tables

Plugin-ul creează următoarele tabele (prefix: `wp_`):

### HUB Tables
- `wpt_tenants` - Clienți înregistrați
- `wpt_plans` - Plans de abonament
- `wpt_tenant_addons` - Addon-uri activate per tenant
- `wpt_available_modules` - Catalog module disponibile
- `wpt_module_categories` - Categorii module
- `wpt_tenant_modules` - Module activate per tenant
- `wpt_releases` - Versiuni plugins/themes
- `wpt_site_versions` - Versiuni instalate per tenant
- `wpt_recommendations` - Reviews clienți
- `wpt_analytics` - Statistici utilizare

### Common Tables
- `wpt_sync_queue` - Queue sincronizare
- `wpt_error_logs` - Log-uri erori

## API Endpoints

### Authentication
Toate request-urile necesită:
- Header: `X-Tenant-Key: {tenant_key}`
- Header: `X-API-Key: {api_key}`

### Endpoints disponibile
- `GET /wp-json/wpt/v1/sync/check` - Verificare conexiune
- `GET /wp-json/wpt/v1/sync/config` - Obținere configurare sync
- `GET /wp-json/wpt/v1/sync/pull` - Pull date de la HUB
- `POST /wp-json/wpt/v1/sync/push` - Push date către HUB
- `GET /wp-json/wpt/v1/modules/list` - Lista module disponibile
- `POST /wp-json/wpt/v1/modules/activate` - Activare modul

## Roles & Capabilities

### HUB Roles
- `optica` - Proprietar optica (access limitat la propriul brand)
- `medic` - Doctor (acces la profil și program)
- `furnizor` - Supplier (acces la produse)
- `candidat` - Job applicant (acces la propriul profil)

### Admin Capabilities
- `manage_website_config` - Configurare website
- `purchase_modules` - Achiziție module
- `view_wpt_logs` - Vizualizare logs
- `view_wpt_transactions` - Vizualizare tranzacții

## Development

### Hooks disponibile

```php
// După creare tenant
do_action('wpt_tenant_created', $tenant_id, $tenant_data);

// După activare modul
do_action('wpt_module_activated', $tenant_id, $module_slug);

// Înainte de push sync
do_action('wpt_before_sync_push', $post_id, $post_type);
```

### Filtre disponibile

```php
// Modificare date sync înainte de trimitere
apply_filters('wpt_sync_data', $data, $post_id, $post_type);

// Modificare configurare sync
apply_filters('wpt_sync_config', $config, $tenant_id);
```

## Securitate

- Toate API calls sunt autentificate cu tenant_key + api_key
- Rate limiting: 100 requests/min per tenant
- SQL injection protection via $wpdb->prepare()
- XSS protection via esc_* functions
- Nonce verification pentru admin forms

## Support

Pentru suport tehnic: tech@opticamedicala.ro

## License

GPL v2 or later

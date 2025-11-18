<?php
/**
 * Custom translations for HUB plugin strings.
 *
 * @package WPT_Optica_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPT_Hub_Translations {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'gettext', array( __CLASS__, 'translate_strings' ), 10, 3 );
	}

	/**
	 * Translate selected strings in admin/frontend.
	 *
	 * @param string $translated Current translated text.
	 * @param string $text       Original text.
	 * @param string $domain     Text domain.
	 *
	 * @return string
	 */
	public static function translate_strings( $translated, $text, $domain ) {
		if ( 'wpt-optica-core' !== $domain ) {
			return $translated;
		}

        static $map = array(
            'Actions' => 'Acțiuni',
            'Active' => 'Activ',
            'Add New Module' => 'Adaugă modul nou',
            'Add New Plan' => 'Adaugă plan nou',
            'Add New Tenant' => 'Adaugă tenant nou',
            'Add New' => 'Adaugă',
            'Addon created successfully' => 'Add-on creat cu succes',
            'Addon deleted successfully' => 'Add-on șters cu succes',
            'Addon not found.' => 'Add-on-ul nu a fost găsit.',
            'Addon updated successfully' => 'Add-on actualizat cu succes',
            'Admin' => 'Administrator',
            'Analytics (30 days)' => 'Statistici (30 de zile)',
            'Analytics' => 'Statistici',
            'API Key' => 'Cheie API',
            'Avg. Pages/Visit' => 'Pagini/visită (medie)',
            'Are you sure you want to delete this tenant? This action cannot be undone.' => 'Sigur vrei să ștergi acest tenant? Acțiunea este ireversibilă.',
            'Are you sure you want to delete this?' => 'Sigur vrei să ștergi acest element?',
            'Brand' => 'Brand',
            'Brand post associated with this tenant' => 'Articolul de tip brand asociat acestui tenant',
            'Cancel' => 'Anulează',
            'Categories' => 'Categorii',
            'Created' => 'Creat la',
            'Cannot activate addon. Required modules must be active first: %s' => 'Add-on-ul nu poate fi activat. Modulele necesare trebuie active înainte: %s',
            'Cannot delete addon. %d tenants are using this addon.' => 'Add-on-ul nu poate fi șters. %d tenanți îl folosesc.',
            'Cannot delete category. It has %d modules assigned. Please reassign or delete them first.' => 'Categoria nu poate fi ștearsă. Are %d module asociate. Realocă sau șterge mai întâi modulele.',
            'Cannot delete plan. %d active tenants are using this plan.' => 'Planul nu poate fi șters. %d tenanți activi folosesc acest plan.',
            'Category created successfully' => 'Categoria a fost creată cu succes',
            'Category deleted successfully' => 'Categoria a fost ștearsă cu succes',
            'Category not found' => 'Categoria nu a fost găsită',
            'Category updated successfully' => 'Categoria a fost actualizată cu succes',
            'Configuration pushed successfully!' => 'Configurația a fost trimisă cu succes!',
            'Configuration saved!' => 'Configurația a fost salvată!',
            'Create Addon' => 'Creează add-on',
            'Create Module' => 'Creează modul',
            'Create New Addon' => 'Creează add-on nou',
            'Create New Plan' => 'Creează plan nou',
            'Create Plan' => 'Creează planul',
            'Create Tenant' => 'Creează tenantul',
            'Dashboard' => 'Tablou de bord',
            'Database Version:' => 'Versiune bază de date:',
            'Delete' => 'Șterge',
            'Error pushing configuration' => 'Eroare la trimiterea configurației',
            'Error saving configuration' => 'Eroare la salvarea configurației',
            'Failed tenants:' => 'Tenanți la care a eșuat:',
            'Failed to activate addon.' => 'Activarea add-on-ului a eșuat.',
            'Full URL including https:// (can be set later)' => 'URL complet, inclusiv https:// (poate fi setat ulterior)',
            'General' => 'General',
            'HUB User' => 'Utilizator HUB',
            'ID' => 'ID',
            'Insufficient permissions' => 'Permisiuni insuficiente',
            'Invalid user ID' => 'ID utilizator invalid',
            'Latest:' => 'Ultima versiune:',
            'Job Applications' => 'Aplicări la joburi',
            'Module Categories' => 'Categorii de module',
            'Module availability updated and pushed to all %d affected tenants successfully' => 'Disponibilitatea modulului a fost actualizată și trimisă cu succes către toți cei %d tenanți afectați',
            'Module availability updated. Pushed to %d/%d tenants. %d failed.' => 'Disponibilitatea modulului a fost actualizată. Trimis către %d/%d tenanți. %d au eșuat.',
            'Module created successfully' => 'Modul creat cu succes',
            'Module info pushed to %d/%d tenants. %d failed.' => 'Informațiile despre modul au fost trimise către %d/%d tenanți. %d au eșuat.',
            'Module info pushed to all %d tenants successfully' => 'Informațiile despre modul au fost trimise cu succes către toți cei %d tenanți',
            'Module not found or inactive.' => 'Modulul nu a fost găsit sau este inactiv.',
            'Module not found.' => 'Modulul nu a fost găsit.',
            'Module not found' => 'Modulul nu a fost găsit',
            'Module updated successfully' => 'Modul actualizat cu succes',
            'Modules' => 'Module',
            'N/A' => 'N/A',
            'None' => 'Fără',
            'No plan (optional)' => 'Fără plan (opțional)',
            'No tenants found' => 'Nu a fost găsit niciun tenant',
            'No data available' => 'Nu există date disponibile',
            'Not Active' => 'Inactiv',
            'Pending Site' => 'Site în așteptare',
            'Pending' => 'În așteptare',
            'Plan & Add-ons' => 'Plan și add-on-uri',
            'Plan created successfully' => 'Plan creat cu succes',
            'Plan deleted successfully' => 'Plan șters cu succes',
            'Plan updated successfully' => 'Plan actualizat cu succes',
            'Plan' => 'Plan',
            'Plans & Pricing' => 'Planuri și tarife',
            'Plugin Version:' => 'Versiune plugin:',
            'Pushing to tenant...' => 'Se trimite către tenant...',
            'Quick Actions' => 'Acțiuni rapide',
            'Release not found' => 'Versiunea nu a fost găsită',
            'Releases' => 'Versiuni',
            'REST API:' => 'REST API:',
            'Saved successfully' => 'Salvat cu succes',
            'Saving...' => 'Se salvează...',
            'Select a brand' => 'Selectează un brand',
            'Select a user' => 'Selectează un utilizator',
            'Settings saved successfully' => 'Setările au fost salvate cu succes',
            'Settings' => 'Setări',
            'Site URL' => 'URL site',
            'Site' => 'Site',
            'Page Views' => 'Afișări pagini',
            'Status' => 'Status',
            'System Status' => 'Starea sistemului',
            'Sync Configuration' => 'Configurație sincronizare',
            'Suspended' => 'Suspendat',
            'Tenant Key' => 'Cheie tenant',
            'Tenant created successfully! Tenant Key: %s | API Key: %s' => 'Tenant creat cu succes! Cheie tenant: %s | Cheie API: %s',
            'Tenant deleted successfully' => 'Tenantul a fost șters cu succes',
            'Tenant not found' => 'Tenantul nu a fost găsit',
            'Tenant updated successfully' => 'Tenantul a fost actualizat cu succes',
            'Tenant' => 'Tenant',
            'Tenants Management' => 'Administrare tenanți',
            'Tenants' => 'Tenanți',
            'Unlimited' => 'Nelimitat',
            'Update Category' => 'Actualizează categoria',
            'Update Tenant' => 'Actualizează tenantul',
            'Upload New Release' => 'Încarcă o versiune nouă',
            'Used by: %s' => 'Folosit de: %s',
            'Used to authenticate API requests' => 'Folosită pentru autentificarea cererilor API',
            'Used to identify this tenant in API requests' => 'Utilizată pentru identificarea tenantului în cererile API',
            'User' => 'Utilizator',
            'View All' => 'Vezi toate',
            'View Analytics' => 'Vezi statisticile',
            'Visitors' => 'Vizitatori',
            'Traffic Overview' => 'Imagine de ansamblu trafic',
            'Top Performing Sites' => 'Site-uri cu performanțe ridicate',
            'WordPress user who owns this tenant' => 'Utilizatorul WordPress asociat acestui tenant',
            'WPT Platform' => 'Platforma WPT',
            'Website Manager' => 'Manager site',
            'visitors' => 'vizitatori',
        );

		return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
	}
}

WPT_Hub_Translations::init();

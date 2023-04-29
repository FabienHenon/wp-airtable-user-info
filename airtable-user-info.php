<?php
/*
Plugin Name: Airtable User Info
Plugin URI: https://fabien404.fr/
Description: A shortcode to retrieve user information from Airtable.
Version: 1.0.4
Author: Fabien 404
Author URI: https://fabien404.fr/
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit(); // Exit if accessed directly.
}

function get_airtable_user_info($atts)
{
    // Get the email address of the logged in user
    $current_user_email = wp_get_current_user()->user_email;

    // Parse the shortcode attributes
    $atts = shortcode_atts(
        [
            'field_id' => '',
            'default_value' => '',
        ],
        $atts
    );

    // Get the Airtable API configuration options
    $base_id = get_option('airtable_user_info_base_id');
    $table_id = get_option('airtable_user_info_table_id');
    $email_field_id = get_option('airtable_user_info_email_field_id');
    $bearer_token = get_option('airtable_user_info_bearer_token');

    // Build the Airtable API URL
    $api_url =
        'https://api.airtable.com/v0/' .
        $base_id .
        '/' .
        $table_id .
        '?filterByFormula=' .
        $email_field_id .
        '%3D%22' .
        urlencode($current_user_email) .
        '%22&maxRecords=1&returnFieldsByFieldId=true';

    // Make the API request
    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $bearer_token,
        ],
    ]);

    // Check for errors
    if (is_wp_error($response)) {
        return $atts['default_value'];
    }

    // Parse the API response
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $records = $body['records'];

    // Return the field value or the default value
    if (count($records) > 0) {
        $fields = $records[0]['fields'];
        return isset($fields[$atts['field_id']])
            ? $fields[$atts['field_id']]
            : $atts['default_value'];
    } else {
        return $atts['default_value'];
    }
}

function airtable_user_info_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Configuration du plugin Airtable User Info</h1>
        <p>
            <strong>Utilisation du plugin :</strong><br />
            <code>[airtable_user_info field_id='' default_value='']</code><br />
        </p>
        <ul>
            <li><code>field_id</code> : L'ID du champ qu'on veut afficher</li>
            <li><code>default_value</code> : La valeur par défaut à afficher si aucune donné n'a été trouvée pour l'utilisateur ou si le champ n'existe pas</li>
        </ul>
        <form method="post" action="options.php">
            <?php settings_fields('airtable_user_info_settings_group'); ?>
            <?php do_settings_sections('airtable_user_info_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Base ID :</th>
                    <td><input type="text" name="airtable_user_info_base_id" value="<?php echo esc_attr(
                        get_option('airtable_user_info_base_id')
                    ); ?>" /></td>
                </tr>
                 
                <tr valign="top">
                    <th scope="row">Table ID :</th>
                    <td><input type="text" name="airtable_user_info_table_id" value="<?php echo esc_attr(
                        get_option('airtable_user_info_table_id')
                    ); ?>" /></td>
                </tr>
                 
                <tr valign="top">
                    <th scope="row">Email Field ID :</th>
                    <td><input type="text" name="airtable_user_info_email_field_id" value="<?php echo esc_attr(
                        get_option('airtable_user_info_email_field_id')
                    ); ?>" /></td>
                </tr>
                 
                <tr valign="top">
                    <th scope="row">Bearer Token :</th>
                    <td><input type="password" name="airtable_user_info_bearer_token" value="<?php echo esc_attr(
                        get_option('airtable_user_info_bearer_token')
                    ); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function airtable_user_info_menu()
{
    add_menu_page(
        'Airtable User Info Settings', // titre de la page
        'Airtable User Info Settings', // nom du menu
        'manage_options', // capacités requises pour accéder à cette page
        'airtable-user-info-settings', // slug de la page
        'airtable_user_info_settings_page' // fonction qui affiche le contenu de la page
    );
}

// Enregistrement des options dans la base de données WordPress
function airtable_user_info_register_settings()
{
    register_setting(
        'airtable_user_info_settings_group',
        'airtable_user_info_base_id'
    );
    register_setting(
        'airtable_user_info_settings_group',
        'airtable_user_info_table_id'
    );
    register_setting(
        'airtable_user_info_settings_group',
        'airtable_user_info_email_field_id'
    );
    register_setting(
        'airtable_user_info_settings_group',
        'airtable_user_info_bearer_token'
    );
}

function airtable_user_info_init()
{
    add_shortcode('airtable_user_info', 'get_airtable_user_info');
}

add_action('init', 'airtable_user_info_init');
add_action('admin_init', 'airtable_user_info_register_settings');
add_action('admin_menu', 'airtable_user_info_menu');

<?php
/**
 * GitHub updater class
 */
class WP_Content_Importer_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $github_response;
    private $github_repo;
    private $github_user;
    private $access_token;

    public function __construct($file) {
        error_log('WP Content Importer: Initializing updater');
        $this->file = $file;
        add_action('admin_init', array($this, 'set_plugin_properties'));

        // Set defaults
        $this->github_user = 'woloszynpawel'; // Your GitHub username
        $this->github_repo = 'wp-content-importer'; // Your repository name
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        error_log('WP Content Importer: Updater initialized with user: ' . $this->github_user . ' and repo: ' . $this->github_repo);
    }

    public function set_plugin_properties() {
        error_log('WP Content Importer: Setting plugin properties');
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
        error_log('WP Content Importer: Current version: ' . $this->plugin['Version']);
    }

    private function get_repository_info() {
        if (!empty($this->github_response)) {
            return;
        }

        $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );
        
        error_log('WP Content Importer: Checking GitHub API: ' . $request_uri);

        $response = wp_remote_get($request_uri, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));

        if (is_wp_error($response)) {
            error_log('WP Content Importer: GitHub API Error: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('WP Content Importer: GitHub API Response Code: ' . $response_code);
        error_log('WP Content Importer: GitHub API Response: ' . $body);

        $data = json_decode($body, true);

        if ($data) {
            $this->github_response = $data;
            error_log('WP Content Importer: Found release version: ' . $data['tag_name']);
        }
    }

    public function modify_transient($transient) {
        error_log('WP Content Importer: Checking for updates');
        
        if (!isset($transient->checked)) {
            error_log('WP Content Importer: No checked versions in transient');
            return $transient;
        }

        $this->get_repository_info();

        if (!empty($this->github_response)) {
            $version = str_replace('v', '', $this->github_response['tag_name']);
            $current_version = $this->plugin['Version'];
            
            error_log('WP Content Importer: Comparing versions - Current: ' . $current_version . ' Available: ' . $version);

            if (version_compare($version, $current_version, '>')) {
                error_log('WP Content Importer: New version available');
                
                $package = $this->github_response['zipball_url'];
                error_log('WP Content Importer: Update package URL: ' . $package);

                $obj = new stdClass();
                $obj->slug = $this->basename;
                $obj->new_version = $version;
                $obj->url = $this->plugin['PluginURI'];
                $obj->package = $package;
                $obj->tested = '6.4.3';
                $transient->response[$this->basename] = $obj;
                
                error_log('WP Content Importer: Added update information to transient');
            } else {
                error_log('WP Content Importer: No new version available');
            }
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!empty($args->slug)) {
            if ($args->slug == $this->basename) {
                $this->get_repository_info();

                $plugin = array(
                    'name'              => $this->plugin['Name'],
                    'slug'              => $this->basename,
                    'version'           => $this->github_response['tag_name'],
                    'author'            => $this->plugin['AuthorName'],
                    'author_profile'    => $this->plugin['AuthorURI'],
                    'last_updated'      => $this->github_response['published_at'],
                    'homepage'          => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections'          => array(
                        'description'   => $this->plugin['Description'],
                        'changelog'     => $this->github_response['body'],
                    ),
                    'download_link'     => $this->github_response['zipball_url']
                );

                return (object) $plugin;
            }
        }

        return $result;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
} 
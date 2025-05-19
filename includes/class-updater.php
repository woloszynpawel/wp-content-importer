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
        $this->file = $file;
        add_action('admin_init', array($this, 'set_plugin_properties'));

        // Set defaults
        $this->github_user = 'pawelwoloszyn'; // Your GitHub username
        $this->github_repo = 'wp-content-importer'; // Your repository name
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    public function set_plugin_properties() {
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
    }

    private function get_repository_info() {
        if (!empty($this->github_response)) {
            return;
        }

        $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );

        if ($this->access_token) {
            $request_uri = add_query_arg('access_token', $this->access_token, $request_uri);
        }

        $response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true);

        if (is_array($response)) {
            $response = current($response);
        }

        if ($response) {
            $this->github_response = $response;
        }
    }

    public function modify_transient($transient) {
        if (!isset($transient->checked)) {
            return $transient;
        }

        $this->get_repository_info();

        if (!empty($this->github_response)) {
            $version = str_replace('v', '', $this->github_response['tag_name']);

            if (version_compare($version, $this->plugin['Version'], '>')) {
                $package = $this->github_response['zipball_url'];

                if ($this->access_token) {
                    $package = add_query_arg('access_token', $this->access_token, $package);
                }

                $obj = new stdClass();
                $obj->slug = $this->basename;
                $obj->new_version = $version;
                $obj->url = $this->plugin['PluginURI'];
                $obj->package = $package;
                $obj->tested = '6.4.3'; // Update this with your tested WordPress version
                $transient->response[$this->basename] = $obj;
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
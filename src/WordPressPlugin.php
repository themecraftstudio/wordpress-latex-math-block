<?php

namespace Themecraft\WordPress\LatexMathBlock;

/**
 * @author Themecraft Studio <info@themecraft.studio>
 * 
 * Base class for WordPress plugins.
 */
abstract class WordPressPlugin
{
    const ENTRY_SCRIPT = 'plugin.php';

    /** @var static */
    protected static $instance;

    /** @var string Full (not real) path of the plugin e.g. /home/wordpress/www/wp-content/plugins/seed/plugin.php */
    protected $entryScriptPathname;

    /** @var string Parent directory of $entryScriptPathname */
    protected $path;

    /** @var string Public URL of parent directory of $entryScriptPathname */
    protected $url;

    /** @var bool */
    protected $bootstrapped = false;

    /** @var bool */
    protected $debug = false;

    /** @var array Scripts to be registered and enqueued */
    protected $scripts = [];

    /** @var string Version of this plugin */
    protected $version;

    /**
     * WordPressPlugin constructor.
     * @param string $path subdirectory of WP_PLUGIN_DIR
     *
     * Called on plugin activation/loading.
     * This is the config/setup phase. This phase is also *performed during activation*.
     * During activation, 'plugins_loaded' will not be fired and therefore the plugin won't boot.
     * 
     */
    protected function __construct()
    {
        // Determines the not resolved (i.e., WP_PLUGIN_DIR subdir) path of the entry script.
        // https://developer.wordpress.org/plugins/plugin-basics/determining-plugin-and-content-directories/

        // Activation: plugin-seed/plugin.php
        // Load: /Users/ettore/Workspace/Themecraft/seed-wordpress/wp/wp-content/plugins/plugin-seed/plugin.php
        global $plugin;

        assert(!empty($plugin), __METHOD__.' can be called only on plugin activation or loading');

        if (did_action('plugins_loaded')) {
            // Activation
            $this->entryScriptPathname = WP_PLUGIN_DIR .'/'. $plugin;
        } else {
            // Load
            $this->entryScriptPathname = $plugin;
        }

        // Notice: $entry is always a subdir of WP_PLUGIN_DIR and therefore may not be the real/resolved path.
        assert(is_file($this->entryScriptPathname));
        assert(substr($this->entryScriptPathname, 0, strlen(WP_PLUGIN_DIR)) === WP_PLUGIN_DIR);

        // Main configuration
        $this->configure();

        // Bootstraps the plugin at the end of the config phase.
        // During activation 'plugins_loaded' is not fired and therefore the plugin is not bootstrapped.
        add_action('plugins_loaded', function () {
            $this->bootstrap();
        });
    }

    /**
     * This is called as soon as possible from the plugin's entry script.
     * This method's goal should be to configure the plugin.
     * This method is also called _during plugin activation_.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->path = plugin_dir_path($this->entryScriptPathname);
        $this->url = plugin_dir_url($this->entryScriptPathname);

        // Debug can be toggled at runtime (to e.g. send logs to Sentry)
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
    }

    //region Public API

    /**
     * Should be called from the plugin's entry file (i.e., the one with the headers) on the first time.
     */
    public static function getInstance(): self
    {
        if (!static::$instance)
            static::$instance = new static();

        return static::$instance;
    }

    public function getPath(string $rel = ''): string
    {
        return $this->path . trim($rel, '/');
    }

    public function getUrl(string $rel = ''): string
    {
        return $this->url . trim($rel, '/');
    }

    public function enableDebug(): self
    {
        $this->debug = true;
        return $this;
    }

    public function disableDebug(): self
    {
        $this->debug = false;
        return $this;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    /**
     * All non-admin are registered (via wp_register_Script) on 'init',
     * whereas admin scripts are registered only on 'admin_init'.
     *
     * @param string $handle
     * @param string $relPath
     * @param array $deps
     * @param [type] $version
     * @param boolean $inFooter
     * @param boolean $admin
     * @param boolean $esModule
     * @return self
     */
    public function registerScript(string $handle, string $relPath, array $deps = [], $version = null, bool $inFooter = true, bool $esModule = false): self
    {
        assert(!$this->bootstrapped);

        // Determine actual path
        $path = $this->getPath($relPath);

        if (!is_file($path))
            return $this->addAdminNotice(sprintf('Unable to locate script %s', $path), 'error');

        if (!(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)) {
            $info = pathinfo($path);
            $minPath = "{$info['dirname']}/{$info['filename']}.min.${info['extension']}";

            if (is_file($minPath)) {
//            	$path = $minPath;
                $relPath = str_replace($this->getPath(), '', $minPath);
            }
        }

        if ($esModule)
            // Blocks WP from revving the module to avoid any other importing module to execute this module multiple times.
            // "<script module src='$path.js?v=1'", and "import from '$path.js'" are treated as two different URLs by browsers/ES importers.
            $version = null;
        elseif (empty($version))
            $version = $this->getVersion();

        $this->scripts[$handle] = [
            'path' => $this->getUrl($relPath),
            'deps' => $deps,
            'version' => $esModule ? null : $version,
            'footer' => $inFooter,
            // 'admin' => $admin,
            'module' => $esModule,
        ];

        return $this;
    }

    public function registerExternalScript(string $handle, string $url, array $deps = [], bool $inFooter = true, bool $esModule = false): self
    {
        assert(!$this->bootstrapped);

        $this->scripts[$handle] = [
            'path' => $url,
            'deps' => $deps,
            'version' => false,
            'footer' => $inFooter,
            'module' => $esModule,
            // 'admin' => $admin,
        ];

        return $this;
    }

    /**
     * Enqueues a script on both front and admin.
     * 
     * Can be called not later than admin|wp_footer hook.
     *
     * @param string $handle
     * @return WordPressPlugin
     */
    public function enqueueFrontScript(string $handle): self
    {
        assert(isset($this->scripts[$handle]));
        assert(!did_action('wp_print_footer_scripts'));
        assert($this->scripts[$handle]['footer'] || !did_action('wp_enqueue_scripts'));

        if (isset($this->scripts[$handle]))
            $this->scripts[$handle]['front'] = true;

        return $this;
    }

    public function enqueueAdminScript(string $handle): self
    {
        assert(isset($this->scripts[$handle]));
        assert(!did_action('admin_print_footer_scripts'));
        assert($this->scripts[$handle]['footer'] || !did_action('admin_enqueue_scripts'));

        if (isset($this->scripts[$handle]))
            $this->scripts[$handle]['admin'] = true;

        return $this;
    }

    public function addAdminNotice(string $message, string $type = 'info', $dismissible = false): self
    {
        assert(!did_action('admin_notices'));

        add_action('admin_notices', function () use ($message, $type, $dismissible) {
            echo sprintf('<div class="notice notice-%s %s"><p>%s</p></div>', $type, $dismissible ? 'is-dismissible': '', $message);
        });

        return $this;
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            if (is_file($versionFile = $this->getPath('.gitversion'))) {
                $this->version = trim(file_get_contents($versionFile));
            } else {
                // Fallback to style.css header
                $headerData = get_file_data($this->entryScriptPathname, ['version' => 'Version']);
                $this->version = $headerData['version'];
            }
        }

        return $this->version;
    }

    //endregion Public API


    //region Hooks

    //endregion Hooks

    /**
     * Called on 'plugins_loaded' when the plugin is active. As a consequence, this method is 
     * _not called during plugin activation_.
     * 
     * Plugin configuration should not be performed here, with the only exception being when
     * certain settings depend on other plugins('s configuration). At this stage all other plugins are loaded (e.g. ACF).
     */
    protected function bootstrap(): void
    {
        assert(!$this->bootstrapped);

        add_action('init', function () {
            assert( ((bool) did_action('init')) === doing_action('init'));

            $this->registerScripts();
        });

        add_action('wp_enqueue_scripts', function () {
            // Enqueues front head scripts.
            $this->enqueueFrontScripts(false);
        });
        add_action('wp_footer', function () {
            // Enqueues front footer scripts.
            $this->enqueueFrontScripts(true);
        });

        add_action('admin_enqueue_scripts', function () {
            // Enqueues admin head scripts.
            $this->enqueueAdminScripts(false);
        });
        add_action('admin_footer', function () {
            // Enqueues admin footer scripts.
            $this->enqueueAdminScripts(true);
        });

        $this->bootstrapped = true;
    }

    protected function registerScripts(): void
    {
        foreach ($this->scripts as $handle => $script)
            wp_register_script($handle, $script['path'], $script['deps'], $script['version'], $script['footer']);
    }

    /**
     * Enqueue scripts based on (footer, front, admin) combination.
     * 
     * When both $admin and $front are set, any script is enqueued.
     */
    protected function enqueueScripts(bool $footer, bool $front, bool $admin): void
    {
        foreach ($this->scripts as $handle => $script) {
            if ($script['footer'] !== $footer || wp_script_is( $handle, 'enqueued' ))
                continue;

            if ((isset($script['front']) && $script['front'] === $front) 
                || (isset($script['admin']) && $script['admin'] === $admin))

                wp_enqueue_script($handle);
        }
    }

    protected function enqueueAdminScripts(bool $footer): void
    {
        $this->enqueueScripts($footer, false, true);
    }

    protected function enqueueFrontScripts(bool $footer): void
    {
        $this->enqueueScripts($footer, true, false);
    }    
}
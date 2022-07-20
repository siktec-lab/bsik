<?php

namespace Bsik\Render;

require_once PLAT_PATH_AUTOLOAD;

use \Twig\Loader\FilesystemLoader;
use \Twig\Loader\ArrayLoader;
use \Twig\Loader\ChainLoader;
use \Twig\Environment;

if (!defined("PLAT_PAGES_BLOCKS")) define("PLAT_PAGES_BLOCKS", "");
if (!defined("PLAT_PAGES_TEMPLATES")) define("PLAT_PAGES_TEMPLATES", "");
if (!defined("PLAT_TEMPLATES_CACHE")) define("PLAT_TEMPLATES_CACHE", "cache");

class Template {

	private static string $ext = 'tpl';

	private string $cache_path = "";

	private bool   $cache_enable = false;
	private bool   $debug 		 = false;
	private bool   $auto_reload  = false;

	public ChainLoader $loader;
	public Environment $env;

	public function __construct(
		string $cache 			= PLAT_TEMPLATES_CACHE,
		bool   $cache_enable 	= true,
		bool   $debug 			= PLAT_TEMPLATE_DEBUG,
		bool   $auto_reload 	= PLAT_TEMPLATE_RELOAD
	) {
		$this->cache_enable = $cache_enable;
		$this->cache_path   = $cache;
		$this->debug 		= $debug;
		$this->auto_reload  = $auto_reload;
		$this->set();
	}

	public function set() {
		$this->loader = new ChainLoader([]);
		$this->env = new Environment($this->loader, [
			'debug' 		=> $this->debug,
			'auto_reload' 	=> $this->auto_reload,
			'cache' 		=> !empty($this->cache_path) ? $this->cache_path : false,
		]);
		$this->env->addGlobal("__DEBUG__", $this->debug);
		$this->addExtension(new \Twig\Extension\DebugExtension());
		$this->addExtension(new \Bsik\Render\TemplatingExtension());
	}

	public function addExtension(\Twig\Extension\ExtensionInterface $ext) {
		if (!$this->env->hasExtension(\get_class($ext))) {
			$this->env->addExtension($ext);
		}
	}

	public function addTemplates(array $templates) {
		$to_load = [];
		foreach ($templates as $name => $template) {
			if (!str_ends_with($name, self::$ext))
				$to_load[$name.'.'.self::$ext] = $template;
			else 
				$to_load[$name] = $template;
		}
		$array_loader = new ArrayLoader($to_load);
		$this->loader->addLoader($array_loader);
	}

	public function addFolders($paths) {
		$to_load = [];
		foreach ($paths as $path) {
			if (file_exists($path))
				$to_load[] = $path;
		}
		$folder_loader = new FilesystemLoader($to_load);
		$this->loader->addLoader($folder_loader);
	}
	
	public function render(string $name, array $context = []) : string {
		$template = $this->env->load($name.'.'.self::$ext);
		return $template->render($context);
	}
}

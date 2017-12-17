<?php

namespace Xclydes\Larva;

use Collective\Html\FormBuilder as LaravelForm;
use Collective\Html\HtmlBuilder;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class LarvaServiceProvider extends ServiceProvider {
	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		if( !defined( '_XCLYDESLARVA_NS_CLASSES_' ) ) {
			define('_XCLYDESLARVA_NS_CLASSES_', '\Xclydes\Larva');
		}
		if( !defined( '_XCLYDESLARVA_NS_RESOURCES_' ) ) {
			define('_XCLYDESLARVA_NS_RESOURCES_', 'xclydes-larva');
		}
		
        $this->registerFormIfNeeded();
        $this->mergeConfigFrom( __DIR__.'/config/larva.php', _XCLYDESLARVA_NS_RESOURCES_);
	}
	
	/**
	 * 
	 */
	public function boot()
	{
		/* Views */
		$viewsDir = __DIR__ . '/views';		
		$this->loadViewsFrom($viewsDir, _XCLYDESLARVA_NS_RESOURCES_);
		$this->publishes([
			$viewsDir => resource_path('views/vendor/' . _XCLYDESLARVA_NS_RESOURCES_)
		], 'views');
		
		/* Languages */
		$langDir = __DIR__.'/lang';
		$this->loadTranslationsFrom($langDir, _XCLYDESLARVA_NS_RESOURCES_);		
		$this->publishes([
			$langDir => resource_path('lang/vendor/' . _XCLYDESLARVA_NS_RESOURCES_),
		], 'translations');

        /* Config */
        $this->publishes([
            __DIR__.'/config/larva.php' => config_path('larva.php'),
        ], 'config');
    }
	
	//--Copies from Kris-Form-Builder
	//https://github.com/kristijanhusak/laravel-form-builder
	
	/**
	 * Add Laravel Form to container if not already set
	 */
	private function registerFormIfNeeded()
	{
		//Register collective html support
		if (!$this->app->offsetExists('html')) {
			
			$this->app->singleton('html', function($app) {
				return new HtmlBuilder($app['url'], $app['view']);
			});
				
				if (! $this->aliasExists('Html')) {
					
					AliasLoader::getInstance()->alias(
							'Html',
							'Collective\Html\HtmlFacade'
							);
				}
		}
		
		//Register collective form support
		if (!$this->app->offsetExists('form')) {
	
			$this->app->singleton('form', function($app) {
	
				// LaravelCollective\HtmlBuilder 5.2 is not backward compatible and will throw an exeption
				// https://github.com/kristijanhusak/laravel-form-builder/commit/a36c4b9fbc2047e81a79ac8950d734e37cd7bfb0
				$form = new LaravelForm($app['html'], $app['url'], $app['view'], csrf_token());
				
				return $form->setSessionStore( $app['session.store'] );
			});
	
			if (! $this->aliasExists('Form')) {	
				AliasLoader::getInstance()->alias(
					'Form',
					'Collective\Html\FormFacade'
				);
			}
		}
		
		//Add the laravel input Facade if not added
		if (! $this->aliasExists('Input')) {
			AliasLoader::getInstance()->alias(
				'Input',
				'Illuminate\Support\Facades\Input'
			);
		}
		
		//Add Kris FormBuilder if not registered
		$this->app->register('Kris\LaravelFormBuilder\FormBuilderServiceProvider');
		if ( !$this->aliasExists('FormBuilder') ) {		
			AliasLoader::getInstance()->alias(
				'FormBuilder',
				'Kris\LaravelFormBuilder\Facades\FormBuilder'
			);
		}
		
		if ( !$this->aliasExists('LarvaHelper') ) {
			AliasLoader::getInstance()->alias(
					'LarvaHelper',
					'Xclydes\Larva\Helpers\LarvaHelper'
					);
		}
	}
	
	/**
	 * Check if an alias already exists in the IOC
	 * @param $alias
	 * @return bool
	 */
	private function aliasExists($alias)
	{
		return array_key_exists($alias, AliasLoader::getInstance()->getAliases());
	}	
}

<?php namespace Xclydes\Larva;

use Illuminate\Support\ServiceProvider;
use Xclydes\Larva\Contracts\ILarvaComponent;

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
	}
}

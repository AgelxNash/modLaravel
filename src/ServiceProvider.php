<?php namespace AgelxNash\modLaravel;

use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{

	public function boot(){
		$this->publishes(array(
			__DIR__.'/../config/config.php' => config_path('modx.php')
		), 'config');
	}
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__.'/../config/config.php',
			'modx'
		);

		$this->app->singleton('modx', function () {
			return new Modx;
		});
	}
}
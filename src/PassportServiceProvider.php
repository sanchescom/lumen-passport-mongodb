<?php

namespace Kayrules\LumenPassport;

use Kayrules\LumenPassport\Console\Commands\Purge;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;

/**
 * Class CustomQueueServiceProvider
 * @package App\Providers
 */
class PassportServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
		$this->app->configure('api');
		$this->app->register(\MoeenBasra\LaravelPassportMongoDB\PassportServiceProvider::class);

        $this->app->singleton(Connection::class, function() {
            return $this->app['db.connection'];
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Purge::class
            ]);
        }

        $this->registerRoutes();
    }
    /**
     * @return void
     */
    public function register()
    {
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function registerRoutes()
    {
        $this->forAccessTokens();
        $this->forTransientTokens();
        $this->forClients();
        $this->forPersonalAccessTokens();
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     *
     * @return void
     */
    public function forAccessTokens()
    {
		$this->app->router->group(['prefix' => config('api.prefix')], function () {
			$this->app->router->post('/oauth/token', [
	            'uses' => '\Kayrules\LumenPassport\Http\Controllers\AccessTokenController@issueToken'
	        ]);
		});

        $this->app->router->group(['prefix' => config('api.prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/oauth/tokens', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\AuthorizedAccessTokenController@forUser',
            ]);

            $this->app->router->delete('/oauth/tokens/{token_id}', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\AuthorizedAccessTokenController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     *
     * @return void
     */
    public function forTransientTokens()
    {
		$this->app->router->group(['prefix' => config('api.prefix')], function () {
	        $this->app->router->post('/oauth/token/refresh', [
	            'middleware' => ['auth'],
	            'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\TransientTokenController@refresh',
	        ]);
		});
    }

    /**
     * Register the routes needed for managing clients.
     *
     * @return void
     */
    public function forClients()
    {
        $this->app->router->group(['prefix' => config('api.prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/oauth/clients', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\ClientController@forUser',
            ]);

            $this->app->router->post('/oauth/clients', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\ClientController@store',
            ]);

            $this->app->router->put('/oauth/clients/{client_id}', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\ClientController@update',
            ]);

            $this->app->router->delete('/oauth/clients/{client_id}', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\ClientController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for managing personal access tokens.
     *
     * @return void
     */
    public function forPersonalAccessTokens()
    {
        $this->app->router->group(['prefix' => config('api.prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/oauth/scopes', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\ScopeController@all',
            ]);

            $this->app->router->get('/oauth/personal-access-tokens', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@forUser',
            ]);

            $this->app->router->post('/oauth/personal-access-tokens', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@store',
            ]);

            $this->app->router->delete('/oauth/personal-access-tokens/{token_id}', [
                'uses' => '\MoeenBasra\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@destroy',
            ]);
        });
    }
}
